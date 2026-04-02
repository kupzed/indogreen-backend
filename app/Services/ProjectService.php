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


}
