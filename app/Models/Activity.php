<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Activity extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'short_desc',
        'description',
        'value',
        'project_id',
        'kategori',
        'activity_date',
        'attachment',
        'jenis',
        'mitra_id',
        'from',
        'to',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'value'         => 'decimal:2',
    ];

    protected $appends = ['attachments'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ActivityAttachment::class);
    }

    public function getActivityNameAttribute()
    {
        return $this->name ?? 'Activity #' . $this->id;
    }

    protected function formatBytes(?int $bytes): ?string
    {
        if ($bytes === null) return null;
        $units = ['bytes','KB','MB','GB','TB'];
        $i = 0; $num = (float) $bytes;
        // Mengonversi angka byte murni ke satuan yang lebih mudah dibaca (KB/MB/GB)
        while ($num >= 1024 && $i < count($units) - 1) { $num /= 1024; $i++; }
        $rounded = ($i === 0) ? round($num) : ($num < 10 ? number_format($num, 1) : (string) round($num));
        return $rounded . $units[$i];
    }

    protected function normalizePublicPath(string $path): string
    {
        // Menstandarisasi path file (menghapus prefix 'public/') untuk pencocokan filesystem Laravel
        $p = ltrim($path, '/');
        if (str_starts_with($p, 'public/')) {
            $p = substr($p, 7);
        }
        return $p;
    }

    protected function publicStorageUrl(string $rel): string
    {
        // Mendapatkan URL absolut file yang bisa diakses publik (fallback ke /storage jika env tidak diatur)
        $base = config('filesystems.disks.public.url');
        if (!$base) {
            $base = URL::to('/storage');
        }
        return rtrim($base, '/') . '/' . ltrim($rel, '/');
    }

    public function getAttachmentsAttribute(): array
    {
        /**
         * Logika Transisi: Sistem ini mendukung dua cara penyimpanan attachment:
         * 1. Cara Baru: Relasi HasMany ke tabel 'activity_attachments'.
         * 2. Cara Lama: Path file tunggal yang disimpan di kolom 'attachment'.
         */
        $atts = $this->getRelationValue('attachments') ?? $this->attachments()->get();
        if ($atts && $atts->count() > 0) {
            return $atts->map(fn($att) => [
                'id'          => $att->id,
                'name'        => $att->name ?: basename($att->file_path),
                'description' => $att->description,
                'size'        => $att->size,
                'sizeLabel'   => $att->size ? $this->formatBytes($att->size) : null,
                'path'        => $att->file_path,
                'url'         => asset('storage/'.$att->file_path),
            ])->all();
        }

        // Jika tidak ada data di tabel relasi, cek apakah ada file di kolom legacy 'attachment'
        if (!$this->attachment) return [];

        $rel  = $this->normalizePublicPath($this->attachment);
        $disk = Storage::disk('public');

        $exists = $disk->exists($rel);
        $size   = $exists ? $disk->size($rel) : null;

        // Fallback jika database mencatat file ada tapi Storage API gagal (cek path fisik)
        if ($size === null) {
            $abs = storage_path('app/public/' . $rel);
            if (is_file($abs)) {
                $size = @filesize($abs) ?: null;
            }
        }

        return [[
            'path'      => $rel,
            'name'      => basename($rel),
            'size'      => $size,
            'sizeLabel' => $this->formatBytes($size),
            'url'       => $this->publicStorageUrl($rel),
        ]];
    }

    public function scopeFilter($query, array $filters)
    {
        // Filter standar berdasarkan ID dan Enum
        $query->when($filters['project_id'] ?? null, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })
        ->when($filters['jenis'] ?? null, function ($query, $jenis) {
            $query->where('jenis', $jenis);
        })
        ->when($filters['kategori'] ?? null, function ($query, $kategori) {
            $query->where('kategori', $kategori);
        })
        ->when($filters['mitra_id'] ?? null, function ($query, $mitraId) {
            $query->where('mitra_id', $mitraId);
        });

        // Filter rentang tanggal (mendukung date_from saja, date_to saja, atau keduanya)
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('activity_date', [$filters['date_from'], $filters['date_to']]);
        } elseif (!empty($filters['date_from'])) {
            $query->where('activity_date', '>=', $filters['date_from']);
        } elseif (!empty($filters['date_to'])) {
            $query->where('activity_date', '<=', $filters['date_to']);
        }

        // Pencarian global (fuzzy search) ke berbagai kolom dan tabel relasi (Project & Mitra)
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('short_desc', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhereHas('project', function ($q2) use ($like) {
                      $q2->where('name', 'like', $like);
                  })
                  ->orWhereHas('mitra', function ($q3) use ($like) {
                      $q3->where('nama', 'like', $like);
                  });
            });
        });

        $sortBy  = $filters['sort_by'] ?? 'created';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc');
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        // Sorting khusus: Jika sorting berdasarkan tanggal, tambahkan ID sebagai tie-breaker agar pagination stabil
        if ($sortBy === 'activity_date') {
            $query->orderBy('activity_date', $sortDir)
                  ->orderBy('id', $sortDir); 
        } else {
            $query->orderBy('id', $sortDir);
        }

        return $query;
    }
}
