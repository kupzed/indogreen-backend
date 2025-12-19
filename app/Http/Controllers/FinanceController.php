<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
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
        $activities = Activity::with('project:id,name') // Eager load project agar query ringan
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->whereIn('kategori', $financeCategories) // Filter hanya kategori di atas
            ->orderBy('activity_date', 'asc') // Urutkan berdasarkan tanggal
            ->get();

        // 4. Formatting Data (Mapping)
        // Kita format agar sesuai kebutuhan frontend/laporan
        $reportData = $activities->map(function ($activity) {
            return [
                'activity_date' => $activity->activity_date->format('Y-m-d'), // Format tanggal
                'kategori'      => $activity->kategori,
                'activity_name' => $activity->name,
                'project_name'  => $activity->project ? $activity->project->name : '-', // Handle jika project null
                'value'         => $activity->value,
                // Optional: Format currency string untuk tampilan langsung
                'value_formatted' => 'Rp ' . number_format($activity->value, 0, ',', '.'),
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
}