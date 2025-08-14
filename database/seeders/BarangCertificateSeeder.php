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

        // Get specific mitras for consistent seeding
        $indogreenMitra = Mitra::where('nama', 'LIKE', '%Indogreen%')->first();
        $mitraJayaMitra = Mitra::where('nama', 'LIKE', '%Mitra Jaya%')->first();
        
        // Create some sample barang certificates with consistent mitra assignment
        $barangCertificates = [
            [
                'name' => 'Samsung Solar Panel 400W',
                'no_seri' => 'SERI-001',
                'mitra_id' => $indogreenMitra?->id ?? $mitras->first()->id,
            ],
            [
                'name' => 'SMA Inverter 5000W',
                'no_seri' => 'SERI-002',
                'mitra_id' => $indogreenMitra?->id ?? $mitras->first()->id,
            ],
            [
                'name' => 'LG Battery 10kWh',
                'no_seri' => 'SERI-003',
                'mitra_id' => $indogreenMitra?->id ?? $mitras->first()->id,
            ],
            [
                'name' => 'Victron Mounting System',
                'no_seri' => 'SERI-004',
                'mitra_id' => $mitraJayaMitra?->id ?? $mitras->random()->id,
            ],
            [
                'name' => 'Panasonic Cable 4mm²',
                'no_seri' => 'SERI-005',
                'mitra_id' => $mitraJayaMitra?->id ?? $mitras->random()->id,
            ],
            [
                'name' => 'Sharp Charge Controller 60A',
                'no_seri' => 'SERI-006',
                'mitra_id' => $indogreenMitra?->id ?? $mitras->first()->id,
            ],
            [
                'name' => 'Fronius Solar Pump 2HP',
                'no_seri' => 'SERI-007',
                'mitra_id' => $mitraJayaMitra?->id ?? $mitras->random()->id,
            ],
            [
                'name' => 'Jinko Solar Panel 500W',
                'no_seri' => 'SERI-008',
                'mitra_id' => $indogreenMitra?->id ?? $mitras->first()->id,
            ],
        ];

        foreach ($barangCertificates as $barangCertificate) {
            BarangCertificate::create($barangCertificate);
        }

        // Create additional random barang certificates
        BarangCertificate::factory(50)->create();

        $this->command->info('Barang certificates seeded successfully.');
    }
}
