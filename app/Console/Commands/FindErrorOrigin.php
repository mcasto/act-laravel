<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FindErrorOrigin extends Command
{
    protected $signature = 'log:origin {file?}';

    protected $description = 'Find the origin of errors in Laravel log files (excludes vendor paths)';

    public function handle()
    {
        $file = $this->argument('file') ?? storage_path('logs/laravel.log');

        if (!file_exists($file)) {
            $this->error("Log file not found: {$file}");
            return 1;
        }

        $log = file_get_contents($file);

        // Find all stack trace lines with file paths
        preg_match_all('/^#(\d+) ([^\(]+)\((\d+)\)/m', $log, $matches, PREG_SET_ORDER);

        $origins = [];
        $currentError = null;

        foreach ($matches as $match) {
            $lineNum = $match[1];
            $path = $match[2];
            $fileLine = $match[3];

            // Reset when we hit a new stack trace (#0)
            if ($lineNum === '0') {
                $currentError = null;
            }

            // Skip vendor paths
            if (str_contains($path, '/vendor/')) {
                continue;
            }

            // First non-vendor path is the origin
            if ($currentError === null) {
                $currentError = "{$path}:{$fileLine}";
                $origins[] = $currentError;
            }
        }

        if (empty($origins)) {
            $this->info('No error origins found (or all errors are in vendor code)');
            $this->newLine();
            return 0;
        }

        $this->warn('Error origins found:');
        $this->newLine();

        foreach (array_unique($origins) as $origin) {
            $this->info("  {$origin}");
        }

        $this->newLine();

        return 0;
    }
}
