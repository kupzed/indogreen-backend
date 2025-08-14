<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Project;
use App\Models\BarangCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        $project = Project::inRandomOrder()->first();
        
        // Get barang certificates that belong to the same mitra as the project
        $barangCertificate = null;
        if ($project && $project->mitra_id) {
            $barangCertificate = BarangCertificate::where('mitra_id', $project->mitra_id)->inRandomOrder()->first();
        }
        
        // If no barang certificate found for the project's mitra, create one
        if (!$barangCertificate && $project && $project->mitra_id) {
            $barangCertificate = BarangCertificate::factory()->create([
                'mitra_id' => $project->mitra_id
            ]);
        }
        
        $dateOfIssue = $this->faker->dateTimeBetween('-2 years', 'now');
        $dateOfExpired = $this->faker->dateTimeBetween($dateOfIssue, '+2 years');
        
        return [
            'name' => $this->faker->words(3, true),
            'no_certificate' => $this->faker->unique()->numerify('CERT-#####'),
            'project_id' => $project?->id ?? null,
            'barang_certificate_id' => $barangCertificate?->id ?? null,
            'status' => $this->faker->randomElement(['Belum', 'Tidak Aktif', 'Aktif']),
            'date_of_issue' => $dateOfIssue->format('Y-m-d'),
            'date_of_expired' => $dateOfExpired->format('Y-m-d'),
            'attachment' => $this->faker->optional()->filePath(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
