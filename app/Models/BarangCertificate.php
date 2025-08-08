<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarangCertificate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'no_seri',
        'mitra_id',
    ];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'barang_certificate_id');
    }

    /**
     * Get activity name for logging
     */
    public function getActivityNameAttribute()
    {
        return $this->name ?? 'Barang Certificate #' . $this->id;
    }
}
