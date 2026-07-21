<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Show;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class OptimizePosters extends Command
{
    protected $signature = 'posters:optimize';
    protected $description = 'Optimize all poster images in place and delete any not referenced in the database';

    public function handle(): int
    {
        // Collect all poster filenames referenced in DB, stripping any ?t= cache-busting suffixes
        $dbPosters = Show::withTrashed()->pluck('poster')
            ->merge(Course::withTrashed()->pluck('poster'))
            ->filter()
            ->map(fn($p) => basename(parse_url($p, PHP_URL_PATH)))
            ->unique()
            ->values()
            ->all();

        Storage::disk('public')->makeDirectory('posters-sm');

        $diskFiles = Storage::disk('public')->files('posters');

        // Clean up any small-variant files whose full-size poster no longer exists
        $currentFilenames = collect($diskFiles)->map(fn($p) => basename($p))->all();
        foreach (Storage::disk('public')->files('posters-sm') as $smallRelativePath) {
            if (!in_array(basename($smallRelativePath), $currentFilenames)) {
                Storage::disk('public')->delete($smallRelativePath);
            }
        }

        $deleted  = 0;
        $optimized = 0;
        $failed   = 0;

        foreach ($diskFiles as $relativePath) {
            $filename     = basename($relativePath);
            $absPath      = Storage::disk('public')->path($relativePath);
            $smallAbsPath = Storage::disk('public')->path("posters-sm/{$filename}");

            if (!in_array($filename, $dbPosters)) {
                Storage::disk('public')->delete($relativePath);
                Storage::disk('public')->delete("posters-sm/{$filename}");
                $this->line("  [DELETED] {$filename}");
                $deleted++;
                continue;
            }

            try {
                $posterImage = Image::read($absPath);

                $posterImage->scaleDown(width: 1200)->save($absPath, quality: 80);
                $posterImage->scaleDown(width: 600)->save($smallAbsPath, quality: 80);

                $this->line("  [OK] {$filename}");
                $optimized++;
            } catch (\Throwable $e) {
                $this->error("  [FAIL] {$filename}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Done. Optimized: {$optimized}, Deleted: {$deleted}, Failed: {$failed}");
        return $failed > 0 ? 1 : 0;
    }
}
