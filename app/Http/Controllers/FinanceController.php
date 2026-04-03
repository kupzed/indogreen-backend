<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Http\Requests\FinanceReportRequest;
use App\Http\Requests\UpdateFinanceValueRequest;
use App\Http\Resources\FinanceResource;
use App\Services\FinanceService;
use App\Services\ActivityLogService;

class FinanceController extends Controller
{
    public function __construct(protected FinanceService $financeService, protected ActivityLogService $activityLogService)
    {
        $this->middleware('permission:finance-view')->only(['index']);
        $this->middleware('permission:finance-update')->only(['update']);
    }

    /**
     * Display a listing of finance activities based on filters.
     *
     * @param FinanceReportRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(FinanceReportRequest $request)
    {
        $validated = $request->validated();
        
        $activities = $this->financeService->getFinanceReport($validated);

        $totalValue = $activities->sum('value');
        
        $meta = [
            'total_records' => $activities->count(),
            'total_value' => $totalValue,
            'type' => $validated['type'],
        ];

        // Sejajarkan meta dengan yang dibutuhkan frontend (khusus project report)
        if ($validated['type'] === 'project') {
            $project = Project::select('id', 'name')->find($validated['project_id']);
            $meta['project'] = $project;
            $meta['filters'] = [
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ];
        } else {
            $meta['period'] = $validated['year'] . '-' . str_pad($validated['month'], 2, '0', STR_PAD_LEFT);
        }

        return response()->json([
            'status' => 'success',
            'meta' => $meta,
            'data' => FinanceResource::collection($activities),
        ]);
    }

    /**
     * Update the specified activity value.
     *
     * @param UpdateFinanceValueRequest $request
     * @param Activity $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFinanceValueRequest $request, Activity $finance)
    {
        $validated = $request->validated();

        $finance = $this->financeService->updateActivityValue($finance, $validated['value']);

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai activity berhasil diperbarui',
            'meta' => [
                'value_formatted' => 'Rp ' . number_format((float) $finance->value, 0, ',', '.'),
            ],
            'data' => new FinanceResource($finance),
        ]);
    }
}