<?php

namespace App\Models\Traits;

use App\Services\ActivityLogService;
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
        
        $service = new ActivityLogService();
        $service->log(
            $action,
            get_class($this),
            $this->id,
            $this->getActivityName(),
            $description,
            $action === 'updated' ? $this->getOriginal() : null,
            $action !== 'deleted' ? $this->getAttributes() : null
        );
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

    // Get activity logs for this model
    public function getActivityLogs($filters = [])
    {
        $service = new ActivityLogService();
        return $service->getUserLogs(Auth::id(), array_merge($filters, [
            'model_type' => get_class($this),
            'model_id' => $this->id
        ]));
    }
} 