<?php

namespace App\Helpers;

use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityHelper
{
    /**
     * Log an activity manually
     */
    public static function log($action, $modelType = null, $modelId = null, $modelName = null, $description = null, $oldValues = null, $newValues = null)
    {
        $service = new ActivityLogService();
        return $service->log($action, $modelType, $modelId, $modelName, $description, $oldValues, $newValues);
    }

    /**
     * Log user action
     */
    public static function logUserAction($action, $description = null)
    {
        return self::log(
            $action,
            \App\Models\User::class,
            Auth::id(),
            Auth::user() ? Auth::user()->name : 'Unknown User',
            $description
        );
    }

    /**
     * Log model action
     */
    public static function logModelAction($action, $model, $description = null)
    {
        return self::log(
            $action,
            get_class($model),
            $model->id,
            $model->getActivityName ?? $model->name ?? class_basename($model) . ' #' . $model->id,
            $description
        );
    }

    /**
     * Log view action
     */
    public static function logView($model, $description = null)
    {
        return self::logModelAction('view', $model, $description);
    }

    /**
     * Log export action
     */
    public static function logExport($modelType, $description = null)
    {
        return self::log(
            'export',
            $modelType,
            null,
            class_basename($modelType) . ' Export',
            $description
        );
    }

    /**
     * Log import action
     */
    public static function logImport($modelType, $description = null)
    {
        return self::log(
            'import',
            $modelType,
            null,
            class_basename($modelType) . ' Import',
            $description
        );
    }
} 