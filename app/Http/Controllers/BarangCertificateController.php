<?php

namespace App\Http\Controllers;

use App\Models\BarangCertificate;
use App\Http\Requests\BarangCertificateRequest;
use App\Http\Resources\BarangCertificateResource;
use App\Services\BarangCertificateService;
use Illuminate\Http\Request;

class BarangCertificateController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $filters = $request->all();

        $barangCertificates = $this->barangCertificateService->getPaginatedBarangCertificates($filters, $perPage);

        return BarangCertificateResource::collection($barangCertificates)->additional([
            'message' => 'Barang certificates retrieved successfully',
            'form_dependencies' => $this->barangCertificateService->getFormDependencies()
        ]);
    }

    public function store(BarangCertificateRequest $request)
    {
        $validated = $request->validated();

        $barangCertificate = $this->barangCertificateService->createBarangCertificate($validated);

        return response()->json([
            'message' => 'Barang certificate created successfully',
            'data' => new BarangCertificateResource($barangCertificate->load('mitra'))
        ], 201);
    }

    public function show(BarangCertificate $barangCertificate)
    {
        $bcDetail = $this->barangCertificateService->getBarangCertificateDetail($barangCertificate);

        return (new BarangCertificateResource($bcDetail))->additional([
            'message' => 'Barang certificate retrieved successfully',
            'form_dependencies' => $this->barangCertificateService->getFormDependencies()
        ]);
    }

    public function update(BarangCertificateRequest $request, BarangCertificate $barangCertificate)
    {
        $validated = $request->validated();

        $barangCertificate = $this->barangCertificateService->updateBarangCertificate($barangCertificate, $validated);

        return response()->json([
            'message' => 'Barang certificate updated successfully',
            'data' => new BarangCertificateResource($barangCertificate->load('mitra'))
        ]);
    }

    public function destroy(BarangCertificate $barangCertificate)
    {
        $this->barangCertificateService->deleteBarangCertificate($barangCertificate);

        return response()->json([
            'message' => 'Barang certificate deleted successfully'
        ]);
    }

    public function __construct(protected BarangCertificateService $barangCertificateService)
    {
        $this->middleware('permission:bc-view')->only(['index', 'show']);
        $this->middleware('permission:bc-create')->only(['store']);
        $this->middleware('permission:bc-update')->only(['update']);
        $this->middleware('permission:bc-delete')->only(['destroy']);
    }
}
