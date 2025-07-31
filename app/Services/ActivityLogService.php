<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ActivityLogService
{
    protected $logPath = 'activity-logs';
    protected $maxFileSize = 10485760; // 10MB per file
    protected $maxFilesPerUser = 100; // Maximum 100 files per user

    /**
     * Log an activity to file
     */
    public function log($action, $modelType = null, $modelId = null, $modelName = null, $description = null, $oldValues = null, $newValues = null)
    {
        $userId = Auth::id();
        if (!$userId) {
            return false;
        }

        $logData = [
            'id' => $this->generateLogId(),
            'user_id' => $userId,
            'user_name' => Auth::user() ? Auth::user()->name : 'Unknown User',
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'model_name' => $modelName,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'timestamp' => now()->toISOString(),
            'created_at' => now()->toISOString(),
        ];

        $this->writeToFile($userId, $logData);
        
        return $logData;
    }

    /**
     * Get activity logs for a user
     */
    public function getUserLogs($userId, $filters = [])
    {
        $logs = $this->readUserLogs($userId);
        
        // Apply filters
        if (!empty($filters)) {
            $logs = $this->filterLogs($logs, $filters);
        }

        return $logs;
    }

    /**
     * Get recent activity logs
     */
    public function getRecentLogs($limit = 10)
    {
        $allLogs = [];
        $users = $this->getAllUsers();
        
        foreach ($users as $userId) {
            $userLogs = $this->readUserLogs($userId);
            $allLogs = array_merge($allLogs, $userLogs);
        }

        // Sort by timestamp descending
        usort($allLogs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($allLogs, 0, $limit);
    }

    /**
     * Get activity statistics
     */
    public function getStats()
    {
        $stats = [
            'total_activities' => 0,
            'today_activities' => 0,
            'this_week_activities' => 0,
            'this_month_activities' => 0,
            'actions_count' => [],
            'models_count' => [],
        ];

        $users = $this->getAllUsers();
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        foreach ($users as $userId) {
            $userLogs = $this->readUserLogs($userId);
            
            foreach ($userLogs as $log) {
                $stats['total_activities']++;
                
                $logTime = Carbon::parse($log['timestamp']);
                
                if ($logTime->gte($today)) {
                    $stats['today_activities']++;
                }
                
                if ($logTime->gte($weekStart)) {
                    $stats['this_week_activities']++;
                }
                
                if ($logTime->gte($monthStart)) {
                    $stats['this_month_activities']++;
                }

                // Count actions
                $action = $log['action'];
                $stats['actions_count'][$action] = ($stats['actions_count'][$action] ?? 0) + 1;

                // Count models
                $modelType = $log['model_type'];
                if ($modelType) {
                    $stats['models_count'][$modelType] = ($stats['models_count'][$modelType] ?? 0) + 1;
                }
            }
        }

        return $stats;
    }

    /**
     * Get filter options
     */
    public function getFilterOptions()
    {
        $options = [
            'actions' => [],
            'model_types' => [],
            'users' => [],
        ];

        $users = $this->getAllUsers();
        
        foreach ($users as $userId) {
            $userLogs = $this->readUserLogs($userId);
            
            foreach ($userLogs as $log) {
                if (!in_array($log['action'], $options['actions'])) {
                    $options['actions'][] = $log['action'];
                }
                
                if ($log['model_type'] && !in_array($log['model_type'], $options['model_types'])) {
                    $options['model_types'][] = $log['model_type'];
                }
                
                $userKey = $log['user_id'] . '|' . $log['user_name'];
                if (!in_array($userKey, $options['users'])) {
                    $options['users'][] = $userKey;
                }
            }
        }

        return $options;
    }

    /**
     * Write log data to file
     */
    protected function writeToFile($userId, $logData)
    {
        $filename = $this->getCurrentFilename($userId);
        $filepath = "{$this->logPath}/user_{$userId}/{$filename}";
        
        // Create directory if not exists
        Storage::makeDirectory("{$this->logPath}/user_{$userId}");
        
        // Read existing logs
        $logs = [];
        if (Storage::exists($filepath)) {
            $content = Storage::get($filepath);
            $logs = json_decode($content, true) ?: [];
        }
        
        // Add new log
        $logs[] = $logData;
        
        // Check file size and rotate if needed
        if (strlen(json_encode($logs)) > $this->maxFileSize) {
            $this->rotateFile($userId, $filename);
            $logs = [$logData]; // Start new file with current log
        }
        
        // Write to file
        Storage::put($filepath, json_encode($logs, JSON_PRETTY_PRINT));
    }

    /**
     * Read user logs from files
     */
    protected function readUserLogs($userId)
    {
        $logs = [];
        $userDir = "{$this->logPath}/user_{$userId}";
        
        if (!Storage::exists($userDir)) {
            return $logs;
        }
        
        $files = Storage::files($userDir);
        
        // Sort files by name (newest first)
        rsort($files);
        
        foreach ($files as $file) {
            $content = Storage::get($file);
            $fileLogs = json_decode($content, true) ?: [];
            $logs = array_merge($logs, $fileLogs);
        }
        
        return $logs;
    }

    /**
     * Get current filename for user
     */
    protected function getCurrentFilename($userId)
    {
        return date('Y-m-d') . '.json';
    }

    /**
     * Rotate log file
     */
    protected function rotateFile($userId, $filename)
    {
        $oldPath = "{$this->logPath}/user_{$userId}/{$filename}";
        $newPath = "{$this->logPath}/user_{$userId}/" . date('Y-m-d_H-i-s') . '.json';
        
        if (Storage::exists($oldPath)) {
            Storage::move($oldPath, $newPath);
        }
        
        // Clean old files
        $this->cleanOldFiles($userId);
    }

    /**
     * Clean old log files
     */
    protected function cleanOldFiles($userId)
    {
        $userDir = "{$this->logPath}/user_{$userId}";
        $files = Storage::files($userDir);
        
        if (count($files) > $this->maxFilesPerUser) {
            // Sort files by modification time (oldest first)
            usort($files, function($a, $b) {
                return Storage::lastModified($a) - Storage::lastModified($b);
            });
            
            // Remove oldest files
            $filesToDelete = array_slice($files, 0, count($files) - $this->maxFilesPerUser);
            
            foreach ($filesToDelete as $file) {
                Storage::delete($file);
            }
        }
    }

    /**
     * Get all users who have logs
     */
    protected function getAllUsers()
    {
        $users = [];
        $directories = Storage::directories($this->logPath);
        
        foreach ($directories as $dir) {
            if (preg_match('/user_(\d+)$/', $dir, $matches)) {
                $users[] = $matches[1];
            }
        }
        
        return $users;
    }

    /**
     * Filter logs based on criteria
     */
    protected function filterLogs($logs, $filters)
    {
        return array_filter($logs, function($log) use ($filters) {
            foreach ($filters as $key => $value) {
                if (empty($value)) continue;
                
                switch ($key) {
                    case 'action':
                        if ($log['action'] !== $value) return false;
                        break;
                    case 'model_type':
                        if ($log['model_type'] !== $value) return false;
                        break;
                    case 'user_id':
                        if ($log['user_id'] != $value) return false;
                        break;
                    case 'date_from':
                        if (strtotime($log['timestamp']) < strtotime($value)) return false;
                        break;
                    case 'date_to':
                        if (strtotime($log['timestamp']) > strtotime($value)) return false;
                        break;
                    case 'search':
                        $search = strtolower($value);
                        $description = strtolower($log['description'] ?? '');
                        $modelName = strtolower($log['model_name'] ?? '');
                        if (strpos($description, $search) === false && strpos($modelName, $search) === false) {
                            return false;
                        }
                        break;
                }
            }
            return true;
        });
    }

    /**
     * Generate unique log ID
     */
    protected function generateLogId()
    {
        return uniqid('log_', true);
    }

    /**
     * Delete all logs for a user
     */
    public function deleteUserLogs($userId)
    {
        $userDir = "{$this->logPath}/user_{$userId}";
        if (Storage::exists($userDir)) {
            Storage::deleteDirectory($userDir);
        }
    }

    /**
     * Export user logs to JSON
     */
    public function exportUserLogs($userId, $filters = [])
    {
        $logs = $this->getUserLogs($userId, $filters);
        return json_encode($logs, JSON_PRETTY_PRINT);
    }
} 