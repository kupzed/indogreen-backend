<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new ActivityLogService();
    }

    /**
     * Display a listing of activity logs
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        $isAdmin = $actor->hasAnyRole(['super_admin', 'admin']);
        
        $filters = [];
        
        // Build filters
        if ($request->has('action') && $request->action) {
            $filters['action'] = $request->action;
        }
        
        if ($request->has('model_type') && $request->model_type) {
            $filters['model_type'] = $request->model_type;
        }
        
        if ($request->has('user_id') && $request->user_id) {
            $filters['user_id'] = $request->user_id;
        }
        
        if ($request->has('date_from') && $request->date_from) {
            $filters['date_from'] = $request->date_from;
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $filters['date_to'] = $request->date_to;
        }
        
        if ($request->has('search') && $request->search) {
            $filters['search'] = $request->search;
        }

        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // Fetch logs (null for all users if admin, else only current user)
        $logTargetId = $isAdmin ? null : $actor->id;
        $allLogs = $this->service->getLogs($filters, $logTargetId);
        
        $total = count($allLogs);
        $offset = ($page - 1) * $perPage;
        $logs = array_slice($allLogs, $offset, $perPage);

        return response()->json([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ]
        ]);
    }

    /**
     * Get activity logs for specific model
     */
    public function getModelLogs(Request $request, $modelType, $modelId): JsonResponse
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        $isAdmin = $actor->hasAnyRole(['super_admin', 'admin']);

        $filters = [
            'model_type' => $modelType,
            'model_id' => $modelId
        ];
        
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // For admins, show ALL logs for this model from ANY user
        // For regular users, show only their actions on this model
        $logTargetId = $isAdmin ? null : $actor->id;
        $allLogs = $this->service->getLogs($filters, $logTargetId);
        
        $total = count($allLogs);
        $offset = ($page - 1) * $perPage;
        $logs = array_slice($allLogs, $offset, $perPage);

        return response()->json([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ]
        ]);
    }

    /**
     * Get recent activity logs (last 10)
     */
    public function getRecent(): JsonResponse
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        $isAdmin = $actor->hasAnyRole(['super_admin', 'admin']);

        $logTargetId = $isAdmin ? null : $actor->id;
        $logs = $this->service->getRecentLogs(10, $logTargetId);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get activity statistics
     */
    public function getStats(): JsonResponse
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        $isAdmin = $actor->hasAnyRole(['super_admin', 'admin']);

        $logTargetId = $isAdmin ? null : $actor->id;
        $stats = $this->service->getStats($logTargetId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get available filter options
     */
    public function getFilterOptions(): JsonResponse
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        $isAdmin = $actor->hasAnyRole(['super_admin', 'admin']);

        $logTargetId = $isAdmin ? null : $actor->id;
        $options = $this->service->getFilterOptions($logTargetId);

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    /**
     * Export user logs
     */
    public function export(Request $request): JsonResponse
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        $isAdmin = $actor->hasAnyRole(['super_admin', 'admin']);

        $filters = [];
        
        if ($request->has('action') && $request->action) {
            $filters['action'] = $request->action;
        }
        
        if ($request->has('model_type') && $request->model_type) {
            $filters['model_type'] = $request->model_type;
        }
        
        if ($request->has('date_from') && $request->date_from) {
            $filters['date_from'] = $request->date_from;
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $filters['date_to'] = $request->date_to;
        }

        $logTargetId = $isAdmin ? null : $actor->id;
        $jsonData = $this->service->exportUserLogs($logTargetId, $filters);

        return response()->json([
            'success' => true,
            'data' => json_decode($jsonData, true)
        ]);
    }

    /**
     * Delete user logs
     */
    public function deleteUserLogs(): JsonResponse
    {
        $this->service->deleteUserLogs(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'User logs deleted successfully'
        ]);
    }
}
