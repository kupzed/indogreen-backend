<?php

namespace App\Models\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        // Log saat model dibuat
        static::created(function ($model) {
            $model->logActivity('created');
        });

        // Log saat model diupdate
        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        // Log saat model dihapus
        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity($action)
    {
        $description = $this->getActivityDescription($action);
        
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'model_name' => $this->getActivityName(),
            'old_values' => $action === 'updated' ? $this->getOriginal() : null,
            'new_values' => $action !== 'deleted' ? $this->getAttributes() : null,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    protected function getActivityDescription($action)
    {
        $modelName = class_basename($this);
        
        switch ($action) {
            case 'created':
                return "Created new {$modelName}";
            case 'updated':
                return "Updated {$modelName}";
            case 'deleted':
                return "Deleted {$modelName}";
            default:
                return "Performed {$action} on {$modelName}";
        }
    }

    protected function getActivityName()
    {
        // Override method ini di model untuk memberikan nama yang lebih deskriptif
        if (method_exists($this, 'getName')) {
            return $this->getName();
        }
        
        if (isset($this->name)) {
            return $this->name;
        }
        
        if (isset($this->title)) {
            return $this->title;
        }
        
        return class_basename($this) . ' #' . $this->id;
    }

    // Relasi ke activity logs
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'model');
    }
} 