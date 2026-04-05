<?php

namespace App\Http\Controllers;

use App\Models\Mitra;
use App\Http\Requests\MitraRequest;
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

        $mitras = $this->mitraService->getPaginatedMitras($filters, $perPage, $sortBy, $sortDir);

        return MitraResource::collection($mitras)->additional([
            'form_dependencies' => $this->mitraService->getFormDependencies(),
            'message' => 'Mitra retrieved successfully'
        ]);
    }

    public function store(MitraRequest $request)
    {
        $mitra = $this->mitraService->createMitra($request->validated());

        return (new MitraResource($mitra))
            ->additional(['message' => 'Mitra created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Mitra $mitra)
    {
        $mitraDetail = $this->mitraService->getMitraDetail($mitra);

        return (new MitraResource($mitraDetail))->additional([
            'form_dependencies' => $this->mitraService->getFormDependencies(),
            'message' => 'Mitra retrieved successfully'
        ]);
    }

    public function update(MitraRequest $request, Mitra $mitra)
    {
        $mitra = $this->mitraService->updateMitra($mitra, $request->validated());

        return (new MitraResource($mitra))->additional([
            'message' => 'Mitra updated successfully'
        ]);
    }

    public function destroy(Mitra $mitra)
    {
        $this->mitraService->deleteMitra($mitra);

        return response()->json([
            'message' => 'Mitra deleted successfully'
        ]);
    }


    public function __construct(protected MitraService $mitraService)
    {
        $this->middleware('permission:mitra-view')->only(['index', 'show']);
        $this->middleware('permission:mitra-create')->only(['store']);
        $this->middleware('permission:mitra-update')->only(['update']);
        $this->middleware('permission:mitra-delete')->only(['destroy']);
    }
}