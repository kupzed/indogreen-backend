<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Mitra;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $mitraIds = Mitra::where('is_customer', true)->pluck('id')->toArray();
        
        // Create INDOGREEN project only
        Project::create([
            'name' => 'INDOGREEN',
            'description' => 'Project Internal Indogreen',
            'status' => 'Ongoing',
            'start_date' => '2023-05-19',
            'mitra_id' => 1,
            'kategori' => 'PLTS Hybrid',
            'lokasi' => 'Yasmin, Bogor',
            'no_po' => 'PO-0001',
            'no_so' => 'SO-0001',
            'is_cert_projects' => false,
        ]);

        // \App\Models\Project::factory(52)->create();
    }
} 