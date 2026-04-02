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
     * Scope a query to only include filtered projects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, array $filters)
    {
        // Filter status
        $query->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        });

        // Filter kategori
        $query->when($filters['kategori'] ?? null, function ($query, $kategori) {
            $query->where('kategori', $kategori);
        });

        // Filter customer (asumsi customer_id merujuk ke mitra_id yang is_customer=true)
        $query->when($filters['customer_id'] ?? null, function ($query, $customerId) {
            $query->where('mitra_id', $customerId);
        });

        // Filter is_cert_projects
        if (isset($filters['is_cert_projects'])) {
            $query->where('is_cert_projects', filter_var($filters['is_cert_projects'], FILTER_VALIDATE_BOOLEAN));
        }

        // Filter Date Range (start_date)
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('start_date', [$filters['date_from'], $filters['date_to']]);
        } elseif (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        } elseif (!empty($filters['date_to'])) {
            $query->where('start_date', '<=', $filters['date_to']);
        }

        // Search
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhere('lokasi', 'like', $like)
                  ->orWhere('no_po', 'like', $like)
                  ->orWhere('no_so', 'like', $like)
                  ->orWhereHas('mitra', function ($q2) use ($like) {
                      $q2->where('nama', 'like', $like);
                  });
            });
        });

        // Sorting
        $sortBy  = $filters['sort_by'] ?? 'created';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc');
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        if ($sortBy === 'start_date') {
            $query->orderBy('start_date', $sortDir)
                  ->orderBy('id', $sortDir);
        } else {
            $query->orderBy('id', $sortDir);
        }

        return $query;
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