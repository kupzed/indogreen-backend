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
        'mitra_id',
        'kategori',
        'lokasi',
        'no_po',
        'no_so',
        'is_cert_projects',
    ];

    protected $casts = [
        'start_date' => 'date',
        'finish_date' => 'date',
        'is_cert_projects' => 'boolean',
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get activity name for logging
     */
    public function getActivityNameAttribute()
    {
        return $this->name ?? 'Project #' . $this->id;
    }

    /**
     * Scope to filter certificate projects
     */
    public function scopeCertProjects($query)
    {
        return $query->where('is_cert_projects', true);
    }

    /**
     * Scope to filter non-certificate projects
     */
    public function scopeNonCertProjects($query)
    {
        return $query->where('is_cert_projects', false);
    }

    /**
     * Check if project is a certificate project
     */
    public function isCertProject(): bool
    {
        return $this->is_cert_projects;
    }

    /**
     * Toggle certificate project status
     */
    public function toggleCertProject(): bool
    {
        $this->update(['is_cert_projects' => !$this->is_cert_projects]);
        return $this->is_cert_projects;
    }
}