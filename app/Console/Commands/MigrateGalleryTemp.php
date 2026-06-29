<?php

namespace App\Console\Commands;

use App\Models\GalleryImage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class MigrateGalleryTemp extends Command
{
    protected $signature = 'gallery:migrate-temp';
    protected $description = 'Move gallery-temp images to permanent gallery storage with proper naming';

    public function handle(): int
    {
        $records = GalleryImage::with('show')
            ->where('image', 'like', 'gallery-temp/%')
            ->get();

        if ($records->isEmpty()) {
            $this->info('No gallery-temp records found.');
            return 0;
        }

        $this->info("Found {$records->count()} records to migrate.");
        Storage::disk('public')->makeDirectory('gallery');

        $migrated = 0;
        $failed   = 0;

        foreach ($records as $record) {
            $tempPath = $record->image;

            if (!Storage::disk('public')->exists($tempPath)) {
                $this->warn("  [SKIP] File missing on disk: {$tempPath} (id={$record->id})");
                $failed++;
                continue;
            }

            $show     = $record->show;
            $showSlug = Str::slug($show->name);
            $showYear = Carbon::parse($show->ticket_sales_start)->year;
            $ext      = strtolower(pathinfo($tempPath, PATHINFO_EXTENSION)) ?: 'jpg';
            $filename = "gallery-{$showSlug}-{$showYear}-" . uniqid() . ".{$ext}";
            $permanentPath = "gallery/{$filename}";

            try {
                Image::read(Storage::disk('public')->path($tempPath))
                    ->scaleDown(width: 1920, height: 1920)
                    ->save(Storage::disk('public')->path($permanentPath), quality: 80);

                $record->image = $permanentPath;
                $record->save();

                Storage::disk('public')->delete($tempPath);

                $this->line("  [OK] id={$record->id} → {$permanentPath}");
                $migrated++;
            } catch (\Throwable $e) {
                $this->error("  [FAIL] id={$record->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Done. Migrated: {$migrated}, Failed/Skipped: {$failed}");
        return $failed > 0 ? 1 : 0;
    }
}
