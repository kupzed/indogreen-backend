<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get finance activities based on filters.
     *
     * @param array $filters
     * @return Collection
     */
    public function getFinanceReport(array $filters): Collection
    {
        $type = $filters['type'] ?? 'month';
        
        $query = Activity::query()
            ->with(['project:id,name', 'mitra:id,nama', 'attachments'])
            ->whereIn('kategori', $this->getFinanceCategories())
            ->orderBy('activity_date', 'asc');

        if ($type === 'month') {
            $month = $filters['month'] ?? date('n');
            $year = $filters['year'] ?? date('Y');
            
            $query->whereYear('activity_date', $year)
                  ->whereMonth('activity_date', $month);
        } elseif ($type === 'project') {
            $projectId = $filters['project_id'];
            $startDate = $filters['start_date'] ?? null;
            $endDate = $filters['end_date'] ?? null;

            $query->where('project_id', $projectId);

            if ($startDate) {
                $query->whereDate('activity_date', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('activity_date', '<=', $endDate);
            }
        }

        return $query->get();
    }

    /**
     * Update activity value and log the change.
     *
     * @param Activity $activity
     * @param float $value
     * @return Activity
     */
    public function updateActivityValue(Activity $activity, float $value): Activity
    {
        $previousValue = $activity->value;

        $activity->update(['value' => $value]);
        
        $activity->load(['project:id,name', 'mitra:id,nama', 'attachments']);

        $this->activityLogService->log(
            'finance_value_update',
            'finance_activity_value',
            $activity->id,
            $activity->name,
            sprintf('Memperbarui nilai finance activity #%d', $activity->id),
            ['value' => $previousValue],
            ['value' => $activity->value]
        );

        return $activity;
    }

    /**
     * List of finance categories.
     *
     * @return array
     */
    public function getFinanceCategories(): array
    {
        return [
            'Expense Report',
            'Invoice',
            'Invoice & FP',
            'Payment',
            'Faktur Pajak',
            'Kasbon',
        ];
    }
}
