<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Mitra; // Gunakan Mitra sebagai pengganti Customer
use App\Models\Activity; // Tambahkan ini jika belum ada
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Project::with('mitra');

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // Filter customer (asumsi customer_id merujuk ke mitra_id yang is_customer=true)
        if ($request->filled('customer_id')) {
            $query->where('mitra_id', $request->customer_id); // Ganti customer_id dengan mitra_id
        }
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhereHas('mitra', function($q2) use ($search) {
                      $q2->where('nama', 'like', "%$search%");
                  });
            });
        }
        $projects = $query->paginate(10); // Hapus withQueryString()

        return response()->json([
            'message' => 'Projects retrieved successfully',
            'data' => $projects->items(),
            'pagination' => [
                'total' => $projects->total(),
                'per_page' => $projects->perPage(),
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'from' => $projects->firstItem(),
                'to' => $projects->lastItem(),
            ]
        ]);
    }

    // Metode 'create' tidak lagi diperlukan untuk API

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
            'status' => ['required', Rule::in(['Ongoing', 'Prospect', 'Complete', 'Cancel'])],
            'start_date' => 'required|date',
            'finish_date' => 'nullable|date',
            'mitra_id' => 'required|exists:partners,id', // Harus merujuk ke partners, bukan customers
        ]);

        // Jika Anda memiliki 'customer_id' di Project model, pastikan divalidasi juga
        // $validated['customer_id'] = $request->input('customer_id'); // Jika masih ada

        $project = Project::create($validated);

        return response()->json([
            'message' => 'Project created successfully',
            'data' => $project,
        ], 201);
    }

    /**
     * Display the specified resource.
     * @param  \App\Models\Project  $project
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Project $project, Request $request)
    {
        $project->load('mitra'); // Memuat relasi mitra

        // Query aktivitas berdasarkan project_id
        $query = Activity::with(['project', 'mitra']) // Ganti 'customer' dengan 'mitra'
            ->where('project_id', $project->id);

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
        $activities = $query->paginate(10);

        // Daftar kategori untuk filter (bisa juga diambil dari config atau database)
        $kategoriList = [
            'Expense Report', 'Invoice', 'Purchase Order', 'Payment', 'Quotation',
            'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar'
        ];

        return response()->json([
            'message' => 'Project details retrieved successfully',
            'data' => [
                'project' => $project,
                'activities' => $activities->items(),
                'activity_pagination' => [
                    'total' => $activities->total(),
                    'per_page' => $activities->perPage(),
                    'current_page' => $activities->currentPage(),
                    'last_page' => $activities->lastPage(),
                    'from' => $activities->firstItem(),
                    'to' => $activities->lastItem(),
                ],
                'kategori_list' => $kategoriList,
                // Anda mungkin perlu endpoint terpisah untuk mengambil daftar mitra/customer/vendor
            ]
        ]);
    }

    // Metode 'edit' tidak lagi diperlukan untuk API

    /**
     * Update the specified resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => ['required', Rule::in(['Ongoing', 'Prospect', 'Complete', 'Cancel'])],
            'start_date' => 'required|date',
            'finish_date' => 'nullable|date',
            'mitra_id' => 'required|exists:partners,id', // Mitra ID
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => $project,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Project deleted successfully'], 204);
    }

    // Endpoint tambahan untuk mendapatkan daftar customer/mitra (jika diperlukan untuk dropdown di frontend)
    public function getCustomersForProject()
    {
        $customers = Mitra::where('is_customer', true)->get(['id', 'nama']);
        return response()->json(['data' => $customers]);
    }
}