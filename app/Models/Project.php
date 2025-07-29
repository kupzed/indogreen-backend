<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
        'finish_date',
        'mitra_id', // Pastikan ini ada di fillable
    ];

    protected $casts = [
        'start_date' => 'date',
        'finish_date' => 'date',
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function mitra(): BelongsTo // Tambahkan tipe hint
    {
        // Pastikan foreign key di tabel projects adalah 'mitra_id'
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    /**
     * Get activity name for logging
     */
    public function getActivityNameAttribute()
    {
        return $this->name ?? 'Project #' . $this->id;
    }
}