<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Show;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupStorageOrphans extends Command
{
    protected $signature = 'storage:cleanup-orphans';
    protected $description = 'Delete orphaned poster files and stale gallery-temp uploads';

    public function handle(): int
    {
        $posterCount = $this->cleanPosters();
        $tempCount   = $this->cleanGalleryTemp();

        $this->info("Done. Posters deleted: {$posterCount}, Gallery-temp deleted: {$tempCount}");
        return 0;
    }

    private function cleanPosters(): int
    {
        $dbPosters = Show::withTrashed()->pluck('poster')
            ->merge(Course::withTrashed()->pluck('poster'))
            ->filter()
            ->map(fn($p) => basename(parse_url($p, PHP_URL_PATH)))
            ->unique()
            ->all();

        $deleted = 0;
        foreach (Storage::disk('public')->files('posters') as $path) {
            if (!in_array(basename($path), $dbPosters)) {
                Storage::disk('public')->delete($path);
                $this->line("  [POSTER] deleted: " . basename($path));
                $deleted++;
            }
        }

        return $deleted;
    }

    private function cleanGalleryTemp(): int
    {
        // Any file older than 1 hour is a failed/abandoned upload — the
        // new pipeline deletes temp files immediately on success.
        $cutoff  = now()->subHour()->timestamp;
        $deleted = 0;

        foreach (Storage::disk('public')->files('gallery-temp') as $path) {
            if (Storage::disk('public')->lastModified($path) < $cutoff) {
                Storage::disk('public')->delete($path);
                $this->line("  [GALLERY-TEMP] deleted: " . basename($path));
                $deleted++;
            }
        }

        return $deleted;
    }
}
