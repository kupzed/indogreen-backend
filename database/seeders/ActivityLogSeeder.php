<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Project;
use App\Models\Activity;
use App\Models\Mitra;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users, projects, activities, and mitras
        $users = User::all();
        $projects = Project::all();
        $activities = Activity::all();
        $mitras = Mitra::all();

        if ($users->isEmpty() || $projects->isEmpty()) {
            $this->command->info('Skipping ActivityLogSeeder: No users or projects found');
            return;
        }

        $actions = ['created', 'updated', 'viewed', 'deleted'];
        $modelTypes = [
            Project::class => $projects,
            Activity::class => $activities,
            Mitra::class => $mitras,
        ];

        foreach ($modelTypes as $modelType => $models) {
            if ($models->isEmpty()) continue;

            foreach ($models as $model) {
                // Create 1-3 activity logs per model
                $logCount = rand(1, 3);
                
                for ($i = 0; $i < $logCount; $i++) {
                    $action = $actions[array_rand($actions)];
                    $user = $users->random();
                    
                    ActivityLog::create([
                        'user_id' => $user->id,
                        'action' => $action,
                        'model_type' => $modelType,
                        'model_id' => $model->id,
                        'model_name' => $model->getActivityNameAttribute(),
                        'description' => $this->getDescription($action, $model),
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Seeder/1.0',
                        'created_at' => now()->subDays(rand(1, 30)),
                        'updated_at' => now()->subDays(rand(1, 30)),
                    ]);
                }
            }
        }

        $this->command->info('ActivityLogSeeder completed successfully');
    }

    private function getDescription($action, $model)
    {
        $modelName = class_basename($model);
        
        switch ($action) {
            case 'created':
                return "Created new {$modelName}";
            case 'updated':
                return "Updated {$modelName}";
            case 'viewed':
                return "Viewed {$modelName}";
            case 'deleted':
                return "Deleted {$modelName}";
            default:
                return "Performed {$action} on {$modelName}";
        }
    }
}
