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
        'description',
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

        if (!$this->attachment) return [];

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
