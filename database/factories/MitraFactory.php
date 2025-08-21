<?php

namespace Database\Factories;

use App\Models\Mitra;
use Illuminate\Database\Eloquent\Factories\Factory;

class MitraFactory extends Factory
{
    protected $model = Mitra::class;

    public function definition(): array
    {
        // Pilih minimal satu kategori secara acak
        $kategoriList = ['is_pribadi', 'is_perusahaan', 'is_customer', 'is_vendor'];
        $chosen = $this->faker->randomElements($kategoriList, $this->faker->numberBetween(1, 4));
        $flags = [
            'is_pribadi' => false,
            'is_perusahaan' => false,
            'is_customer' => false,
            'is_vendor' => false,
        ];
        foreach ($chosen as $cat) {
            $flags[$cat] = true;
        }
        return [
            'nama' => $this->faker->company(),
            'is_pribadi' => $flags['is_pribadi'],
            'is_perusahaan' => $flags['is_perusahaan'],
            'is_customer' => $flags['is_customer'],
            'is_vendor' => $flags['is_vendor'],
            'alamat' => $this->faker->address(),
            'website' => $this->faker->optional()->url(),
            'email' => $this->faker->optional()->safeEmail(),
            'kontak_1' => $this->faker->optional()->phoneNumber(),
            'kontak_1_nama' => $this->faker->optional()->name(),
            'kontak_1_jabatan' => $this->faker->optional()->jobTitle(),
            'kontak_2_nama' => $this->faker->optional()->name(),
            'kontak_2' => $this->faker->optional()->phoneNumber(),
            'kontak_2_jabatan' => $this->faker->optional()->jobTitle(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
} 