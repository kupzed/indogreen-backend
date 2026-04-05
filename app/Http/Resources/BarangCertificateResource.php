<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangCertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'no_seri' => $this->no_seri,
            'mitra_id' => $this->mitra_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'mitra' => $this->whenLoaded('mitra', function() {
                return [
                    'id' => $this->mitra->id,
                    'nama' => $this->mitra->nama,
                ];
            }),
            'certificates' => CertificateResource::collection($this->whenLoaded('certificates')),
        ];
    }
}
