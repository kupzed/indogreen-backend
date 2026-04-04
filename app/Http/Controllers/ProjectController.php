<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Resources\ProjectResource;
use App\Http\Requests\ProjectRequest;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $projects = $this->projectService->getPaginatedProjects($request->all(), $perPage);

        return ProjectResource::collection($projects)->additional([
            'message' => 'Projects retrieved successfully',
            'form_dependencies' => $this->projectService->getFormDependencies()
        ]);
    }

    public function store(ProjectRequest $request)
    {
        $validated = $request->validated();

        $project = $this->projectService->createProject($validated);

        return response()->json([
            'message' => 'Project created successfully',
            'data' => new ProjectResource($project),
        ], 201);
    }

    public function show(Project $project)
    {
        $project = $this->projectService->getProjectDetail($project);

        return (new ProjectResource($project))->additional([
            'message' => 'Project details retrieved successfully',
            'form_dependencies' => $this->projectService->getFormDependencies()
        ]);
    }

    public function update(ProjectRequest $request, Project $project)
    {
        $validated = $request->validated();

        $project = $this->projectService->updateProject($project, $validated);

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($project),
        ]);
    }

    public function destroy(Project $project)
    {
        $this->projectService->deleteProject($project);
        
        return response()->json([
            'message' => 'Project deleted successfully'
        ], 200);
    }

    public function __construct(protected ProjectService $projectService)
    {
        $this->middleware('permission:project-view')->only(['index', 'show']);
        $this->middleware('permission:project-create')->only(['store']);
        $this->middleware('permission:project-update')->only(['update']);
        $this->middleware('permission:project-delete')->only(['destroy']);
    }
}
