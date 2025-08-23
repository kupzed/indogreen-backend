<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MitraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('partners')->insert([
            [
                'nama' => 'PT. Indogreen Technology And Management',
                'is_perusahaan' => true,
                'is_pribadi' => false,
                'is_customer' => true,
                'is_vendor' => true,
                'alamat' => 'Ruko Taman Yasmin Sektor 6, No. 226, Jl. KH. Abdullah Bin Nuh - Kota Bogor',
                'website' => 'https://indogreen.id',
                'email' => 'support@indogreen.id',
                'kontak_1' => '02517541749',
                'kontak_1_nama' => 'Indogreen',
                'kontak_1_jabatan' => 'Office',
                'kontak_2_nama' => 'Admo',
                'kontak_2' => '08118307487',
                'kontak_2_jabatan' => 'Direktur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // \App\Models\Mitra::factory(67)->create();
    }
} 