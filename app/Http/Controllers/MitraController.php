<?php

namespace App\Http\Controllers;

use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class MitraController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Mitra::query();

        // Filter kategori
        if ($request->filled('kategori')) {
            $kategori = $request->kategori;
            // Pastikan kategori yang dikirim dari frontend valid
            if (in_array($kategori, ['pribadi', 'perusahaan', 'customer', 'vendor'])) {
                $query->where('is_' . $kategori, true);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('alamat', 'like', "%$search%")
                  ->orWhere('website', 'like', "%$search%");
            });
        }

        $mitras = $query->paginate(10); // Hapus withQueryString()

        return response()->json([
            'message' => 'Mitra retrieved successfully',
            'data' => $mitras->items(),
            'pagination' => [
                'total' => $mitras->total(),
                'per_page' => $mitras->perPage(),
                'current_page' => $mitras->currentPage(),
                'last_page' => $mitras->lastPage(),
                'from' => $mitras->firstItem(),
                'to' => $mitras->lastItem(),
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
            'nama' => 'required|string|max:255',
            'is_pribadi' => 'nullable|boolean',
            'is_perusahaan' => 'nullable|boolean',
            'is_customer' => 'nullable|boolean',
            'is_vendor' => 'nullable|boolean',
            'alamat' => 'required|string',
            'website' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:partners,email', // Asumsi email unik di tabel partners
            'kontak_1' => 'required|string|max:255',
            'kontak_1_nama' => 'required|string|max:255',
            'kontak_1_jabatan' => 'required|string|max:255',
            'kontak_2_nama' => 'nullable|string|max:255',
            'kontak_2' => 'nullable|string|max:255',
            'kontak_2_jabatan' => 'nullable|string|max:255',
        ]);

        if (!($request->has('is_pribadi') || $request->has('is_perusahaan') || $request->has('is_customer') || $request->has('is_vendor'))) {
            // Mengembalikan error validasi dalam format JSON
            return response()->json(['message' => 'Minimal satu kategori mitra wajib dipilih.'], 422); // 422 Unprocessable Entity
        }

        $validated['is_pribadi'] = $request->boolean('is_pribadi'); // Menggunakan boolean() helper
        $validated['is_perusahaan'] = $request->boolean('is_perusahaan');
        $validated['is_customer'] = $request->boolean('is_customer');
        $validated['is_vendor'] = $request->boolean('is_vendor');

        $mitra = Mitra::create($validated);

        return response()->json([
            'message' => 'Mitra created successfully',
            'data' => $mitra,
        ], 201);
    }

    /**
     * Display the specified resource.
     * @param  \App\Models\Mitra  $mitra
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Mitra $mitra)
    {
        return response()->json([
            'message' => 'Mitra retrieved successfully',
            'data' => $mitra,
        ]);
    }

    // Metode 'edit' tidak lagi diperlukan untuk API

    /**
     * Update the specified resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Mitra  $mitra
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Mitra $mitra)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'is_pribadi' => 'nullable|boolean',
            'is_perusahaan' => 'nullable|boolean',
            'is_customer' => 'nullable|boolean',
            'is_vendor' => 'nullable|boolean',
            'alamat' => 'required|string',
            'website' => 'nullable|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('partners', 'email')->ignore($mitra->id)], // Pastikan email unik kecuali untuk dirinya sendiri
            'kontak_1' => 'required|string|max:255',
            'kontak_1_nama' => 'required|string|max:255',
            'kontak_1_jabatan' => 'required|string|max:255',
            'kontak_2_nama' => 'nullable|string|max:255',
            'kontak_2' => 'nullable|string|max:255',
            'kontak_2_jabatan' => 'nullable|string|max:255',
        ]);

        if (!($request->has('is_pribadi') || $request->has('is_perusahaan') || $request->has('is_customer') || $request->has('is_vendor'))) {
            return response()->json(['message' => 'Minimal satu kategori mitra wajib dipilih.'], 422);
        }

        $validated['is_pribadi'] = $request->boolean('is_pribadi');
        $validated['is_perusahaan'] = $request->boolean('is_perusahaan');
        $validated['is_customer'] = $request->boolean('is_customer');
        $validated['is_vendor'] = $request->boolean('is_vendor');

        $mitra->update($validated);

        return response()->json([
            'message' => 'Mitra updated successfully',
            'data' => $mitra,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param  \App\Models\Mitra  $mitra
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Mitra $mitra)
    {
        $mitra->delete();
        return response()->json(['message' => 'Mitra deleted successfully'], 204);
    }

    // Endpoint tambahan untuk mendapatkan daftar customer dan vendor (jika diperlukan untuk dropdown di frontend)
    public function getCustomers()
    {
        $customers = Mitra::where('is_customer', true)->get(['id', 'nama']);
        Log::info('Fetched customers:', ['count' => $customers->count(), 'data' => $customers->toArray()]);
        return response()->json(['data' => $customers]);
    }

    public function getVendors()
    {
        $vendors = Mitra::where('is_vendor', true)->get(['id', 'nama']);
        Log::info('Fetched vendors:', ['count' => $vendors->count(), 'data' => $vendors->toArray()]);
        return response()->json(['data' => $vendors]);
    }
}