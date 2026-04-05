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

    public function getActivityNameAttribute()
    {
        return $this->name ?? 'Barang Certificate #' . $this->id;
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['mitra_id'] ?? null, function ($query, $mitraId) {
            $query->where('mitra_id', $mitraId);
        });

        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('no_seri', 'like', "%{$search}%")
                  ->orWhereHas('mitra', function ($q2) use ($search) {
                      $q2->where('nama', 'like', "%{$search}%");
                  });
            });
        });

        $sortBy  = $filters['sort_by'] ?? 'created';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc');
        $dir     = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        switch ($sortBy) {
            case 'created':
            default:
                $query->orderBy('id', $dir);
                break;
        }
    }
}
