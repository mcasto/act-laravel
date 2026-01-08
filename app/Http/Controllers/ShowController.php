<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\SiteConfig;
use App\Models\StandardButton;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShowController extends Controller
{
    /**
     * Determine start and end dates of current theater season
     *
     * Theater seasons run from October 1 to August 31. This method calculates
     * which season we're currently in based on the current date and returns
     * the start and end dates of that season.
     *
     * @return array Start and end dates as strings ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     *
     * @source None (utility method)
     */
    private function getTheaterSeasonDates(): array
    {
        $now = Carbon::now();

        // Define season start and end months
        $seasonStartMonth = 10; // October
        $seasonEndMonth   = 8;  // August

        // Determine the season's start year
        if ($now->month >= $seasonStartMonth) {
            // If current month is October or later, we're in the current season
            $startYear = $now->year;
        } else {
            // If we're in September or earlier, we're still in the previous season
            $startYear = $now->year - 1;
        }

        // Define the start and end dates of the season
        $seasonStart = Carbon::create($startYear, $seasonStartMonth, 1, 0, 0, 0);
        $seasonEnd   = Carbon::create($startYear + 1, $seasonEndMonth, 31, 23, 59, 59);

        // If we're in September, return the next season
        if ($now->month == 9) {
            $seasonStart = $seasonStart->addYear();
            $seasonEnd   = $seasonEnd->addYear();
        }

        return [
            'start' => $seasonStart->toDateString(),
            'end'   => $seasonEnd->toDateString(),
        ];
    }

    /**
     * Check if poster and slug are unique among non-deleted records
     *
     * Validates that the poster and slug values don't already exist
     * in the database for records that haven't been soft-deleted.
     *
     * @param array $rec Record data containing 'poster' and 'slug'
     * @return array Boolean values for uniqueness ['poster' => bool, 'slug' => bool]
     *
     * @source Database Model: Show (reads for uniqueness check)
     */
    private function checkUniques($rec): array
    {
        $poster = Show::where('poster', '=', $rec['poster'])->whereNull('deleted_at')->first();
        $slug   = Show::where('slug', '=', $rec['slug'])->whereNull('deleted_at')->first();

        return [
            'poster' => ! ! ! $poster,
            'slug'   => ! ! ! $slug,
        ];
    }

    /**
     * Delete a show
     *
     * Soft-deletes a show record by ID.
     *
     * @param int $id The show ID to delete
     * @return JsonResponse The deleted show data or error message
     *
     * @source Database Model: Show (deletes)
     */
    public function destroy(int $id): JsonResponse
    {
        $show = Show::find($id);
        if (! $show) {
            return response()->json(['status' => 'error', 'message' => 'No matching show found.']);
        }

        $show->delete();

        return response()->json($show);
    }

    /**
     * Get shows in the current theater season
     *
     * Retrieves all shows that have performances scheduled within the
     * current theater season (October 1 - August 31).
     *
     * @return JsonResponse Shows with performances in current season
     *
     * @source Database Model: Show (reads with performances relationship)
     */
    public function seasonShows(): JsonResponse
    {
        $seasonDates = $this->getTheaterSeasonDates(); // Get season start & end dates

        $shows = Show::with(["performances", 'galleryImages'])->whereHas('performances', function ($query) use ($seasonDates) {
            $query->whereBetween('date', [$seasonDates['start'], $seasonDates['end']]);
        })->get();

        return response()->json($shows);
    }

    /**
     * Get shows for home page display
     *
     * Retrieves the current/next show with full details and buttons,
     * plus a list of all other upcoming shows. The current show includes
     * audition data, performances, and standard buttons for ticket purchase.
     *
     * @return JsonResponse Current show and array of upcoming shows
     *
     * @source Database Models:
     *   - Show (reads with audition and performances relationships)
     *   - StandardButton (reads for 'show' type)
     */
    public function homeShows(): JsonResponse
    {
        // get site config
        $siteConfig = SiteConfig::latest()->first();
        $price = $siteConfig->ticket_price;

        // get upcoming shows
        $shows = Show::with('audition')
            ->with("performances")
            ->whereHas('performances', function ($query) {
                $query->where('date', '>=', now());
            })
            ->get()
            ->toArray();

        // first show (current or next)
        $currentShow = array_shift($shows);
        $currentShow['fixrLabel'] = 'Pay with Credit / Debit';
        $currentShow['buttons'] = StandardButton::orderBy('sort_order')
            ->get()
            ->map(function ($rec) use ($price) {
                $price = "$" . $price;

                $rec->popupText = view("standard-buttons.{$rec->key}", [
                    'param' => "{$price} per ticket",
                    'subject' => "purchased tickets"
                ])->render();

                return $rec;
            });

        return response()->json(['currentShow' => $currentShow, 'upcomingShows' => $shows]);
    }

    /**
     * Update tentative status for a show
     *
     * Updates whether a show is marked as tentative (not yet confirmed).
     *
     * @param int $id The show ID to update
     * @param Request $request Contains 'tentative' boolean value
     * @return JsonResponse The request data
     *
     * @source Database Model: Show (updates)
     */
    public function updateTentative(int $id, Request $request)
    {
        $show = Show::find($id)->update(['tentative' => $request->tentative]);

        return response()->json($request->all());
    }

    /**
     * Get all shows
     *
     * Retrieves all shows with their performances (including ticket data)
     * and gallery images, ordered by ticket sales start date (most recent first).
     *
     * @return JsonResponse All shows with related data
     *
     * @source Database Model: Show (reads with performances.tickets and galleryImages relationships)
     */
    public function index(): JsonResponse
    {
        return response()->json(Show::with('performances.tickets', 'galleryImages')
            ->orderByDesc('ticket_sales_start')
            ->get());
    }

    /**
     * Get specific show by ID
     *
     * Retrieves detailed information for a single show including performances
     * with ticket data and gallery images.
     *
     * @param int $id The show ID to retrieve
     * @return JsonResponse Show data with relationships
     *
     * @source Database Model: Show (reads with performances.tickets and galleryImages relationships)
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(Show::with(['performances.tickets', 'galleryImages'])->where('id', '=', $id)->first());
    }

    /**
     * Return template data for creating a new show
     *
     * Provides default values for creating a new show record, including
     * the current ticket price from site configuration and calculated
     * default ticket sales start date (6 weeks from now).
     *
     * @return JsonResponse Template object with default values
     *
     * @source Database Model: SiteConfig (reads latest config for ticket_price)
     */
    public function newShow(): JsonResponse
    {
        $config = SiteConfig::orderByDesc('created_at')->first()->toArray();

        return response()->json([
            'name'               => null,
            'writer'             => null,
            'tagline'            => null,
            'director'           => null,
            'info'               => '',
            'poster'             => uniqid(),
            'ticket_sales_start' => date("Y-m-d", strtotime("NOW + 6 weeks")),
            'ticket_price'       => $config['ticket_price'],
            'performances'       => [],
        ]);
    }

    /**
     * Create a new show record
     *
     * Validates show data, checks that poster and slug are unique,
     * and creates a new show record.
     *
     * @param Request $request Contains show data to validate and create
     * @return JsonResponse Created show or validation errors
     *
     * @source Database Model: Show (creates)
     */
    public function create(Request $request): JsonResponse
    {
        $validated = Show::validate($request->all());
        $unique    = $this->checkUniques($validated);
        if (! $unique['poster']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Poster not unique',
            ]);
        }

        if (! $unique['slug']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Slug not unique',
            ]);
        }

        if (! is_array($validated) || array_key_exists('errors', $validated)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validated['errors'],
            ]);
        }

        $show = Show::create($validated);

        return response()->json($show);
    }

    /**
     * Update an existing show record
     *
     * Validates show data and updates the specified show.
     * Validates the show ID exists before attempting update.
     *
     * @param Request $request Contains show data including 'id'
     * @return JsonResponse Updated show data or validation/error messages
     *
     * @source Database Model: Show (updates)
     */
    public function update(Request $request): JsonResponse
    {
        $show = Show::find($request->input('id'));

        if (! $show) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Show not found.',
            ], 404);
        }

        $validated = Show::validate($request->all(), $request->input('id'));
        if (! is_array($validated) || array_key_exists('errors', $validated)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validated,
            ], 422);
        }

        $updated = $show->update($validated);

        return response()->json(['validated' => $validated, 'show' => $show, 'updated' => $updated]);
    }
}
