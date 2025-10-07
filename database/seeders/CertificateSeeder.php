<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Project;
use App\Models\BarangCertificate;
use Illuminate\Database\Seeder;

class CertificateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only get projects that are certificate projects
        $projects = Project::where('is_cert_projects', true)->get();
        $barangCertificates = BarangCertificate::all();

        if ($projects->isEmpty()) {
            $this->command->warn('No certificate projects found. Please run ProjectSeeder first and ensure some projects have is_cert_projects set to true.');
            return;
        }

        if ($barangCertificates->isEmpty()) {
            $this->command->warn('No barang certificates found. Please run BarangCertificateSeeder first.');
            return;
        }

        // Create some sample certificates with proper relationships
        $certificates = [
            [
                'name' => 'ISO 9001 Quality Management System',
                'no_certificate' => 'CERT-001',
                'project_id' => $projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id,
                'barang_certificate_id' => $this->getBarangCertificateForProject($projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id),
                'status' => 'Aktif',
                'date_of_issue' => '2024-01-15',
                'date_of_expired' => '2027-01-15',
            ],
            [
                'name' => 'ISO 14001 Environmental Management',
                'no_certificate' => 'CERT-002',
                'project_id' => $projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id,
                'barang_certificate_id' => $this->getBarangCertificateForProject($projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id),
                'status' => 'Aktif',
                'date_of_issue' => '2024-02-20',
                'date_of_expired' => '2027-02-20',
            ],
            [
                'name' => 'OHSAS 18001 Safety Management',
                'no_certificate' => 'CERT-003',
                'project_id' => $projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id,
                'barang_certificate_id' => $this->getBarangCertificateForProject($projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id),
                'status' => 'Belum',
                'date_of_issue' => '2024-03-10',
                'date_of_expired' => '2027-03-10',
            ],
            [
                'name' => 'Solar Panel TUV Certification',
                'no_certificate' => 'CERT-004',
                'project_id' => $projects->where('name', 'LIKE', '%PLTS%')->first()?->id ?? $projects->first()->id,
                'barang_certificate_id' => $this->getBarangCertificateForProject($projects->where('name', 'LIKE', '%PLTS%')->first()?->id ?? $projects->first()->id),
                'status' => 'Tidak Aktif',
                'date_of_issue' => '2023-06-15',
                'date_of_expired' => '2026-06-15',
            ],
            [
                'name' => 'Inverter IEC Certification',
                'no_certificate' => 'CERT-005',
                'project_id' => $projects->where('name', 'LIKE', '%PLTS%')->first()?->id ?? $projects->first()->id,
                'barang_certificate_id' => $this->getBarangCertificateForProject($projects->where('name', 'LIKE', '%PLTS%')->first()?->id ?? $projects->first()->id),
                'status' => 'Aktif',
                'date_of_issue' => '2024-04-05',
                'date_of_expired' => '2027-04-05',
            ],
        ];

        foreach ($certificates as $certificate) {
            Certificate::create($certificate);
        }

        // Create additional random certificates only for certificate projects
        // Calculate how many certificates to create based on number of certificate projects
        $certificateProjectsCount = $projects->count();
        $certificatesToCreate = min(100, $certificateProjectsCount * 5); // Max 5 certificates per project or 100 total
        
        Certificate::factory($certificatesToCreate)->create();

        $this->command->info("Certificates seeded successfully for {$certificateProjectsCount} certificate projects.");
    }

    /**
     * Get a barang certificate that belongs to the same mitra as the project
     */
    private function getBarangCertificateForProject($projectId)
    {
        if (!$projectId) return null;
        
        $project = Project::find($projectId);
        if (!$project || !$project->mitra_id) return null;
        
        $barangCertificate = BarangCertificate::where('mitra_id', $project->mitra_id)->inRandomOrder()->first();
        
        // If no barang certificate found for the project's mitra, create one
        if (!$barangCertificate) {
            $barangCertificate = BarangCertificate::factory()->create([
                'mitra_id' => $project->mitra_id
            ]);
        }
        
        return $barangCertificate->id;
    }
}
