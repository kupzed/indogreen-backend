<?php

namespace App\Http\Controllers;

use App\Models\BarangCertificate;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangCertificateController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = BarangCertificate::with('mitra');

        // Filter by mitra_id
        if ($request->filled('mitra_id')) {
            $query->where('mitra_id', $request->mitra_id);
        }

        // Search
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

        $barangCertificates = $query->paginate(10);

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

    /**
     * Store a newly created resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Display the specified resource.
     * @param  \App\Models\BarangCertificate  $barangCertificate
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(BarangCertificate $barangCertificate)
    {
        return response()->json([
            'message' => 'Barang certificate retrieved successfully',
            'data' => $barangCertificate->load(['mitra', 'certificates'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BarangCertificate  $barangCertificate
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Remove the specified resource from storage.
     * @param  \App\Models\BarangCertificate  $barangCertificate
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(BarangCertificate $barangCertificate)
    {
        $barangCertificate->delete();

        return response()->json([
            'message' => 'Barang certificate deleted successfully'
        ]);
    }

    /**
     * Get form dependencies for barang certificates
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFormDependencies()
    {
        $mitras = Mitra::select('id', 'nama')->get();

        return response()->json([
            'mitras' => $mitras
        ]);
    }
}
