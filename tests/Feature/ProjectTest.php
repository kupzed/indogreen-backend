<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Mitra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $mitra;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user for authentication
        $this->user = User::factory()->create();
        
        // Create a mitra for testing
        $this->mitra = Mitra::factory()->create([
            'is_customer' => true
        ]);
    }

    public function test_can_get_all_projects()
    {
        Project::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                        'start_date',
                        'finish_date',
                        'mitra_id',
                        'kategori',
                        'lokasi',
                        'no_po',
                        'no_so',
                        'is_cert_projects',
                        'cert_projects_label',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'pagination'
            ]);
    }

    public function test_can_create_project()
    {
        $projectData = [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'Ongoing',
            'start_date' => '2024-01-01',
            'finish_date' => '2024-06-01',
            'mitra_id' => $this->mitra->id,
            'kategori' => 'PLTS Hybrid',
            'lokasi' => 'Jakarta',
            'no_po' => 'PO-001',
            'no_so' => 'SO-001',
            'is_cert_projects' => true
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/projects', $projectData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Project created successfully',
                'data' => [
                    'name' => 'Test Project',
                    'is_cert_projects' => true,
                    'cert_projects_label' => 'Certificate Project'
                ]
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'is_cert_projects' => true
        ]);
    }

    public function test_can_update_project()
    {
        $project = Project::factory()->create([
            'is_cert_projects' => false
        ]);

        $updateData = [
            'name' => 'Updated Project',
            'description' => 'Updated Description',
            'status' => 'Complete',
            'start_date' => '2024-01-01',
            'finish_date' => '2024-06-01',
            'mitra_id' => $this->mitra->id,
            'kategori' => 'PLTS Ongrid',
            'lokasi' => 'Bandung',
            'no_po' => 'PO-002',
            'no_so' => 'SO-002',
            'is_cert_projects' => true
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Project updated successfully',
                'data' => [
                    'name' => 'Updated Project',
                    'is_cert_projects' => true,
                    'cert_projects_label' => 'Certificate Project'
                ]
            ]);
    }

    public function test_can_toggle_cert_project_status()
    {
        $project = Project::factory()->create([
            'is_cert_projects' => false
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/projects/{$project->id}/toggle-cert");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Certificate project status toggled successfully',
                'data' => [
                    'is_cert_projects' => true,
                    'cert_projects_label' => 'Certificate Project'
                ]
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_cert_projects' => true
        ]);
    }

    public function test_can_get_certificate_projects_only()
    {
        // Create projects with different cert status
        Project::factory()->create(['is_cert_projects' => true]);
        Project::factory()->create(['is_cert_projects' => true]);
        Project::factory()->create(['is_cert_projects' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/projects/certificate/list');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        foreach ($data as $project) {
            $this->assertTrue($project['is_cert_projects']);
            $this->assertEquals('Certificate Project', $project['cert_projects_label']);
        }
    }

    public function test_can_filter_projects_by_cert_status()
    {
        Project::factory()->create(['is_cert_projects' => true]);
        Project::factory()->create(['is_cert_projects' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/projects?is_cert_projects=true');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_cert_projects']);
    }

    public function test_project_model_scopes_work()
    {
        Project::factory()->create(['is_cert_projects' => true]);
        Project::factory()->create(['is_cert_projects' => false]);

        $certProjects = Project::certProjects()->get();
        $nonCertProjects = Project::nonCertProjects()->get();

        $this->assertCount(1, $certProjects);
        $this->assertCount(1, $nonCertProjects);
        $this->assertTrue($certProjects->first()->is_cert_projects);
        $this->assertFalse($nonCertProjects->first()->is_cert_projects);
    }

    public function test_project_model_helper_methods_work()
    {
        $project = Project::factory()->create(['is_cert_projects' => false]);

        $this->assertFalse($project->isCertProject());

        $project->toggleCertProject();
        $this->assertTrue($project->isCertProject());
    }

    public function test_validation_works_for_cert_project_field()
    {
        $invalidData = [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'Ongoing',
            'start_date' => '2024-01-01',
            'mitra_id' => $this->mitra->id,
            'kategori' => 'PLTS Hybrid',
            'is_cert_projects' => 'invalid_value' // Should be boolean
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/projects', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_cert_projects']);
    }
}
