<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mitra_id' => $this->mitra_id,
            'mitra' => $this->whenLoaded('mitra', function () {
                return [
                    'id' => $this->mitra->id,
                    'nama' => $this->mitra->nama,
                    'email' => $this->mitra->email,
                    'phone' => $this->mitra->phone,
                ];
            }),
            'kategori' => $this->kategori,
            'lokasi' => $this->lokasi,
            'status' => $this->status,
            'no_po' => $this->no_po,
            'no_so' => $this->no_so,
            'description' => $this->description,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'finish_date' => $this->finish_date?->format('Y-m-d'),
            'is_cert_projects' => $this->is_cert_projects,
            'cert_projects_label' => $this->is_cert_projects ? 'Certificate Project' : 'Regular Project',
            'activities_count' => $this->when($request->user()?->can('activity-view'), function () {
                return $this->activities_count ?? $this->activities()->count();
            }),
            'activities' => $this->when($request->user()?->can('activity-view') && $this->whenLoaded('activities'), function () {
                return ActivityResource::collection($this->activities);
            }),
            'certificates_count' => $this->whenCounted('certificates'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
