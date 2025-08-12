<?php

namespace Database\Seeders;

use App\Models\BarangCertificate;
use App\Models\Mitra;
use Illuminate\Database\Seeder;

class BarangCertificateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mitras = Mitra::all();

        if ($mitras->isEmpty()) {
            $this->command->warn('No mitras found. Please run MitraSeeder first.');
            return;
        }

        // Create some sample barang certificates
        $barangCertificates = [
            [
                'name' => 'Solar Panel Certificate',
                'no_seri' => 'SERI-001',
                'mitra_id' => $mitras->random()->id,
            ],
            [
                'name' => 'Inverter Certificate',
                'no_seri' => 'SERI-002',
                'mitra_id' => $mitras->random()->id,
            ],
            [
                'name' => 'Battery Certificate',
                'no_seri' => 'SERI-003',
                'mitra_id' => $mitras->random()->id,
            ],
            [
                'name' => 'Mounting System Certificate',
                'no_seri' => 'SERI-004',
                'mitra_id' => $mitras->random()->id,
            ],
            [
                'name' => 'Cable Certificate',
                'no_seri' => 'SERI-005',
                'mitra_id' => $mitras->random()->id,
            ],
        ];

        foreach ($barangCertificates as $barangCertificate) {
            BarangCertificate::create($barangCertificate);
        }

        // Create additional random barang certificates
        BarangCertificate::factory(76)->create();

        $this->command->info('Barang certificates seeded successfully.');
    }
}
