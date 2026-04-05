<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'no_certificate' => $this->no_certificate,
            'project_id' => $this->project_id,
            'barang_certificate_id' => $this->barang_certificate_id,
            'status' => $this->status,
            'date_of_issue' => $this->date_of_issue?->format('Y-m-d'),
            'date_of_expired' => $this->date_of_expired?->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'project' => $this->whenLoaded('project'),
            'barang_certificate' => $this->whenLoaded('barangCertificate'),
            'attachments' => $this->attachments,
        ];
    }
}
