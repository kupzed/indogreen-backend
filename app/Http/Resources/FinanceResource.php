<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kategori' => $this->kategori,
            'jenis' => $this->jenis,
            'activity_date' => $this->activity_date ? $this->activity_date->format('Y-m-d') : null,
            'short_desc' => $this->short_desc,
            'description' => $this->description,
            'value' => (float) $this->value,
            'value_formatted' => 'Rp ' . number_format((float) $this->value, 0, ',', '.'),

            'project' => $this->project ? [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ] : null,

            'mitra' => $this->mitra ? [
                'id' => $this->mitra->id,
                'nama' => $this->mitra->nama,
            ] : null,

            'attachments' => $this->attachments,
        ];
    }
}
