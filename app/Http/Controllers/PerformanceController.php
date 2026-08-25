<?php

namespace App\Http\Controllers;

use App\Mail\SoldOutNotificationMailer;
use App\Models\Performance;
use App\Models\SoldOutNotificationRecipient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PerformanceController extends Controller
{
    /**
     * Upsert multiple performance records
     *
     * Handles batch creation, updates, and deletions of performance records.
     * Records marked with 'deleted' property are removed from the database.
     * Records without 'deleted' property are created or updated based on
     * whether they have an 'id' field.
     *
     * In the frontend, users can delete existing records or add new ones and
     * then delete them before submitting. The 'deleted' property tracks these
     * operations without immediately affecting the database.
     *
     * @param Request $request Contains 'performances' array with performance data
     * @return JsonResponse Count of deleted and upserted records
     *
     * @source Database Model: Performance (creates, updates, deletes)
     */
    public function upsert(Request $request): JsonResponse
    {
        $performances = $request->input('performances');

        // find records that have deleted property *and* an id property, which means they need to be deleted from the database
        $deleteRecs = array_filter($performances, function ($performance) {
            return isset($performance['deleted']) && isset($performance['id']);
        });

        // delete those from the database
        foreach ($deleteRecs as $rec) {
            $performance = Performance::find($rec['id']);
            if ($performance) {
                $performance->delete();
            }
        }

        // find records that don't have a deleted property, which means they need to be upserted
        $upserts = array_filter($performances, function ($performance) {
            return ! isset($performance['deleted']);
        });

        $upserts = collect($performances)
            ->filter(function ($performance) {
                return ! isset($performance['deleted']);
            })
            ->map(function ($performance) {
                $performance['sold_out_target'] = $performance['sold_out_target'] ?? 50;

                return $performance;
            });

        $newlySoldOut = [];

        foreach ($upserts as $upsert) {
            $validatedData = Performance::validate($upsert);

            if (isset($validatedData['show_id'])) {
                // Validation passed
                $performance = isset($validatedData['id']) ? Performance::find($validatedData['id']) : new Performance();

                if (! $performance) {
                    $performance = new Performance();
                }

                // Whether it was sold out *before* this save, so we can tell
                // apart "just got marked sold out" from "already was" — only
                // the former should ever prompt to send a notification.
                $wasSoldOut = $performance->exists && (bool) $performance->sold_out;

                $performance->fill($validatedData);

                $performance->save(); // Laravel handles updated_at automatically

                if (! $wasSoldOut && (bool) $performance->sold_out) {
                    $newlySoldOut[] = $performance;
                }
            } else {
                Log::warning('Performance validation failed', ['errors' => $validatedData, 'record' => $upsert]);
            }
        }

        return response()->json([
            'deleted' => count($deleteRecs),
            'upserted' => count($upserts),
            'newly_sold_out' => collect($newlySoldOut)->map(fn ($p) => [
                'id' => $p->id,
                'show_name' => $p->show->name,
                'label' => "{$p->show->name} — {$p->formatted_date} {$p->formatted_time}",
            ]),
        ]);
    }

    /**
     * Emails everyone on the sold-out notification list about one or more
     * newly-sold-out performances. A deliberate, separate step from
     * upsert() above — never fired automatically, only when an admin
     * explicitly confirms it after seeing which performances just changed.
     */
    public function sendSoldOutNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'performance_ids' => 'required|array|min:1',
            'performance_ids.*' => 'integer|exists:performances,id',
        ]);

        $performances = Performance::with('show')->whereIn('id', $validated['performance_ids'])->get();
        $recipients = SoldOutNotificationRecipient::all();

        $sent = 0;
        $failed = 0;

        foreach ($performances as $performance) {
            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)->send(new SoldOutNotificationMailer($performance));
                    $sent++;
                } catch (Exception $e) {
                    $failed++;
                    logger()->error('Failed to send sold-out notification', [
                        'performance_id' => $performance->id,
                        'recipient_id' => $recipient->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json(['status' => 'success', 'sent' => $sent, 'failed' => $failed]);
    }

    /**
     * Update Fixr link for a performance
     *
     * Placeholder method that currently only logs the request.
     * Intended for updating the Fixr ticketing link associated with a performance.
     *
     * @param int $id The performance ID
     * @param Request $request Contains the Fixr link data
     * @return void
     *
     * @source None (only logs)
     * @todo Implement actual Fixr link update functionality
     */
    public function updateFixrLink(int $id, Request $request)
    {
        logger()->info('update-fixr-link', ['id' => $id, 'request' => $request->all()]);
    }
}
