<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule; // Tambahkan ini untuk validasi unik jika diperlukan

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     * Mengembalikan daftar aktivitas dengan filter dan pencarian.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Activity::with(['project', 'mitra']); 

        // Filter Jenis
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        // Filter Kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhereHas('project', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
            });
        }

        $activities = $query->paginate(10); // Hapus withQueryString() karena ini untuk Blade views

        return response()->json([
            'message' => 'Activities retrieved successfully',
            'data' => $activities->items(), // Ambil hanya item data
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

    // Metode 'create' tidak lagi diperlukan untuk API karena SvelteKit akan menangani formulir pembuatan
    // Anda bisa membuat endpoint terpisah untuk mengambil daftar project, customer, mitra jika diperlukan oleh form SvelteKit

    /**
     * Store a newly created resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'project_id' => 'required|exists:projects,id',
            'kategori' => ['required', Rule::in([
                'Expense Report', 'Invoice', 'Purchase Order', 'Payment', 'Quotation',
                'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar'
            ])],
            'activity_date' => 'required|date',
            'attachment' => 'nullable|file|max:10240', // 10MB
            'jenis' => ['required', Rule::in(['Internal', 'Customer', 'Vendor'])],
            'mitra_id' => 'nullable|exists:partners,id', // Mitra ID bisa null untuk Internal
            'from' => 'nullable|string', // Tambahkan validasi from
            'to' => 'nullable|string',   // Tambahkan validasi to
        ]);

        // Logic pengisian customer_id/mitra_id
        if ($request->jenis === 'Internal') {
            $validated['mitra_id'] = 1; // Pastikan ID 1 adalah mitra 'Internal' yang valid
        } 
        // Logika untuk 'Vendor' dan 'Customer' sudah benar jika mitra_id dikirim dari frontend
        // Unset customer_id karena sudah digantikan oleh mitra_id
        if (isset($validated['customer_id'])) {
             unset($validated['customer_id']); 
        }
        
        // Hapus penanganan customer_id jika memang tidak digunakan lagi dan digantikan oleh mitra_id
        // $validated['customer_id'] = $request->input('customer_id'); // Jika masih ada, sesuaikan logic

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        $activity = Activity::create($validated);

        return response()->json([
            'message' => 'Activity created successfully',
            'data' => $activity->load(['project', 'mitra']), // Load relasi untuk respons
        ], 201); // 201 Created
    }

    /**
     * Display the specified resource.
     * Mengembalikan detail aktivitas tunggal.
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Activity $activity)
    {
        try {
            $activity->load(['project', 'mitra']); // Hapus 'customer' jika sudah digantikan oleh 'mitra'
            Log::info('Activity Data:', ['activity' => $activity->toArray()]); // Log tetap berguna untuk debugging

            return response()->json([
                'message' => 'Activity retrieved successfully',
                'data' => $activity,
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing activity: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve activity',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    // Metode 'edit' tidak lagi diperlukan untuk API

    /**
     * Update the specified resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'project_id' => 'required|exists:projects,id',
            'kategori' => ['required', Rule::in([
                'Expense Report', 'Invoice', 'Purchase Order', 'Payment', 'Quotation',
                'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar'
            ])],
            'activity_date' => 'required|date',
            'attachment' => 'nullable|file|max:10240', // 10MB
            'jenis' => ['required', Rule::in(['Internal', 'Customer', 'Vendor'])],
            'mitra_id' => 'nullable|exists:partners,id', // Mitra ID bisa null untuk Internal
            'from' => 'nullable|string', // Tambahkan validasi from
            'to' => 'nullable|string',   // Tambahkan validasi to
        ]);

        // Logic pengisian customer_id/mitra_id
        if ($request->jenis === 'Internal') {
            $validated['mitra_id'] = 1;
        } 
        if (isset($validated['customer_id'])) {
             unset($validated['customer_id']); 
        }

        if ($request->hasFile('attachment')) {
            // Hapus file lama jika ada
            if ($activity->attachment) {
                Storage::disk('public')->delete($activity->attachment);
            }
            $validated['attachment'] = $request->file('attachment')->store('attachments', 'public');
        } else if ($request->input('attachment_removed', false)) {
            // Jika frontend memberi sinyal attachment dihapus tanpa upload baru
            if ($activity->attachment) {
                Storage::disk('public')->delete($activity->attachment);
            }
            $validated['attachment'] = null;
        }


        $activity->update($validated);

        return response()->json([
            'message' => 'Activity updated successfully',
            'data' => $activity->load(['project', 'mitra']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Activity $activity)
    {
        if ($activity->attachment) {
            Storage::disk('public')->delete($activity->attachment);
        }
        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully'
        ], 204); // 204 No Content
    }
    
    // API endpoint tambahan untuk mendapatkan daftar proyek, customer, dan mitra
    public function getFormDependencies()
    {
        $projects = Project::all(['id', 'name', 'mitra_id']); // Tambahkan mitra_id agar frontend bisa tahu customer dari project
        $customers = Mitra::where('is_customer', true)->get(['id', 'nama']);
        $vendors = Mitra::where('is_vendor', true)->get(['id', 'nama']);

        return response()->json([
            'projects' => $projects,
            'customers' => $customers, // Jika masih pakai customer secara terpisah dari mitra
            'vendors' => $vendors, // Ini akan mencakup vendor dan internal jika ada
            'kategori_list' => [ // Untuk dropdown kategori di frontend
                'Expense Report', 'Invoice', 'Purchase Order', 'Payment', 'Quotation',
                'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar'
            ],
            'jenis_list' => ['Internal', 'Customer', 'Vendor'] // Untuk dropdown jenis
        ]);
    }
}