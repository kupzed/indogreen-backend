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
        $mitraCustomer = Mitra::where('is_customer', true)->inRandomOrder()->first();
        
        $kategoriOptions = [
            'PLTS Hybrid', 
            'PLTS Ongrid', 
            'PLTS Offgrid', 
            'PJUTS All In One', 
            'PJUTS Two In One', 
            'PJUTS Konvensional'
        ];
        
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(2),
            'status' => $status,
            'start_date' => $start->format('Y-m-d'),
            'finish_date' => $finish ? $finish->format('Y-m-d') : null,
            'mitra_id' => $mitraCustomer?->id ?? 1,
            'kategori' => $this->faker->randomElement($kategoriOptions),
            'lokasi' => $this->faker->address(),
            'no_po' => $this->faker->optional()->numerify('PO-####'),
            'no_so' => $this->faker->optional()->numerify('SO-####'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
} 