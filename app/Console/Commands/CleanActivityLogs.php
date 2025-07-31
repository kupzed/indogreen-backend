<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Storage;

class CleanActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-logs:clean {--days=30 : Number of days to keep logs} {--user= : Clean logs for specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old activity log files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $userId = $this->option('user');
        $service = new ActivityLogService();
        
        $this->info("Cleaning activity logs older than {$days} days...");
        
        if ($userId) {
            $this->cleanUserLogs($userId, $days);
        } else {
            $this->cleanAllUserLogs($days);
        }
        
        $this->info('Activity logs cleaning completed!');
    }
    
    protected function cleanUserLogs($userId, $days)
    {
        $userDir = "activity-logs/user_{$userId}";
        
        if (!Storage::exists($userDir)) {
            $this->warn("No logs found for user {$userId}");
            return;
        }
        
        $files = Storage::files($userDir);
        $cutoffDate = now()->subDays($days);
        $deletedCount = 0;
        
        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            $fileDate = \Carbon\Carbon::createFromTimestamp($lastModified);
            
            if ($fileDate->lt($cutoffDate)) {
                Storage::delete($file);
                $deletedCount++;
                $this->line("Deleted: {$file}");
            }
        }
        
        $this->info("Deleted {$deletedCount} old log files for user {$userId}");
    }
    
    protected function cleanAllUserLogs($days)
    {
        $directories = Storage::directories('activity-logs');
        $totalDeleted = 0;
        
        foreach ($directories as $dir) {
            if (preg_match('/user_(\d+)$/', $dir, $matches)) {
                $userId = $matches[1];
                $this->cleanUserLogs($userId, $days);
            }
        }
        
        $this->info("Total files deleted: {$totalDeleted}");
    }
}
