<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Mitra;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        $kategori = $this->faker->randomElement([
            'Expense Report', 'Invoice', 'Invoice & FP', 'Purchase Order', 'Payment', 'Quotation',
            'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar',
            'Kontrak', 'Berita Acara', 'Receive Item', 'Other'
        ]);
        $jenis = $this->faker->randomElement(['Internal', 'Customer', 'Vendor']);
        $project = Project::inRandomOrder()->first();
        $mitraVendor = Mitra::where('is_vendor', true)->inRandomOrder()->first();
        $mitraCustomer = Mitra::where('is_customer', true)->inRandomOrder()->first();
        $mitra_id = null;
        if ($jenis === 'Internal') {
            $mitra_id = 1;
        } elseif ($jenis === 'Vendor') {
            $mitra_id = $mitraVendor?->id;
        } elseif ($jenis === 'Customer') {
            $mitra_id = $project?->mitra_id ?? $mitraCustomer?->id;
        }
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(2),
            'project_id' => $project?->id ?? 1,
            'kategori' => $kategori,
            'jenis' => $jenis,
            'mitra_id' => $mitra_id,
            'activity_date' => $this->faker->date(),
            'from' => $this->faker->optional()->sentence(4),
            'to' => $this->faker->optional()->sentence(4),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
} 