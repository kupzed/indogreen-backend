<?php

namespace App\Exports;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Return the query to be used for the export.
     * Uses the pre-built query from the controller (with all filters applied).
     */
    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * Define column headings for the Excel file.
     */
    public function headings(): array
    {
        return [
            'No',
            'Nama Project',
            'Nama Mitra / Customer',
            'Kategori',
            'Status',
            'Lokasi',
            'No. PO',
            'No. SO',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Certificate Project',
            'Deskripsi',
        ];
    }

    /**
     * Map each row of data for the export.
     */
    public function map($project): array
    {
        static $rowNumber = 0;
        $rowNumber++;

        return [
            $rowNumber,
            $project->name,
            $project->mitra?->nama ?? '-',
            $project->kategori ?? '-',
            $project->status,
            $project->lokasi ?? '-',
            $project->no_po ?? '-',
            $project->no_so ?? '-',
            $project->start_date ? $project->start_date->format('d/m/Y') : '-',
            $project->finish_date ? $project->finish_date->format('d/m/Y') : '-',
            $project->is_cert_projects ? 'Ya' : 'Tidak',
            $project->description ?? '-',
        ];
    }

    /**
     * Set the sheet title.
     */
    public function title(): string
    {
        return 'Data Project';
    }

    /**
     * Apply styles to the worksheet (bold header row).
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Bold the first (header) row
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
