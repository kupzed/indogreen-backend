<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    use HasFactory;

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
} 