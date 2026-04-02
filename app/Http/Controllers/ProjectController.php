<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Mitra;
use App\Http\Resources\ProjectResource;
use App\Http\Requests\ProjectRequest;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $projects = Project::with('mitra')
            ->filter($request->all())
            ->paginate($perPage);

        return ProjectResource::collection($projects)->additional([
            'message' => 'Projects retrieved successfully',
            'form_dependencies' => $this->getFormDependenciesArray()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param  \App\Http\Requests\ProjectRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ProjectRequest $request)
    {
        $validated = $request->validated();

        $project = $this->projectService->createProject($validated);

        return response()->json([
            'message' => 'Project created successfully',
            'data' => new ProjectResource($project),
        ], 201);
    }

    /**
     * Display the specified resource.
     * @param  \App\Models\Project  $project
     * @return \App\Http\Resources\ProjectResource
     */
    public function show(Project $project)
    {
        // Detail project + relasi dasar
        $project->load('mitra');

        return (new ProjectResource($project))->additional([
            'message' => 'Project details retrieved successfully',
            'form_dependencies' => $this->getFormDependenciesArray()
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param  \App\Http\Requests\ProjectRequest  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ProjectRequest $request, Project $project)
    {
        $validated = $request->validated();

        $project = $this->projectService->updateProject($project, $validated);

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Project $project)
    {
        $this->projectService->deleteProject($project);
        
        return response()->json([
            'message' => 'Project deleted successfully'
        ], 200);
    }

    /**
     * Toggle certificate project status
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleCertProject(Project $project)
    {
        $project = $this->projectService->toggleCertProject($project);

        return response()->json([
            'message' => 'Certificate project status toggled successfully',
            'data' => new ProjectResource($project),
        ]);
    }

    private function getFormDependenciesArray(): array
    {
        $customers = Mitra::where('is_customer', true)->get(['id', 'nama']);

        $projectStatusList = [
            'Ongoing',
            'Prospect',
            'Complete',
            'Cancel',
        ];

        $projectKategoriList = [
            'PLTS Hybrid', 'PLTS Ongrid', 'PLTS Offgrid',
            'PJUTS All In One', 'PJUTS Two In One', 'PJUTS Konvensional',
        ];

        return [
            'customers' => $customers,
            'project_status_list' => $projectStatusList,
            'project_kategori_list' => $projectKategoriList,
        ];
    }

    public function __construct(protected ProjectService $projectService)
    {
        // hak untuk melihat data project (list, detail, form dependencies)
        $this->middleware('permission:project-view')->only([
            'index', 'show'
        ]);
        // hak membuat project
        $this->middleware('permission:project-create')->only(['store']);
        // hak memperbarui project (termasuk toggle sertifikat)
        $this->middleware('permission:project-update')->only(['update', 'toggleCertProject']);
        // hak menghapus project
        $this->middleware('permission:project-delete')->only(['destroy']);
    }

}
