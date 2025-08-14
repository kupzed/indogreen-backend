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
        $projects = Project::all();
        $barangCertificates = BarangCertificate::all();

        if ($projects->isEmpty()) {
            $this->command->warn('No projects found. Please run ProjectSeeder first.');
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
                'attachment' => 'certificates/iso9001.pdf',
            ],
            [
                'name' => 'ISO 14001 Environmental Management',
                'no_certificate' => 'CERT-002',
                'project_id' => $projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id,
                'barang_certificate_id' => $this->getBarangCertificateForProject($projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id),
                'status' => 'Aktif',
                'date_of_issue' => '2024-02-20',
                'date_of_expired' => '2027-02-20',
                'attachment' => 'certificates/iso14001.pdf',
            ],
            [
                'name' => 'OHSAS 18001 Safety Management',
                'no_certificate' => 'CERT-003',
                'project_id' => $projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id,
                'barang_certificate_id' => $this->getBarangCertificateForProject($projects->where('name', 'LIKE', '%INDOGREEN%')->first()?->id ?? $projects->first()->id),
                'status' => 'Belum',
                'date_of_issue' => '2024-03-10',
                'date_of_expired' => '2027-03-10',
                'attachment' => null,
            ],
            [
                'name' => 'Solar Panel TUV Certification',
                'no_certificate' => 'CERT-004',
                'project_id' => $projects->where('name', 'LIKE', '%PLTS%')->first()?->id ?? $projects->first()->id,
                'barang_certificate_id' => $this->getBarangCertificateForProject($projects->where('name', 'LIKE', '%PLTS%')->first()?->id ?? $projects->first()->id),
                'status' => 'Tidak Aktif',
                'date_of_issue' => '2023-06-15',
                'date_of_expired' => '2026-06-15',
                'attachment' => 'certificates/solar_panel.pdf',
            ],
            [
                'name' => 'Inverter IEC Certification',
                'no_certificate' => 'CERT-005',
                'project_id' => $projects->where('name', 'LIKE', '%PLTS%')->first()?->id ?? $projects->first()->id,
                'barang_certificate_id' => $this->getBarangCertificateForProject($projects->where('name', 'LIKE', '%PLTS%')->first()?->id ?? $projects->first()->id),
                'status' => 'Aktif',
                'date_of_issue' => '2024-04-05',
                'date_of_expired' => '2027-04-05',
                'attachment' => 'certificates/inverter.pdf',
            ],
        ];

        foreach ($certificates as $certificate) {
            Certificate::create($certificate);
        }

        // Create additional random certificates with proper relationships
        Certificate::factory(100)->create();

        $this->command->info('Certificates seeded successfully.');
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
