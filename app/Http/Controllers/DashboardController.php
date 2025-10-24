<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Certificate;
use App\Models\Mitra;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Dashboard data (cards + charts).
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // ---- latest projects
        $latestProjects = Project::with('mitra')->latest()->take(6)->get();

        // ---- status counts
        $totalProjects = Project::count();
        $statusCounts = Project::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $ongoing  = (int) ($statusCounts['Ongoing']  ?? 0);
        $prospect = (int) ($statusCounts['Prospect'] ?? 0);
        $complete = (int) ($statusCounts['Complete'] ?? 0);
        $cancel   = (int) ($statusCounts['Cancel']   ?? 0);

        // ---- certificate metrics
        $certProjects   = (int) Project::where('is_cert_projects', true)->count();
        $certActive     = (int) Certificate::where('status', 'Aktif')->count();
        $certExpiring30 = (int) Certificate::whereNotNull('date_of_expired')
            ->whereBetween('date_of_expired', [now(), now()->addDays(30)])
            ->count();

        // ---- trend 12 months (by start_date if available, else created_at)
        $start = Carbon::now()->startOfMonth()->subMonths(11);
        $end   = Carbon::now()->endOfMonth();

        $trendRows = Project::select(
                DB::raw("DATE_FORMAT(COALESCE(start_date, created_at), '%Y-%m') as ym"),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween(DB::raw("COALESCE(start_date, created_at)"), [$start, $end])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->pluck('total', 'ym');

        $labels = [];
        $counts = [];
        $cursor = $start->copy();
        for ($i = 0; $i < 12; $i++) {
            $key = $cursor->format('Y-m');
            // contoh label: "Jan 2025" (akan mengikuti locale server)
            $labels[] = $cursor->format('M Y');
            $counts[] = (int) ($trendRows[$key] ?? 0);
            $cursor->addMonth();
        }

        // ---- status distribution (for doughnut)
        $statusLabels = ['Ongoing', 'Prospect', 'Complete', 'Cancel'];
        $statusData = array_map(fn ($s) => (int) ($statusCounts[$s] ?? 0), $statusLabels);

        // ---- kategori distribution (bar)
        $kategoriRows = Project::select('kategori', DB::raw('COUNT(*) as total'))
            ->groupBy('kategori')
            ->orderBy('kategori')
            ->get();
        $kategoriLabels = $kategoriRows->pluck('kategori')->map(fn ($v) => $v ?: 'Tidak ada')->values();
        $kategoriCounts = $kategoriRows->pluck('total')->map(fn ($v) => (int) $v)->values();

        // ---- top customers by project count (max 5)
        $topRows = Project::select('mitra_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('mitra_id')
            ->groupBy('mitra_id')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        $names = Mitra::whereIn('id', $topRows->pluck('mitra_id'))
            ->pluck('nama', 'id');

        $topLabels = $topRows->map(fn ($r) => $names[$r->mitra_id] ?? 'Unknown')->all();
        $topCounts = $topRows->pluck('total')->map(fn ($v) => (int) $v)->all();

        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'latest_projects' => $latestProjects,
                'totals' => [
                    'total_projects'   => (int) $totalProjects,
                    'ongoing'          => $ongoing,
                    'prospect'         => $prospect,
                    'complete'         => $complete,
                    'cancel'           => $cancel,
                    'cert_projects'    => $certProjects,
                    'cert_active'      => $certActive,
                    'cert_expiring_30' => $certExpiring30,
                ],
                'trend_12_months' => [
                    'labels' => $labels,
                    'counts' => $counts,
                ],
                'status_distribution' => [
                    'labels' => $statusLabels,
                    'counts' => $statusData,
                ],
                'kategori_distribution' => [
                    'labels' => $kategoriLabels,
                    'counts' => $kategoriCounts,
                ],
                'top_customers' => [
                    'labels' => $topLabels,
                    'counts' => $topCounts,
                ],
            ]
        ]);
    }
}
