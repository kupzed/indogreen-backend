<?php

namespace App\Http\Controllers;

use App\Models\Mitra;
use App\Http\Requests\StoreMitraRequest;
use App\Http\Requests\UpdateMitraRequest;
use App\Http\Resources\MitraResource;
use App\Services\MitraService;
use Illuminate\Http\Request;

class MitraController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'kategori', 'date_from', 'date_to']);
        $perPage = $request->integer('per_page', 10);
        $sortBy = $request->input('sort_by', 'created');
        $sortDir = $request->input('sort_dir', 'desc');

        $mitras = $this->mitraService->getMitras($filters, $perPage, $sortBy, $sortDir);

        return MitraResource::collection($mitras)->additional([
            'form_dependencies' => $this->getFormDependencies(),
            'message' => 'Mitra retrieved successfully'
        ]);
    }

    public function store(StoreMitraRequest $request)
    {
        $mitra = $this->mitraService->createMitra($request->validated());

        return (new MitraResource($mitra))
            ->additional(['message' => 'Mitra created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Mitra $mitra)
    {
        return (new MitraResource($mitra))->additional([
            'form_dependencies' => $this->getFormDependencies(),
            'message' => 'Mitra retrieved successfully'
        ]);
    }

    public function update(UpdateMitraRequest $request, Mitra $mitra)
    {
        $mitra = $this->mitraService->updateMitra($mitra, $request->validated());

        return (new MitraResource($mitra))->additional([
            'message' => 'Mitra updated successfully'
        ]);
    }

    public function destroy(Mitra $mitra)
    {
        $this->mitraService->deleteMitra($mitra);
        return response()->json(['message' => 'Mitra deleted successfully'], 204);
    }

    private function getFormDependencies(): array
    {
        return [
            'kategori_options' => [
                ['value' => 'pribadi', 'label' => 'Pribadi'],
                ['value' => 'perusahaan', 'label' => 'Perusahaan'],
                ['value' => 'customer', 'label' => 'Customer'],
                ['value' => 'vendor', 'label' => 'Vendor'],
            ]
        ];
    }

    public function __construct(protected MitraService $mitraService)
    {
        $this->middleware('permission:mitra-view')->only(['index', 'show']);
        $this->middleware('permission:mitra-create')->only(['store']);
        $this->middleware('permission:mitra-update')->only(['update']);
        $this->middleware('permission:mitra-delete')->only(['destroy']);
    }
}