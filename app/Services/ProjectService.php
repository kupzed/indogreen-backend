<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Mitra;

class ProjectService
{
    public function createProject(array $data): Project
    {
        return Project::create($data);
    }

    public function updateProject(Project $project, array $data): Project
    {
        $project->update($data);
        return $project;
    }

    public function deleteProject(Project $project): void
    {
        $project->delete();
    }

    public function getPaginatedProjects(array $filters, int $perPage)
    {
        return Project::with('mitra')
            ->filter($filters)
            ->paginate($perPage);
    }

    public function getProjectDetail(Project $project): Project
    {
        return $project->load('mitra');
    }

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
