<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BackfillAttachments extends Command
{
    protected $signature = 'backfill:attachments
        {--only=all : activities|certificates|all}
        {--chunk=200 : Chunk size per batch}
        {--disk=public : Storage disk source (default: public)}
        {--dry-run : Simulate only, no DB writes}
        {--null-legacy : Set legacy `attachment` column to NULL after successful copy}';

    protected $description = 'Backfill legacy `attachment` column to *_attachments tables (idempotent & safe).';

    public function handle(): int
    {
        $only     = strtolower((string)$this->option('only') ?: 'all');
        $chunk    = (int) $this->option('chunk');
        $disk     = (string) $this->option('disk');
        $dryRun   = (bool) $this->option('dry-run');
        $nullOld  = (bool) $this->option('null-legacy');

        if (!in_array($only, ['all','activities','certificates'], true)) {
            $this->error('Invalid --only value. Use: activities|certificates|all');
            return self::FAILURE;
        }

        $this->info('=== Backfill Attachments ===');
        $this->line("Options: only={$only}, chunk={$chunk}, disk={$disk}, dry-run=" . ($dryRun ? 'yes' : 'no') . ", null-legacy=" . ($nullOld ? 'yes' : 'no'));
        $this->newLine();

        if ($only === 'all' || $only === 'activities') {
            $this->backfillActivities($disk, $chunk, $dryRun, $nullOld);
            $this->newLine();
        }

        if ($only === 'all' || $only === 'certificates') {
            $this->backfillCertificates($disk, $chunk, $dryRun, $nullOld);
            $this->newLine();
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    /* ========================= ACTIVITIES ========================= */

    protected function backfillActivities(string $disk, int $chunk, bool $dryRun, bool $nullOld): void
    {
        $this->info('Backfilling: activities -> activity_attachments');

        if (!Schema::hasTable('activities')) {
            $this->warn('Table `activities` not found. Skipped.');
            return;
        }
        if (!Schema::hasTable('activity_attachments')) {
            $this->warn('Table `activity_attachments` not found. Skipped.');
            return;
        }
        if (!Schema::hasColumn('activities', 'attachment')) {
            $this->warn('Column `activities.attachment` not found. (Maybe already dropped) Skipped.');
            return;
        }

        $total = DB::table('activities')->whereNotNull('attachment')->count();
        if ($total === 0) {
            $this->line('No legacy attachments to backfill.');
            return;
        }

        $this->line("Found {$total} activities with legacy attachment.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $stats = ['inserted' => 0, 'skipped' => 0, 'missing' => 0, 'external' => 0, 'errors' => 0];

        DB::table('activities')
            ->select(['id', 'attachment', 'created_at', 'updated_at'])
            ->whereNotNull('attachment')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($disk, $dryRun, $nullOld, &$stats, $bar) {
                foreach ($rows as $row) {
                    $raw = $row->attachment;
                    $bar->advance();

                    if (!$raw) { $stats['skipped']++; continue; }

                    // Lewati URL eksternal
                    if (preg_match('#^https?://#i', $raw)) {
                        $stats['external']++;
                        continue;
                    }

                    // Bisa jadi JSON array/string; kita tangani keduanya
                    $paths = $this->normalizeLegacyField($raw);
                    foreach ($paths as $p) {
                        $rel = $this->normalizePublicPath($p);

                        // Idempotent check
                        $exists = DB::table('activity_attachments')
                            ->where('activity_id', $row->id)
                            ->where('file_path', $rel)
                            ->exists();
                        if ($exists) { $stats['skipped']++; continue; }

                        [$size, $mime, $existsFile] = $this->probeFile($disk, $rel);
                        if (!$existsFile) { $stats['missing']++; continue; }

                        $name = basename($rel);
                        if (mb_strlen($name) > 60) $name = mb_substr($name, 0, 60);

                        try {
                            if (!$dryRun) {
                                DB::table('activity_attachments')->insert([
                                    'activity_id' => $row->id,
                                    'name'        => $name,
                                    'description' => null,
                                    'file_path'   => $rel,
                                    'mime'        => $mime,
                                    'size'        => $size,
                                    'created_at'  => $row->created_at,
                                    'updated_at'  => $row->updated_at,
                                ]);
                            }
                            $stats['inserted']++;
                        } catch (\Throwable $e) {
                            $stats['errors']++;
                            Log::warning('Insert activity_attachments failed: ' . $e->getMessage(), ['activity_id' => $row->id, 'path' => $rel]);
                        }
                    }

                    // Set NULL kolom lama jika diminta dan tidak error
                    if ($nullOld && !$dryRun) {
                        try {
                            DB::table('activities')->where('id', $row->id)->update(['attachment' => null]);
                        } catch (\Throwable $e) {
                            $stats['errors']++;
                            Log::warning('Nulling legacy attachment failed: ' . $e->getMessage(), ['activity_id' => $row->id]);
                        }
                    }
                }
            });

        $bar->finish(); $this->newLine();
        $this->table(['inserted','skipped','missing','external','errors'], [array_values($stats)]);
    }

    /* ========================= CERTIFICATES ========================= */

    protected function backfillCertificates(string $disk, int $chunk, bool $dryRun, bool $nullOld): void
    {
        $this->info('Backfilling: certificates -> certificate_attachments');

        if (!Schema::hasTable('certificates')) {
            $this->warn('Table `certificates` not found. Skipped.');
            return;
        }
        if (!Schema::hasTable('certificate_attachments')) {
            $this->warn('Table `certificate_attachments` not found. Skipped.');
            return;
        }
        if (!Schema::hasColumn('certificates', 'attachment')) {
            $this->warn('Column `certificates.attachment` not found. (Maybe already dropped) Skipped.');
            return;
        }

        $total = DB::table('certificates')->whereNotNull('attachment')->count();
        if ($total === 0) {
            $this->line('No legacy attachments to backfill.');
            return;
        }

        $this->line("Found {$total} certificates with legacy attachment.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $stats = ['inserted' => 0, 'skipped' => 0, 'missing' => 0, 'external' => 0, 'errors' => 0];

        DB::table('certificates')
            ->select(['id', 'attachment', 'created_at', 'updated_at'])
            ->whereNotNull('attachment')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($disk, $dryRun, $nullOld, &$stats, $bar) {
                foreach ($rows as $row) {
                    $raw = $row->attachment;
                    $bar->advance();

                    if (!$raw) { $stats['skipped']++; continue; }

                    if (preg_match('#^https?://#i', $raw)) {
                        $stats['external']++;
                        continue;
                    }

                    $paths = $this->normalizeLegacyField($raw);
                    foreach ($paths as $p) {
                        $rel = $this->normalizePublicPath($p);

                        $exists = DB::table('certificate_attachments')
                            ->where('certificate_id', $row->id)
                            ->where('file_path', $rel)
                            ->exists();
                        if ($exists) { $stats['skipped']++; continue; }

                        [$size, $mime, $existsFile] = $this->probeFile($disk, $rel);
                        if (!$existsFile) { $stats['missing']++; continue; }

                        $name = basename($rel);
                        if (mb_strlen($name) > 60) $name = mb_substr($name, 0, 60);

                        try {
                            if (!$dryRun) {
                                DB::table('certificate_attachments')->insert([
                                    'certificate_id' => $row->id,
                                    'name'           => $name,
                                    'description'    => null,
                                    'file_path'      => $rel,
                                    'mime'           => $mime,
                                    'size'           => $size,
                                    'created_at'     => $row->created_at,
                                    'updated_at'     => $row->updated_at,
                                ]);
                            }
                            $stats['inserted']++;
                        } catch (\Throwable $e) {
                            $stats['errors']++;
                            Log::warning('Insert certificate_attachments failed: ' . $e->getMessage(), ['certificate_id' => $row->id, 'path' => $rel]);
                        }
                    }

                    if ($nullOld && !$dryRun) {
                        try {
                            DB::table('certificates')->where('id', $row->id)->update(['attachment' => null]);
                        } catch (\Throwable $e) {
                            $stats['errors']++;
                            Log::warning('Nulling legacy attachment failed: ' . $e->getMessage(), ['certificate_id' => $row->id]);
                        }
                    }
                }
            });

        $bar->finish(); $this->newLine();
        $this->table(['inserted','skipped','missing','external','errors'], [array_values($stats)]);
    }

    /* ========================= HELPERS ========================= */

    /**
     * Legacy field bisa string path atau JSON array of paths.
     *
     * @return string[]
     */
    protected function normalizeLegacyField(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];

        // Coba decode JSON array
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            // ambil string saja
            return array_values(array_filter($decoded, fn($v) => is_string($v) && $v !== ''));
        }

        return [$raw];
    }

    protected function normalizePublicPath(string $path): string
    {
        $p = ltrim($path, '/');
        if (str_starts_with($p, 'public/')) {
            $p = substr($p, 7);
        }
        return $p;
    }

    protected function absolutePublicPath(string $rel): string
    {
        return storage_path('app/public/' . ltrim($rel, '/'));
    }

    /**
     * @return array{0:?int,1:?string,2:bool} [$size, $mime, $exists]
     */
    protected function probeFile(string $disk, string $rel): array
    {
        $size = null; $mime = null; $exists = false;

        try {
            $fs = Storage::disk($disk);
            if ($fs->exists($rel)) {
                $exists = true;
                // Size
                try { $size = $fs->size($rel); } catch (\Throwable $e) {
                    Log::warning('size failed: '.$e->getMessage(), ['path' => $rel]);
                }
                // MIME (kompatibel lint & runtime)
                $mime = $this->guessMime($fs, $rel);
            }
        } catch (\Throwable $e) {
            Log::warning('probeFile disk ops failed: '.$e->getMessage(), ['path' => $rel]);
        }

        if (!$exists) {
            // Coba cek via absolute path (kadang-kadang mismatch disk)
            $abs = $this->absolutePublicPath($rel);
            if (is_file($abs)) {
                $exists = true;
                $size = @filesize($abs) ?: $size;
                $mime = $mime ?? $this->guessMimeViaFileinfo($abs);
            }
        }

        return [$size, $mime, $exists];
    }

    /**
     * Deteksi MIME dengan fallback berbagai versi Flysystem.
     */
    protected function guessMime($fs, string $rel): ?string
    {
        try {
            if (method_exists($fs, 'mimeType')) {
                return $fs->mimeType($rel);
            }
            if (method_exists($fs, 'getMimetype')) {
                /** @noinspection PhpUndefinedMethodInspection */
                $m = $fs->getMimetype($rel);
                return $m ?: null;
            }
        } catch (\Throwable $e) {
            Log::warning('mime detection via disk failed: '.$e->getMessage(), ['path' => $rel]);
        }

        $abs = $this->absolutePublicPath($rel);
        return $this->guessMimeViaFileinfo($abs);
    }

    protected function guessMimeViaFileinfo(string $abs): ?string
    {
        if (!is_file($abs)) return null;
        if (!function_exists('finfo_open')) return null;

        $f = @finfo_open(FILEINFO_MIME_TYPE);
        if (!$f) return null;

        $m = @finfo_file($f, $abs) ?: null;
        @finfo_close($f);
        return $m ?: null;
    }
}
