<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Mitra extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'nama',
        'is_pribadi',
        'is_perusahaan',
        'is_customer',
        'is_vendor',
        'alamat',
        'website',
        'email',
        'kontak_1',
        'kontak_1_nama',
        'kontak_1_jabatan',
        'kontak_2_nama',
        'kontak_2',
        'kontak_2_jabatan',
    ];

    protected $table = 'partners';

    public function projects()
    {
        return $this->hasMany(Project::class, 'mitra_id');
    }

    public function barangCertificates(): HasMany
    {
        return $this->hasMany(BarangCertificate::class, 'mitra_id');
    }

    public function getActivityNameAttribute()
    {
        return $this->nama ?? 'Mitra #' . $this->id;
    }

    public function scopeFilter(Builder $query, array $filters)
    {
        $query->when($filters['kategori'] ?? null, function ($query, $kategori) {
            if (in_array(strtolower($kategori), ['pribadi', 'perusahaan', 'customer', 'vendor'])) {
                $query->where('is_' . strtolower($kategori), true);
            }
        });

        $query->when($filters['date_from'] ?? null, function ($query, $dateFrom) use ($filters) {
            $dateTo = $filters['date_to'] ?? null;
            if ($dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            } else {
                $query->where('created_at', '>=', $dateFrom);
            }
        });

        $query->when(!isset($filters['date_from']) && isset($filters['date_to']), function ($query) use ($filters) {
            $query->where('created_at', '<=', $filters['date_to']);
        });

        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('alamat', 'like', "%$search%")
                  ->orWhere('website', 'like', "%$search%");
            });
        });
    }
} 