<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Mitra;
use App\Models\Activity;
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
        // Filter kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        // Filter customer (asumsi customer_id merujuk ke mitra_id yang is_customer=true)
        if ($request->filled('customer_id')) {
            $query->where('mitra_id', $request->customer_id);
        }
        // Filter Date Range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('start_date', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhere('lokasi', 'like', "%$search%")
                  ->orWhere('no_po', 'like', "%$search%")
                  ->orWhere('no_so', 'like', "%$search%")
                  ->orWhereHas('mitra', function($q2) use ($search) {
                      $q2->where('nama', 'like', "%$search%");
                  });
            });
        }
        
        // Sorting
        if ($request->filled('sort')) {
            $sortOrder = $request->get('sort', 'desc'); // default desc (terbaru)
            if ($sortOrder === 'asc') {
                $query->orderBy('created_at', 'asc'); // terlama
            } else {
                $query->orderBy('created_at', 'desc'); // terbaru
            }
        } else {
            $query->orderBy('created_at', 'desc'); // default terbaru
        }
        
        $projects = $query->paginate(10);

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
            'mitra_id' => 'required|exists:partners,id',
            'kategori' => ['required', Rule::in([
                'PLTS Hybrid', 
                'PLTS Ongrid', 
                'PLTS Offgrid', 
                'PJUTS All In One', 
                'PJUTS Two In One', 
                'PJUTS Konvensional'
            ])],
            'lokasi' => 'nullable|string',
            'no_po' => 'nullable|string',
            'no_so' => 'nullable|string',
        ]);

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
        $project->load('mitra');

        // Query aktivitas berdasarkan project_id
        $query = Activity::with(['project', 'mitra'])
            ->where('project_id', $project->id);

        // Filter Jenis
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        // Filter Kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        // Filter Date Range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('activity_date', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->where('activity_date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('activity_date', '<=', $request->date_to);
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

        // Daftar kategori untuk filter
        $kategoriList = [
            'Expense Report', 'Invoice', 'Purchase Order', 'Payment', 'Quotation',
            'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar'
        ];

        // Daftar kategori project
        $projectKategoriList = [
            'PLTS Hybrid', 
            'PLTS Ongrid', 
            'PLTS Offgrid', 
            'PJUTS All In One', 
            'PJUTS Two In One', 
            'PJUTS Konvensional'
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
                'project_kategori_list' => $projectKategoriList,
            ]
        ]);
    }

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
            'mitra_id' => 'required|exists:partners,id',
            'kategori' => ['required', Rule::in([
                'PLTS Hybrid', 
                'PLTS Ongrid', 
                'PLTS Offgrid', 
                'PJUTS All In One', 
                'PJUTS Two In One', 
                'PJUTS Konvensional'
            ])],
            'lokasi' => 'nullable|string',
            'no_po' => 'nullable|string',
            'no_so' => 'nullable|string',
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

    // Endpoint tambahan untuk mendapatkan daftar customer/mitra
    public function getCustomersForProject()
    {
        $customers = Mitra::where('is_customer', true)->get(['id', 'nama']);
        return response()->json(['data' => $customers]);
    }
}