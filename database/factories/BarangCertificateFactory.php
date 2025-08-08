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
        $mitra = Mitra::inRandomOrder()->first();
        
        return [
            'name' => $this->faker->words(3, true),
            'no_seri' => $this->faker->unique()->numerify('SERI-#####'),
            'mitra_id' => $mitra?->id ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
