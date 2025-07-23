<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Display a listing of the dashboard data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $projects = Project::with('mitra')->orderBy('created_at', 'desc')->latest()->take(5)->get();
        $activities = Activity::with('project')->latest()->take(5)->get(); // Load project juga

        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'latest_projects' => $projects,
                'latest_activities' => $activities,
                // Anda bisa menambahkan metrik lain di sini jika ada
            ]
        ]);
    }
}