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
            [
                'nama' => 'CV Mitra Jaya',
                'is_perusahaan' => false,
                'is_pribadi' => false,
                'is_customer' => false,
                'is_vendor' => true,
                'alamat' => 'Jl. Mitra No. 2, Bandung',
                'website' => 'https://mitrajaya.co.id',
                'email' => 'admin@mitrajaya.co.id',
                'kontak_1' => '082112345678',
                'kontak_1_nama' => 'Andi Mitra',
                'kontak_1_jabatan' => 'Owner',
                'kontak_2_nama' => null,
                'kontak_2' => null,
                'kontak_2_jabatan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Mitra Pribadi',
                'is_perusahaan' => false,
                'is_pribadi' => true,
                'is_customer' => false,
                'is_vendor' => false,
                'alamat' => 'Jl. Pribadi No. 3, Surabaya',
                'website' => 'https://pribadi.org',
                'email' => 'mitra.pribadi@gmail.com',
                'kontak_1' => '085612345678',
                'kontak_1_nama' => 'Rina Pribadi',
                'kontak_1_jabatan' => 'Freelancer',
                'kontak_2_nama' => null,
                'kontak_2' => null,
                'kontak_2_jabatan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        \App\Models\Mitra::factory(67)->create();
    }
    // {
    //     \App\Models\Mitra::factory(12)->create();
    // }
} 