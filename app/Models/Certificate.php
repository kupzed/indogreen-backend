<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Certificate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'no_certificate',
        'project_id',
        'barang_certificate_id',
        'status',
        'date_of_issue',
        'date_of_expired',
        'attachment',
    ];

    protected $casts = [
        'date_of_issue'   => 'date',
        'date_of_expired' => 'date',
    ];

    protected $appends = ['attachments'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function barangCertificate(): BelongsTo
    {
        return $this->belongsTo(BarangCertificate::class, 'barang_certificate_id');
    }

    public function getActivityNameAttribute()
    {
        return $this->name ?? 'Certificate #' . $this->id;
    }

    protected function formatBytes(?int $bytes): ?string
    {
        if ($bytes === null) return null;
        $units = ['bytes','KB','MB','GB','TB'];
        $i = 0; $num = (float) $bytes;
        while ($num >= 1024 && $i < count($units) - 1) { $num /= 1024; $i++; }
        $rounded = ($i === 0) ? round($num) : ($num < 10 ? number_format($num, 1) : (string) round($num));
        return $rounded . $units[$i];
    }

    protected function normalizePublicPath(string $path): string
    {
        $p = ltrim($path, '/');
        if (str_starts_with($p, 'public/')) {
            $p = substr($p, 7);
        }
        return $p;
    }

    protected function publicStorageUrl(string $rel): string
    {
        $base = config('filesystems.disks.public.url');
        if (!$base) {
            $base = URL::to('/storage');
        }
        return rtrim($base, '/') . '/' . ltrim($rel, '/');
    }

    public function getAttachmentsAttribute(): array
    {
        if (!$this->attachment) return [];

        if (preg_match('#^https?://#i', $this->attachment)) {
            return [[
                'path'      => $this->attachment,
                'name'      => basename(parse_url($this->attachment, PHP_URL_PATH) ?? $this->attachment),
                'size'      => null,
                'sizeLabel' => null,
                'url'       => $this->attachment,
            ]];
        }

        $rel  = $this->normalizePublicPath($this->attachment);
        $disk = Storage::disk('public');

        $exists = $disk->exists($rel);
        $size   = $exists ? $disk->size($rel) : null;

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
}
