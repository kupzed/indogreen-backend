<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Mitra;

class ProjectService
{
    /**
     * Create a new project
     *
     * @param array $data
     * @return Project
     */
    public function createProject(array $data): Project
    {
        return Project::create($data);
    }

    /**
     * Update an existing project
     *
     * @param Project $project
     * @param array $data
     * @return Project
     */
    public function updateProject(Project $project, array $data): Project
    {
        $project->update($data);
        return $project;
    }

    /**
     * Delete a project
     *
     * @param Project $project
     * @return void
     */
    public function deleteProject(Project $project): void
    {
        $project->delete();
    }

    /**
     * Get paginated projects with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedProjects(array $filters, int $perPage)
    {
        return Project::with('mitra')
            ->filter($filters)
            ->paginate($perPage);
    }

    /**
     * Get project detail with relations
     *
     * @param Project $project
     * @return Project
     */
    public function getProjectDetail(Project $project): Project
    {
        return $project->load('mitra');
    }

    /**
     * Get form dependencies for project
     *
     * @return array
     */
    public function getFormDependencies(): array
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
}
