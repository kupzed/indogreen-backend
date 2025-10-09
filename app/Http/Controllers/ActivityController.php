<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\Project;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with(['project', 'mitra', 'attachments']);

        if ($request->filled('jenis'))     $query->where('jenis', $request->jenis);
        if ($request->filled('kategori'))  $query->where('kategori', $request->kategori);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('activity_date', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->where('activity_date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('activity_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('project', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%$search%");
                    });
            });
        }

        $perPage = $request->integer('per_page', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) $perPage = 10;

        $activities = $query->paginate($perPage);
        $items = collect($activities->items())->map->toArray()->all();

        return response()->json([
            'message' => 'Activities retrieved successfully',
            'data' => $items,
            'pagination' => [
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'from' => $activities->firstItem(),
                'to' => $activities->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'required|string',
            'project_id'    => 'required|exists:projects,id',
            'kategori'      => ['required', Rule::in([
                'Expense Report', 'Invoice', 'Purchase Order', 'Payment', 'Quotation',
                'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar', 'Kontrak'
            ])],
            'activity_date' => 'required|date',
            'jenis'         => ['required', Rule::in(['Internal', 'Customer', 'Vendor'])],
            'mitra_id'      => 'nullable|exists:partners,id',
            'from'          => 'nullable|string|max:255',
            'to'            => 'nullable|string|max:255',

            // Multi-file
            'attachments.*'             => ['file', 'max:10240'], // 10MB/file
            'attachment_names'          => ['array'],
            'attachment_names.*'        => ['nullable', 'string', 'max:255'],
            'attachment_descriptions'   => ['array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:500'],
        ]);

        // Aturan bisnis eksisting
        if ($request->jenis === 'Internal') $validated['mitra_id'] = 1;

        return DB::transaction(function () use ($request, $validated) {
            $activity = Activity::create($validated);

            // Simpan lampiran baru
            $files = $request->file('attachments', []);
            $names = $request->input('attachment_names', []);
            $descs = $request->input('attachment_descriptions', []);

            foreach ($files as $i => $file) {
                if (!$file) continue;

                $path = $file->store('attachments/activities/' . $activity->id, 'public');
                $displayName = $names[$i] ?? $file->getClientOriginalName();
                $desc = $descs[$i] ?? null;

                $activity->attachments()->create([
                    'name'        => $displayName,
                    'description' => $desc,
                    'file_path'   => $path,
                    'mime'        => $file->getClientMimeType(),
                    'size'        => $file->getSize(),
                ]);
            }

            return response()->json([
                'message' => 'Activity created successfully',
                'data' => $activity->load(['project', 'mitra', 'attachments'])->toArray(),
            ], 201);
        });
    }

    public function show(Activity $activity)
    {
        try {
            $activity->load(['project', 'mitra', 'attachments']);

            return response()->json([
                'message' => 'Activity retrieved successfully',
                'data' => $activity->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing activity: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'required|string',
            'project_id'    => 'required|exists:projects,id',
            'kategori'      => ['required', Rule::in([
                'Expense Report', 'Invoice', 'Purchase Order', 'Payment', 'Quotation',
                'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar', 'Kontrak'
            ])],
            'activity_date' => 'required|date',
            'jenis'         => ['required', Rule::in(['Internal', 'Customer', 'Vendor'])],
            'mitra_id'      => 'nullable|exists:partners,id',
            'from'          => 'nullable|string|max:255',
            'to'            => 'nullable|string|max:255',

            // Multi-file
            'attachments.*'             => ['file', 'max:10240'],
            'attachment_names'          => ['array'],
            'attachment_names.*'        => ['nullable', 'string', 'max:255'],
            'attachment_descriptions'   => ['array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:500'],
            'removed_existing_ids'      => ['array'],
            'removed_existing_ids.*'    => ['integer', 'exists:activity_attachments,id'],
        ]);

        if ($request->jenis === 'Internal') $validated['mitra_id'] = 1;

        return DB::transaction(function () use ($request, $activity, $validated) {
            // Hapus lampiran lama yang dipilih
            $removedIds = $request->input('removed_existing_ids', []);
            if (!empty($removedIds)) {
                $toDelete = ActivityAttachment::whereIn('id', $removedIds)
                    ->where('activity_id', $activity->id)
                    ->get();

                foreach ($toDelete as $att) {
                    if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                        Storage::disk('public')->delete($att->file_path);
                    }
                    $att->delete();
                }
            }

            $activity->update($validated);

            // Simpan lampiran baru
            $files = $request->file('attachments', []);
            $names = $request->input('attachment_names', []);
            $descs = $request->input('attachment_descriptions', []);

            foreach ($files as $i => $file) {
                if (!$file) continue;

                $path = $file->store('attachments/activities/' . $activity->id, 'public');
                $displayName = $names[$i] ?? $file->getClientOriginalName();
                $desc = $descs[$i] ?? null;

                $activity->attachments()->create([
                    'name'        => $displayName,
                    'description' => $desc,
                    'file_path'   => $path,
                    'mime'        => $file->getClientMimeType(),
                    'size'        => $file->getSize(),
                ]);
            }

            return response()->json([
                'message' => 'Activity updated successfully',
                'data' => $activity->load(['project', 'mitra', 'attachments'])->toArray(),
            ]);
        });
    }

    public function destroy(Activity $activity)
    {
        // Hapus semua file fisik sebelum delete record
        foreach ($activity->attachments as $att) {
            if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
        }
        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully'
        ]);
    }

    public function getFormDependencies()
    {
        $projects  = Project::all(['id', 'name', 'mitra_id']);
        $customers = Mitra::where('is_customer', true)->get(['id', 'nama']);
        $vendors   = Mitra::where('is_vendor', true)->get(['id', 'nama']);

        return response()->json([
            'projects'      => $projects,
            'customers'     => $customers,
            'vendors'       => $vendors,
            'kategori_list' => [
                'Expense Report', 'Invoice', 'Purchase Order', 'Payment', 'Quotation',
                'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar', 'Kontrak'
            ],
            'jenis_list'    => ['Internal', 'Customer', 'Vendor']
        ]);
    }
}
