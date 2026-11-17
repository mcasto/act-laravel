<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Show;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Brings every show/course poster in line with what ImageController::update()
 * produces — a JPEG at most 1200px wide in posters/ plus a 600px copy in
 * posters-sm/ — and deletes poster files nothing references.
 *
 * - Non-JPEG posters (old PNG uploads, often 1MB+) are converted to .jpg; every
 *   show/course using one is pointed at the new filename and the old files
 *   are deleted.
 * - Posters that already match are left untouched, so re-running it never
 *   re-compresses a JPEG (each re-save at quality 80 loses a little more).
 * - Soft-deleted shows/courses count as references, so their posters are kept.
 */
class OptimizePosters extends Command
{
    protected $signature = 'posters:optimize {--dry-run : List what would change without changing anything}';

    protected $description = 'Optimize poster images (1200px + 600px JPEGs) and delete any not referenced in the database';

    private const FULL_WIDTH = 1200;
    private const SMALL_WIDTH = 600;
    private const QUALITY = 80;

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $dryRun = (bool) $this->option('dry-run');
        $prefix = $dryRun ? '[dry run] ' : '';

        // filename (any ?t= cache-buster stripped) => every show/course using it
        $users = [];
        foreach ([Show::class, Course::class] as $class) {
            $class::withTrashed()->whereNotNull('poster')->where('poster', '!=', '')->get()
                ->each(function (Model $model) use (&$users) {
                    $users[basename(parse_url($model->poster, PHP_URL_PATH))][] = $model;
                });
        }

        $disk->makeDirectory('posters-sm');

        // ── Unreferenced files ────────────────────────────────────────────
        $deleted = 0;
        foreach ($disk->files('posters') as $relativePath) {
            $filename = basename($relativePath);
            if (! isset($users[$filename])) {
                $this->line("{$prefix}[DELETED] {$filename}");
                $dryRun || $disk->delete([$relativePath, "posters-sm/{$filename}"]);
                $deleted++;
            }
        }

        // Small copies whose full-size poster no longer exists.
        foreach ($disk->files('posters-sm') as $smallRelativePath) {
            if (! $disk->exists('posters/' . basename($smallRelativePath))) {
                $dryRun || $disk->delete($smallRelativePath);
            }
        }

        // ── Referenced posters ────────────────────────────────────────────
        $optimized = 0;
        $failed = 0;

        foreach ($users as $file => $models) {
            $fullPath = "posters/{$file}";
            $smallPath = "posters-sm/{$file}";

            if (! $disk->exists($fullPath)) {
                // Only worth flagging when a live show/course uses it — a
                // soft-deleted one missing its poster affects nothing.
                $live = array_filter($models, fn (Model $m) => ! $m->trashed());
                if ($live) {
                    $this->warn("[MISSING] {$file} (used by " . $this->describe($live) . ')');
                }
                continue;
            }

            try {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $convert = ! in_array($ext, ['jpg', 'jpeg'], true);
                $fullWidth = getimagesize($disk->path($fullPath))[0] ?? 0;
                $smallWidth = $disk->exists($smallPath) ? (getimagesize($disk->path($smallPath))[0] ?? 0) : null;

                $redoFull = $convert || $fullWidth > self::FULL_WIDTH;
                $redoSmall = $convert || $smallWidth === null || $smallWidth > self::SMALL_WIDTH;

                if (! $redoFull && ! $redoSmall) {
                    continue;
                }

                $newFile = $convert ? $this->jpegName($file) : $file;
                $line = sprintf('%s[OK] %s  %s → %s%s', $prefix, $file, $this->kb($disk->size($fullPath)), $newFile, $redoFull ? '' : ' (small copy only)');
                $optimized++;

                if ($dryRun) {
                    $this->line($line);
                    continue;
                }

                $image = Image::read($disk->path($fullPath));

                if ($redoFull) {
                    $image->scaleDown(width: self::FULL_WIDTH)->save($disk->path("posters/{$newFile}"), quality: self::QUALITY);
                }
                $image->scaleDown(width: self::SMALL_WIDTH)->save($disk->path("posters-sm/{$newFile}"), quality: self::QUALITY);

                if ($newFile !== $file) {
                    foreach ($models as $model) {
                        $model->poster = $newFile;
                        $model->save();
                    }
                    $disk->delete([$fullPath, $smallPath]);
                }

                $this->line($line . ' (now ' . $this->kb($disk->size("posters/{$newFile}"))
                    . ', small ' . $this->kb($disk->size("posters-sm/{$newFile}")) . ')');
            } catch (\Throwable $e) {
                $this->error("[FAIL] {$file}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info(($dryRun ? 'Dry run — nothing changed. Would optimize' : 'Done. Optimized') . ": {$optimized}, "
            . ($dryRun ? 'would delete' : 'Deleted') . ": {$deleted}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** foo.png → foo.jpg, or foo-<id>.jpg if foo.jpg is already taken. */
    private function jpegName(string $file): string
    {
        $base = pathinfo($file, PATHINFO_FILENAME);
        $name = "{$base}.jpg";

        return Storage::disk('public')->exists("posters/{$name}") ? "{$base}-" . uniqid() . '.jpg' : $name;
    }

    private function describe(array $models): string
    {
        return collect($models)->map(fn (Model $m) => class_basename($m) . " #{$m->id} {$m->name}")->join(', ');
    }

    private function kb(int $bytes): string
    {
        return round($bytes / 1024) . ' KB';
    }
}
