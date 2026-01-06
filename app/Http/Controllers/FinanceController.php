<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
        $this->middleware('permission:finance-view')->only(['monthlyReport', 'projectReport']);
        $this->middleware('permission:finance-update')->only('updateValue');
    }

    /**
     * Menampilkan laporan keuangan bulanan.
     */
    public function monthlyReport(Request $request)
    {
        // 1. Validasi Input (Bulan dan Tahun)
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2000|max:'.(date('Y')+1),
        ]);

        $month = $request->month;
        $year  = $request->year;

        // 2. Daftar Kategori Keuangan (Sesuai request Anda)
        $financeCategories = [
            'Expense Report',
            'Invoice',
            'Invoice & FP',
            'Payment',
            'Faktur Pajak',
            'Kasbon',
        ];

        // 3. Query Data
        $activities = Activity::with([
                'project:id,name',
                'mitra:id,nama',
                'attachments',
            ]) // Eager load relasi penting
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->whereIn('kategori', $financeCategories) // Filter hanya kategori di atas
            ->orderBy('activity_date', 'asc') // Urutkan berdasarkan tanggal
            ->get();

        // 4. Formatting Data (Mapping)
        // Kita format agar sesuai kebutuhan frontend/laporan
        $reportData = $activities->map(function ($activity) {
            return [
                'activity_date'   => $activity->activity_date->format('Y-m-d'),
                'kategori'        => $activity->kategori,
                'activity_name'   => $activity->name,
                'project_name'    => $activity->project ? $activity->project->name : '-',
                'value'           => $activity->value,
                'value_formatted' => 'Rp ' . number_format($activity->value, 0, ',', '.'),
                'activity'        => $activity->loadMissing(['project', 'mitra', 'attachments'])->toArray(),
            ];
        });

        // 5. Hitung Total (Opsional tapi sangat berguna untuk laporan)
        $totalValue = $activities->sum('value');

        return response()->json([
            'status' => 'success',
            'meta' => [
                'period' => "$year-$month",
                'total_records' => $reportData->count(),
                'total_value' => $totalValue,
            ],
            'data' => $reportData,
        ]);
    }

    /**
     * Menampilkan laporan keuangan berdasarkan project.
     */
    public function projectReport(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $projectId = (int) $validated['project_id'];
        $startDate = $validated['start_date'] ?? null;
        $endDate   = $validated['end_date'] ?? null;

        $financeCategories = [
            'Expense Report',
            'Invoice',
            'Invoice & FP',
            'Payment',
            'Faktur Pajak',
            'Kasbon',
        ];

        $activitiesQuery = Activity::with([
                'project:id,name',
                'mitra:id,nama',
                'attachments',
            ])
            ->where('project_id', $projectId)
            ->whereIn('kategori', $financeCategories);

        if ($startDate) {
            $activitiesQuery->whereDate('activity_date', '>=', $startDate);
        }

        if ($endDate) {
            $activitiesQuery->whereDate('activity_date', '<=', $endDate);
        }

        $activities = $activitiesQuery
            ->orderBy('activity_date', 'asc')
            ->get();

        $reportData = $activities->map(function ($activity) {
            return [
                'activity_date'   => optional($activity->activity_date)->format('Y-m-d'),
                'kategori'        => $activity->kategori,
                'activity_name'   => $activity->name,
                'project_name'    => $activity->project ? $activity->project->name : '-',
                'value'           => $activity->value,
                'value_formatted' => 'Rp ' . number_format($activity->value, 0, ',', '.'),
                'activity'        => $activity->loadMissing(['project', 'mitra', 'attachments'])->toArray(),
            ];
        });

        $totalValue = $activities->sum('value');
        $project = Project::select('id', 'name')->find($projectId);

        return response()->json([
            'status' => 'success',
            'meta' => [
                'project' => $project,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'total_records' => $reportData->count(),
                'total_value' => $totalValue,
            ],
            'data' => $reportData,
        ]);
    }

    /**
     * Update hanya kolom value activity terkait laporan keuangan.
     */
    public function updateValue(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'value' => 'required|numeric|min:0',
        ]);

        $previousValue = $activity->value;

        $activity->update(['value' => $validated['value']]);

        $activity->load(['project:id,name', 'mitra:id,nama', 'attachments']);

        // [TETAP ADA] Log hanya tercatat saat melakukan update value
        $this->activityLogService->log(
            'finance_value_update',
            'finance_activity_value',
            $activity->id,
            $activity->name,
            sprintf('Memperbarui nilai finance activity #%d', $activity->id),
            ['value' => $previousValue],
            ['value' => $activity->value]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai activity berhasil diperbarui',
            'meta' => [
                'value_formatted' => 'Rp ' . number_format($activity->value, 0, ',', '.'),
            ],
            'data' => $activity,
        ]);
    }
}