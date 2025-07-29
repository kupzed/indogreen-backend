<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Get activity name for logging
     */
    public function getActivityNameAttribute()
    {
        return $this->nama ?? 'Mitra #' . $this->id;
    }
} 