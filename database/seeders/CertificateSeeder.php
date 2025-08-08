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

        // Create some sample certificates
        $certificates = [
            [
                'name' => 'ISO 9001 Certificate',
                'no_certificate' => 'CERT-001',
                'project_id' => $projects->random()->id,
                'barang_certificate_id' => $barangCertificates->random()->id,
                'status' => 'Aktif',
                'date_of_issue' => '2024-01-15',
                'date_of_expired' => '2027-01-15',
                'attachment' => 'certificates/iso9001.pdf',
            ],
            [
                'name' => 'ISO 14001 Certificate',
                'no_certificate' => 'CERT-002',
                'project_id' => $projects->random()->id,
                'barang_certificate_id' => $barangCertificates->random()->id,
                'status' => 'Aktif',
                'date_of_issue' => '2024-02-20',
                'date_of_expired' => '2027-02-20',
                'attachment' => 'certificates/iso14001.pdf',
            ],
            [
                'name' => 'OHSAS 18001 Certificate',
                'no_certificate' => 'CERT-003',
                'project_id' => $projects->random()->id,
                'barang_certificate_id' => $barangCertificates->random()->id,
                'status' => 'Belum',
                'date_of_issue' => '2024-03-10',
                'date_of_expired' => '2027-03-10',
                'attachment' => null,
            ],
            [
                'name' => 'Solar Panel Certification',
                'no_certificate' => 'CERT-004',
                'project_id' => $projects->random()->id,
                'barang_certificate_id' => $barangCertificates->random()->id,
                'status' => 'Tidak Aktif',
                'date_of_issue' => '2023-06-15',
                'date_of_expired' => '2026-06-15',
                'attachment' => 'certificates/solar_panel.pdf',
            ],
            [
                'name' => 'Inverter Certification',
                'no_certificate' => 'CERT-005',
                'project_id' => $projects->random()->id,
                'barang_certificate_id' => $barangCertificates->random()->id,
                'status' => 'Aktif',
                'date_of_issue' => '2024-04-05',
                'date_of_expired' => '2027-04-05',
                'attachment' => 'certificates/inverter.pdf',
            ],
        ];

        foreach ($certificates as $certificate) {
            Certificate::create($certificate);
        }

        // Create additional random certificates
        Certificate::factory(15)->create();

        $this->command->info('Certificates seeded successfully.');
    }
}
