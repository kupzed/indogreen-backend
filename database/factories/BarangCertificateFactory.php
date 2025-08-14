<?php

namespace Database\Factories;

use App\Models\BarangCertificate;
use App\Models\Mitra;
use Illuminate\Database\Eloquent\Factories\Factory;

class BarangCertificateFactory extends Factory
{
    protected $model = BarangCertificate::class;

    public function definition(): array
    {
        // Get a random mitra, prefer those that are vendors or customers
        $mitra = Mitra::where(function($query) {
            $query->where('is_vendor', true)
                  ->orWhere('is_customer', true)
                  ->orWhere('is_perusahaan', true);
        })->inRandomOrder()->first();
        
        // If no suitable mitra found, get any mitra
        if (!$mitra) {
            $mitra = Mitra::inRandomOrder()->first();
        }
        
        // Generate realistic product names
        $productTypes = [
            'Solar Panel', 'Inverter', 'Battery', 'Mounting System', 'Cable', 'Connector',
            'Charge Controller', 'Solar Pump', 'LED Light', 'Power Bank', 'Generator',
            'Transformer', 'Switchgear', 'Meter', 'Fuse', 'Circuit Breaker'
        ];
        
        $productType = $this->faker->randomElement($productTypes);
        $brand = $this->faker->randomElement(['Samsung', 'LG', 'Panasonic', 'Sharp', 'Jinko', 'Trina', 'Canadian Solar', 'SMA', 'Fronius', 'Victron']);
        
        return [
            'name' => $brand . ' ' . $productType,
            'no_seri' => $this->faker->unique()->numerify('SERI-#####'),
            'mitra_id' => $mitra?->id ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
