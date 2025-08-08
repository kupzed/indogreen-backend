<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'no_certificate',
        'project_id',
        'barang_certificate_id',
        'status',
        'date_of_issue',
        'date_of_expired',
        'attachment',
    ];

    protected $casts = [
        'date_of_issue' => 'date',
        'date_of_expired' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function barangCertificate(): BelongsTo
    {
        return $this->belongsTo(BarangCertificate::class, 'barang_certificate_id');
    }

    /**
     * Get activity name for logging
     */
    public function getActivityNameAttribute()
    {
        return $this->name ?? 'Certificate #' . $this->id;
    }
}
