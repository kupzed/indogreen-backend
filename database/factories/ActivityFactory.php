<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Mitra;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        // Kategori yang memiliki nilai uang
        $financeCategories = [
            'Expense Report', 'Invoice', 'Invoice & FP', 'Payment', 'Faktur Pajak', 'Kasbon'
        ];
        $kategori = $this->faker->randomElement([
            'Expense Report', 'Invoice', 'Invoice & FP', 'Purchase Order', 'Payment', 'Quotation',
            'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar',
            'Kontrak', 'Berita Acara', 'Receive Item', 'Delivery Order', 'Legalitas', 'Other',
        ]);
        // Jika kategori termasuk keuangan, beri nilai random, jika tidak 0
        $value = in_array($kategori, $financeCategories) 
            ? $this->faker->randomFloat(2, 100000, 50000000) // 100rb - 50jt
            : 0;
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
            'short_desc'   => Str::limit($this->faker->realText(120), 80, ''),
            'description' => $this->faker->paragraph(2),
            'project_id' => $project?->id ?? 1,
            'kategori' => $kategori,
            'value' => $value,
            'jenis' => $jenis,
            'mitra_id' => $mitra_id,
            'activity_date' => $this->faker->dateTimeBetween('2020-01-01', '2025-12-31'),
            'from' => $this->faker->optional()->sentence(4),
            'to' => $this->faker->optional()->sentence(4),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
} 