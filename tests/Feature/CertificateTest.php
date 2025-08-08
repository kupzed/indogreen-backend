<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Certificate;
use App\Models\Project;
use App\Models\BarangCertificate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_certificate_model()
    {
        $project = Project::factory()->create();
        $barangCertificate = BarangCertificate::factory()->create();

        $certificate = Certificate::create([
            'name' => 'Test Certificate',
            'no_certificate' => 'CERT-TEST-001',
            'project_id' => $project->id,
            'barang_certificate_id' => $barangCertificate->id,
            'status' => 'Aktif',
            'date_of_issue' => '2024-01-01',
            'date_of_expired' => '2027-01-01',
            'attachment' => 'test.pdf',
        ]);

        $this->assertDatabaseHas('certificates', [
            'name' => 'Test Certificate',
            'no_certificate' => 'CERT-TEST-001',
        ]);

        $this->assertEquals('Test Certificate', $certificate->name);
        $this->assertEquals('CERT-TEST-001', $certificate->no_certificate);
    }

    public function test_certificate_has_relationships()
    {
        $project = Project::factory()->create();
        $barangCertificate = BarangCertificate::factory()->create();
        
        $certificate = Certificate::create([
            'name' => 'Test Certificate',
            'no_certificate' => 'CERT-TEST-002',
            'project_id' => $project->id,
            'barang_certificate_id' => $barangCertificate->id,
            'status' => 'Aktif',
            'date_of_issue' => '2024-01-01',
            'date_of_expired' => '2027-01-01',
        ]);

        $this->assertInstanceOf(Project::class, $certificate->project);
        $this->assertInstanceOf(BarangCertificate::class, $certificate->barangCertificate);
    }
}
