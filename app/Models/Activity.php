<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'project_id',
        'kategori',
        'activity_date',
        'attachment',
        'jenis',
        // 'customer_id', // Hapus ini jika tidak lagi digunakan
        'mitra_id',
        'from', // Tambahkan ini
        'to',   // Tambahkan ini
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Hapus relasi customer() ini jika Activity.customer_id tidak lagi ada
    // public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    // {
    //     return $this->belongsTo(\App\Models\Customer::class);
    // }

    public function mitra(): BelongsTo // Tambahkan tipe hint untuk konsistensi
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }
}