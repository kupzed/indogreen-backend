<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateAttachment extends Model
{
    use HasFactory, LogsActivity;

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

    public function getActivityName(): string
    {
        return $this->name ?: 'Certificate Attachment #' . $this->id;
    }

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }
}
