<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateAttachment extends Model
{
    protected $fillable = [
        'certificate_id',
        'name',
        'description',
        'file_path',
        'mime',
        'size',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }
}
