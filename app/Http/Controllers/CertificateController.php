<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateAttachment;
use App\Models\Project;
use App\Models\BarangCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::with(['project', 'barangCertificate', 'attachments']);

        if ($request->filled('status'))                $query->where('status', $request->status);
        if ($request->filled('project_id'))            $query->where('project_id', $request->project_id);
        if ($request->filled('barang_certificate_id')) $query->where('barang_certificate_id', $request->barang_certificate_id);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date_of_issue', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->where('date_of_issue', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('date_of_issue', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('no_certificate', 'like', "%$search%")
                    ->orWhereHas('project', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('barangCertificate', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%$search%");
                    });
            });
        }

        $perPage = $request->integer('per_page', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) $perPage = 10;

        $certificates = $query->paginate($perPage);
        $items = collect($certificates->items())->map->toArray()->all();

        return response()->json([
            'message' => 'Certificates retrieved successfully',
            'data' => $items,
            'pagination' => [
                'total' => $certificates->total(),
                'per_page' => $certificates->perPage(),
                'current_page' => $certificates->currentPage(),
                'last_page' => $certificates->lastPage(),
                'from' => $certificates->firstItem(),
                'to' => $certificates->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'no_certificate'        => 'required|string|max:30|unique:certificates,no_certificate',
            'project_id'            => 'required|exists:projects,id',
            'barang_certificate_id' => 'required|exists:barang_certificates,id',
            'status'                => ['required', Rule::in(['Belum', 'Tidak Aktif', 'Aktif'])],
            'date_of_issue'         => 'nullable|date',
            'date_of_expired'       => 'nullable|date|after:date_of_issue',

            // Multi-file
            'attachments.*'             => ['file', 'max:10240'],
            'attachment_names'          => ['array'],
            'attachment_names.*'        => ['nullable', 'string', 'max:255'],
            'attachment_descriptions'   => ['array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:500'],
        ]);

        if (($validated['date_of_issue'] ?? '') === '')   $validated['date_of_issue'] = null;
        if (($validated['date_of_expired'] ?? '') === '') $validated['date_of_expired'] = null;

        return DB::transaction(function () use ($request, $validated) {
            $certificate = Certificate::create($validated);

            // Simpan lampiran baru
            $files = $request->file('attachments', []);
            $names = $request->input('attachment_names', []);
            $descs = $request->input('attachment_descriptions', []);

            foreach ($files as $i => $file) {
                if (!$file) continue;

                $path = $file->store('attachments/certificates/' . $certificate->id, 'public');
                $displayName = $names[$i] ?? $file->getClientOriginalName();
                $desc = $descs[$i] ?? null;

                $certificate->attachments()->create([
                    'name'        => $displayName,
                    'description' => $desc,
                    'file_path'   => $path,
                    'mime'        => $file->getClientMimeType(),
                    'size'        => $file->getSize(),
                ]);
            }

            return response()->json([
                'message' => 'Certificate created successfully',
                'data' => $certificate->load(['project', 'barangCertificate', 'attachments'])->toArray()
            ], 201);
        });
    }

    public function show(Certificate $certificate)
    {
        return response()->json([
            'message' => 'Certificate retrieved successfully',
            'data' => $certificate->load(['project', 'barangCertificate', 'attachments'])->toArray()
        ]);
    }

    public function update(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'no_certificate'        => ['required', 'string', 'max:30', Rule::unique('certificates', 'no_certificate')->ignore($certificate->id)],
            'project_id'            => 'required|exists:projects,id',
            'barang_certificate_id' => 'required|exists:barang_certificates,id',
            'status'                => ['required', Rule::in(['Belum', 'Tidak Aktif', 'Aktif'])],
            'date_of_issue'         => 'nullable|date',
            'date_of_expired'       => 'nullable|date|after:date_of_issue',

            // Multi-file
            'attachments.*'             => ['file', 'max:10240'],
            'attachment_names'          => ['array'],
            'attachment_names.*'        => ['nullable', 'string', 'max:255'],
            'attachment_descriptions'   => ['array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:500'],
            'removed_existing_ids'      => ['array'],
            'removed_existing_ids.*'    => ['integer', 'exists:certificate_attachments,id'],
        ]);

        if (($validated['date_of_issue'] ?? '') === '')   $validated['date_of_issue'] = null;
        if (array_key_exists('date_of_expired', $validated) && $validated['date_of_expired'] === '') {
            $validated['date_of_expired'] = null;
        }

        return DB::transaction(function () use ($request, $certificate, $validated) {
            // Hapus lampiran lama yang dipilih
            $removedIds = $request->input('removed_existing_ids', []);
            if (!empty($removedIds)) {
                $toDelete = CertificateAttachment::whereIn('id', $removedIds)
                    ->where('certificate_id', $certificate->id)
                    ->get();

                foreach ($toDelete as $att) {
                    if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                        Storage::disk('public')->delete($att->file_path);
                    }
                    $att->delete();
                }
            }

            $certificate->update($validated);

            // Simpan lampiran baru
            $files = $request->file('attachments', []);
            $names = $request->input('attachment_names', []);
            $descs = $request->input('attachment_descriptions', []);

            foreach ($files as $i => $file) {
                if (!$file) continue;

                $path = $file->store('attachments/certificates/' . $certificate->id, 'public');
                $displayName = $names[$i] ?? $file->getClientOriginalName();
                $desc = $descs[$i] ?? null;

                $certificate->attachments()->create([
                    'name'        => $displayName,
                    'description' => $desc,
                    'file_path'   => $path,
                    'mime'        => $file->getClientMimeType(),
                    'size'        => $file->getSize(),
                ]);
            }

            return response()->json([
                'message' => 'Certificate updated successfully',
                'data' => $certificate->load(['project', 'barangCertificate', 'attachments'])->toArray()
            ]);
        });
    }

    public function destroy(Certificate $certificate)
    {
        foreach ($certificate->attachments as $att) {
            if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
        }
        $certificate->delete();

        return response()->json([
            'message' => 'Certificate deleted successfully'
        ]);
    }

    public function getFormDependencies()
    {
        $projects = Project::select('id', 'name')->get();
        $barangCertificates = BarangCertificate::select('id', 'name', 'no_seri')->get();

        return response()->json([
            'projects' => $projects,
            'barang_certificates' => $barangCertificates
        ]);
    }

    public function getBarangCertificatesByProject($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $barangCertificates = BarangCertificate::where('mitra_id', $project->mitra_id)
                ->select('id', 'name', 'no_seri')
                ->get();

            return response()->json([
                'message' => 'Barang certificates retrieved successfully',
                'data' => $barangCertificates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Project not found',
                'data' => []
            ], 404);
        }
    }
}
