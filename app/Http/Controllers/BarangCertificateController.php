<?php

namespace App\Http\Controllers;

use App\Models\BarangCertificate;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangCertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = BarangCertificate::with('mitra');

        if ($request->filled('mitra_id')) {
            $query->where('mitra_id', $request->mitra_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('no_seri', 'like', "%$search%")
                  ->orWhereHas('mitra', function($q2) use ($search) {
                      $q2->where('nama', 'like', "%$search%");
                  });
            });
        }

        // Sorting (default created desc via id desc)
        $sortBy  = $request->input('sort_by', 'created');
        $sortDir = strtolower($request->input('sort_dir', 'desc'));
        $dir     = in_array($sortDir, ['asc','desc'], true) ? $sortDir : 'desc';

        switch ($sortBy) {
            case 'created':
            default:
                $query->orderBy('id', $dir);
                break;
        }

        $perPage = $request->integer('per_page', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $barangCertificates = $query->paginate($perPage);

        return response()->json([
            'message' => 'Barang certificates retrieved successfully',
            'data' => $barangCertificates->items(),
            'pagination' => [
                'total' => $barangCertificates->total(),
                'per_page' => $barangCertificates->perPage(),
                'current_page' => $barangCertificates->currentPage(),
                'last_page' => $barangCertificates->lastPage(),
                'from' => $barangCertificates->firstItem(),
                'to' => $barangCertificates->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'no_seri' => 'required|string|max:30|unique:barang_certificates,no_seri',
            'mitra_id' => 'required|exists:partners,id',
        ]);

        $barangCertificate = BarangCertificate::create($validated);

        return response()->json([
            'message' => 'Barang certificate created successfully',
            'data' => $barangCertificate->load('mitra')
        ], 201);
    }

    public function show(BarangCertificate $barangCertificate)
    {
        return response()->json([
            'message' => 'Barang certificate retrieved successfully',
            'data' => $barangCertificate->load(['mitra', 'certificates'])
        ]);
    }

    public function update(Request $request, BarangCertificate $barangCertificate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'no_seri' => ['required', 'string', 'max:30', Rule::unique('barang_certificates', 'no_seri')->ignore($barangCertificate->id)],
            'mitra_id' => 'required|exists:partners,id',
        ]);

        $barangCertificate->update($validated);

        return response()->json([
            'message' => 'Barang certificate updated successfully',
            'data' => $barangCertificate->load('mitra')
        ]);
    }

    public function destroy(BarangCertificate $barangCertificate)
    {
        $barangCertificate->delete();

        return response()->json([
            'message' => 'Barang certificate deleted successfully'
        ]);
    }

    public function getFormDependencies()
    {
        $mitras = Mitra::select('id', 'nama')->get();

        return response()->json([
            'mitras' => $mitras
        ]);
    }

    public function __construct()
    {
        // Read/list access
        $this->middleware('permission:bc-view')->only(['index', 'show', 'getFormDependencies']);

        // Create / update / delete
        $this->middleware('permission:bc-create')->only(['store']);
        $this->middleware('permission:bc-update')->only(['update']);
        $this->middleware('permission:bc-delete')->only(['destroy']);
    }
}
