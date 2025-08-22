<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Mitra;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['Prospect', 'Ongoing', 'Cancel', 'Complete']);
        $start = $this->faker->dateTimeBetween('-1 years', 'now');
        $finish = $this->faker->optional()->dateTimeBetween($start, '+6 months');
        
        // Get a mitra that is a customer
        $mitraCustomer = Mitra::where('is_customer', true)->inRandomOrder()->first();
        
        // If no customer mitra found, get any mitra
        if (!$mitraCustomer) {
            $mitraCustomer = Mitra::inRandomOrder()->first();
        }
        
        $kategoriOptions = [
            'PLTS Hybrid', 
            'PLTS Ongrid', 
            'PLTS Offgrid', 
            'PJUTS All In One', 
            'PJUTS Two In One', 
            'PJUTS Konvensional'
        ];
        
        // Generate realistic project names
        $projectTypes = [
            'PLTS', 'PJUTS', 'Solar Farm', 'Rooftop Solar', 'Street Lighting',
            'Hybrid System', 'Off-grid System', 'Grid-tied System'
        ];
        
        $projectType = $this->faker->randomElement($projectTypes);
        $location = $this->faker->randomElement(['Jakarta', 'Bandung', 'Surabaya', 'Medan', 'Semarang', 'Yogyakarta', 'Malang', 'Palembang', 'Makassar', 'Manado']);
        
        return [
            'name' => $projectType . ' ' . $location . ' ' . $this->faker->numerify('##'),
            'description' => $this->faker->paragraph(2),
            'status' => $status,
            'start_date' => $start->format('Y-m-d'),
            'finish_date' => $finish ? $finish->format('Y-m-d') : null,
            'mitra_id' => $mitraCustomer?->id ?? 1,
            'kategori' => $this->faker->randomElement($kategoriOptions),
            'lokasi' => $location,
            'no_po' => $this->faker->optional()->numerify('PO-####'),
            'no_so' => $this->faker->optional()->numerify('SO-####'),
            'is_cert_projects' => $this->faker->boolean(20), // 20% chance of being true
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
} 