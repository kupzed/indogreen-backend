<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'project_id' => $this->project_id,
            'jenis' => $this->jenis,
            'mitra_id' => $this->mitra_id,
            'kategori' => $this->kategori,
            'from' => $this->from,
            'to' => $this->to,
            'short_desc' => $this->short_desc,
            'description' => $this->description,
            'value' => $this->value,
            'activity_date' => $this->activity_date ? $this->activity_date->format('Y-m-d') : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'project' => $this->whenLoaded('project'),
            'mitra' => $this->whenLoaded('mitra'),
            'attachments' => $this->attachments,
        ];
    }
}
