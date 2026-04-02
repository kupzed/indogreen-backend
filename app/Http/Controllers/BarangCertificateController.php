<?php

namespace App\Http\Controllers;

use App\Models\BarangCertificate;
use App\Models\Mitra;
use App\Http\Requests\StoreBarangCertificateRequest;
use App\Http\Requests\UpdateBarangCertificateRequest;
use App\Http\Resources\BarangCertificateResource;
use App\Services\BarangCertificateService;
use Illuminate\Http\Request;

class BarangCertificateController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $barangCertificates = BarangCertificate::with('mitra')
            ->filter($request->all())
            ->paginate($perPage);

        return BarangCertificateResource::collection($barangCertificates)->additional([
            'message' => 'Barang certificates retrieved successfully',
            'form_dependencies' => $this->getFormDependenciesArray()
        ]);
    }

    public function store(StoreBarangCertificateRequest $request)
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
        return (new BarangCertificateResource($barangCertificate->load(['mitra', 'certificates'])))->additional([
            'message' => 'Barang certificate retrieved successfully',
            'form_dependencies' => $this->getFormDependenciesArray()
        ]);
    }

    public function update(UpdateBarangCertificateRequest $request, BarangCertificate $barangCertificate)
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

    private function getFormDependenciesArray(): array
    {
        $mitras = Mitra::select('id', 'nama')->get();

        return [
            'mitras' => $mitras
        ];
    }

    public function __construct(protected BarangCertificateService $barangCertificateService)
    {
        // Read/list access
        $this->middleware('permission:bc-view')->only(['index', 'show']);

        // Create / update / delete
        $this->middleware('permission:bc-create')->only(['store']);
        $this->middleware('permission:bc-update')->only(['update']);
        $this->middleware('permission:bc-delete')->only(['destroy']);
    }
}
