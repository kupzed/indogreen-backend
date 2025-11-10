<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Activity;
use App\Models\Mitra;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Activity::factory(1032)->create();
    }
} 