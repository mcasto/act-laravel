<?php

namespace App\Console\Commands;

use App\Http\Controllers\FixrWebhooksController;
use App\Models\Angel;
use App\Models\AngelLevel;
use App\Models\FixrWebhookResponse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Re-run Fixr webhooks that failed, from the raw copies the webhook
 * controller saves to storage/app/private/logs/ before processing.
 *
 * Written for the Angel donations lost to the founding_angel NOT NULL bug
 * (fixed in FixrWebhooksController) — run only after that fix is deployed.
 * Already-processed message_ids are skipped by the controller's duplicate
 * check, so re-running is safe.
 */
class ReplayFixrWebhooks extends Command
{
    protected $signature = 'fixr:replay-webhooks
                            {files?* : Log filenames in storage/app/private/logs (defaults to the three failed Angel donations)}
                            {--angel-level= : Record as this Angel level ID — for webhooks whose Fixr event no longer matches the level current Fixr link}
                            {--dry-run : Show what would be replayed without changing anything}';

    protected $description = 'Replay saved Fixr webhook payloads that failed to process';

    const DEFAULT_FILES = [
        'fixr_webhook_2026_10_28_20_34_37.log', // Ward & Nancy Smyth — $250 Archangel
        'fixr_webhook_2026_11_16_11_40_45.log', // John Laurich — $100 Angel
        'fixr_webhook_2026_11_20_18_32_19.log', // Nancy Lander — $500 Guardian Angel
    ];

    public function handle(FixrWebhooksController $controller): int
    {
        $files = $this->argument('files') ?: self::DEFAULT_FILES;
        $dryRun = $this->option('dry-run');
        $failed = 0;

        // The controller matches on payload.event_id vs. the trailing digits
        // of the level's fixr_link. If the level has since moved to a new
        // Fixr event, retarget the payload at the current one — keeping the
        // original id in the payload so the stored record shows what happened.
        $retargetEventId = null;

        if ($levelId = $this->option('angel-level')) {
            $level = AngelLevel::find($levelId);
            $path = $level ? parse_url((string) $level->fixr_link, PHP_URL_PATH) : null;

            if (! $path || ! preg_match('/[0-9]+$/', $path, $m)) {
                $this->error("Angel level {$levelId} not found or has no usable Fixr link");
                return self::FAILURE;
            }

            $retargetEventId = $m[0];
            $this->line("Recording as Angel level {$level->id} ({$level->label}, \${$level->min_amount}) — Fixr event {$retargetEventId}");
        }

        foreach ($files as $file) {
            $path = 'logs/' . basename($file);

            if (! Storage::disk('local')->exists($path)) {
                $this->error("{$file}: not found");
                $failed++;
                continue;
            }

            $body = json_decode(Storage::disk('local')->get($path), true)['body'] ?? null;
            $data = $body ? json_decode($body, true) : null;

            if (! $data || empty($data['message_id'])) {
                $this->error("{$file}: no usable webhook body");
                $failed++;
                continue;
            }

            $holder = $data['payload']['ticket_holders'][0] ?? [];
            $label = sprintf(
                '%s %s — %s (%s)',
                $holder['first_name'] ?? '?',
                $holder['last_name'] ?? '?',
                $data['payload']['event_name'] ?? 'unknown event',
                $data['payload']['sold_at'] ?? '?'
            );

            if (FixrWebhookResponse::where('message_id', $data['message_id'])->exists()) {
                $this->line("{$file}: already processed, skipping — {$label}");
                continue;
            }

            if ($retargetEventId && (string) $data['payload']['event_id'] !== $retargetEventId) {
                $data['payload']['replayed_original_event_id'] = $data['payload']['event_id'];
                $data['payload']['event_id'] = $retargetEventId;
                $body = json_encode($data);
            }

            if ($dryRun) {
                $this->info("{$file}: would replay — {$label}");
                continue;
            }

            $request = Request::create('/api/fixr-webhooks', 'POST', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], $body);

            $response = $controller->create($request);
            $result = $response->getData(true);

            if ($response->getStatusCode() !== 200 || ($result['status'] ?? null) !== 'success') {
                $this->error("{$file}: replay failed — " . json_encode($result));
                $failed++;
                continue;
            }

            // Record the donation at when it was actually made, not now.
            if (isset($result['angel_id'])) {
                $soldAt = Carbon::parse($data['payload']['sold_at'])->utc();
                $angel = Angel::find($result['angel_id']);
                $angel->timestamps = false;
                $angel->forceFill(['created_at' => $soldAt, 'updated_at' => $soldAt])->save();
            }

            $this->info("{$file}: replayed — {$label} → " . json_encode($result));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
