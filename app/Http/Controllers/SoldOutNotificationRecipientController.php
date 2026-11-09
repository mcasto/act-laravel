<?php

namespace App\Http\Controllers;

use App\Helpers\LocalTime;
use App\Models\Performance;
use App\Models\Show;
use App\Models\ShowAccessGrant;
use App\Models\SoldOutNotificationRecipient;
use Illuminate\Http\Request;

class SoldOutNotificationRecipientController extends Controller
{
    public function index()
    {
        return response()->json(SoldOutNotificationRecipient::orderBy('name')->get());
    }

    /**
     * The current show (running or next up — the one whose final performance
     * hasn't passed, earliest-starting first, matching getCurrentShow() on
     * the frontend) and its Director(s) from the Production Team, who get
     * sold-out emails automatically. Same director rule as
     * PerformanceController::soldOutEmailsFor().
     *
     * @return \Illuminate\Http\JsonResponse {show: ?string, directors: string[]}
     *
     * @source Database Models: Show, Performance, ShowAccessGrant, User (reads)
     */
    public function currentDirector()
    {
        $today = LocalTime::todayString();

        $showId = Performance::query()
            ->whereHas('show')
            ->groupBy('show_id')
            ->havingRaw('MAX(date) >= ?', [$today])
            ->orderByRaw('MIN(date)')
            ->value('show_id');

        $show = $showId ? Show::find($showId) : null;

        if (! $show) {
            return response()->json(['show' => null, 'directors' => []]);
        }

        $directors = ShowAccessGrant::where('show_id', $show->id)
            ->director()
            ->with('user:id,name')
            ->get()
            ->reject(fn (ShowAccessGrant $g) => ! $g->isActive() && ! $g->isUpcoming())
            ->map(fn (ShowAccessGrant $g) => $g->user?->name)
            ->filter()
            ->unique()
            ->values();

        return response()->json(['show' => $show->name, 'directors' => $directors]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $recipient = SoldOutNotificationRecipient::create($validated);

        return response()->json(['status' => 'success', 'data' => $recipient]);
    }

    public function update(Request $request, $id)
    {
        $recipient = SoldOutNotificationRecipient::find($id);
        if (! $recipient) {
            return response()->json(['status' => 'error', 'message' => 'Recipient not found']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $recipient->update($validated);

        return response()->json(['status' => 'success', 'data' => $recipient]);
    }

    public function destroy($id)
    {
        $recipient = SoldOutNotificationRecipient::find($id);
        if (! $recipient) {
            return response()->json(['status' => 'error', 'message' => 'Recipient not found']);
        }

        $recipient->delete();

        return response()->json(['status' => 'success']);
    }
}
