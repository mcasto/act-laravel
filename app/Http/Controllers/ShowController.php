<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\SiteConfig;
use App\Models\StandardButton;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    /**
     * Utility function to determine start and end of current season (runs from Oct 1 - August 31)
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
     * Ensure poster & slug are unique for undeleted records
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
     * Get shows in current season
     */
    public function seasonShows(): JsonResponse
    {
        $seasonDates = $this->getTheaterSeasonDates(); // Get season start & end dates

        $shows = Show::with("performances")->whereHas('performances', function ($query) use ($seasonDates) {
            $query->whereBetween('date', [$seasonDates['start'], $seasonDates['end']]);
        })->get();

        return response()->json($shows);
    }

    /**
     * Get shows for home page
     */
    public function homeShows(): JsonResponse
    {
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
        $currentShow['buttons'] = StandardButton::orderBy('sort_order')->get();

        logger()->info('current', $currentShow);

        return response()->json(['currentShow' => $currentShow, 'upcomingShows' => $shows]);
    }

    public function updateTentative(int $id, Request $request)
    {
        $show = Show::find($id)->update(['tentative' => $request->tentative]);

        return response()->json($request->all());
    }

    /**
     * Get all shows
     */
    public function index(): JsonResponse
    {
        return response()->json(Show::with('performances.tickets', 'galleryImages')
            ->orderByDesc('ticket_sales_start')
            ->get());
    }

    /**
     * Get specific show by id
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(Show::with(['performances.tickets', 'galleryImages'])->where('id', '=', $id)->first());
    }

    /**
     * Return template for new show record
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
            'poster'             => "poster-" . uniqid(),
            'ticket_sales_start' => date("Y-m-d", strtotime("NOW + 6 weeks")),
            'ticket_price'       => $config['ticket_price'],
            'performances'       => [],
        ]);
    }

    /**
     * Create new show record
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
     * Update show record
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
