<?php

namespace App\Http\Controllers;

use App\Helpers\RefId;
use App\Mail\CompTicketMailer;
use App\Models\CompTicket;
use App\Models\TicketSale;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CompTixController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $id)
    {

        return response()->json(
            CompTicket::where('show_id', $id)
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => [
                'required',
                'string',
                'max:255'
            ],
            'email'          => [
                'required',
                'email',
                'max:255'
            ],
            'show_id'        => [
                'required',
                'integer',
                'exists:shows,id'
            ],
        ]);

        $rec = $validated;

        $comp = CompTicket::create($rec);
        $comp->uid = RefId::ref_id($comp->id);
        $comp->save();

        return response()->json(
            [
                'status' => 'success',
                'list' =>
                CompTicket::where('show_id', $rec['show_id'])
                    ->get()
            ]
        );
    }

    public function send(string $id)
    {
        $rec = CompTicket::where('uid', $id)->firstOrFail();

        Mail::to($rec['email'])->send(new CompTicketMailer([
            'view'  => 'comp-ticket-notice',
            'name'  => $rec['name'],
            'uid'   => $rec['uid'],
        ]));

        $rec->sent_at = Carbon::now();
        $rec->update();

        return response()->json([
            'list' =>
            CompTicket::where('show_id', $rec->show_id)
                ->get()
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $compTicket = CompTicket::with(['show.performances'])
            ->where('uid', $id)
            ->firstOrFail();

        $performanceIds = $compTicket->show->performances->pluck('id');

        $ticketSaleTotals = TicketSale::whereIn('performance_id', $performanceIds)
            ->groupBy('performance_id')
            ->select('performance_id', DB::raw('SUM(quantity) as total'))
            ->pluck('total', 'performance_id');

        $compTotals = CompTicket::whereIn('performance_id', $performanceIds)
            ->groupBy('performance_id')
            ->select('performance_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'performance_id');

        $today = Carbon::today();

        $compTicket->show->performances->each(function ($performance) use ($ticketSaleTotals, $compTotals, $today) {
            $sold = ($ticketSaleTotals[$performance->id] ?? 0) + ($compTotals[$performance->id] ?? 0);
            $performance->past     = Carbon::parse($performance->date)->lt($today);
            $performance->sold_out = $sold >= $performance->sold_out_target;
        });

        return response()->json($compTicket);
    }

    public function redeemComp(string $uid, int $performanceId, string $pickupName): CompTicket
    {
        $comp = CompTicket::where('uid', $uid)->firstOrFail();
        $comp->update([
            'performance_id' => $performanceId,
            'pickup_name'    => $pickupName,
            'redeemed_at'    => now(),
        ]);

        $performance = $comp->fresh()->performance;

        try {
            Mail::to($comp->email)->send(new CompTicketMailer([
                'view'             => 'comp-ticket-confirm',
                'name'             => $comp->name,
                'pickup_name'      => $pickupName,
                'performance_date' => Carbon::parse($performance->date)->format('F j, Y'),
                'performance_time' => Carbon::parse($performance->start_time)->format('g:i A'),
            ]));
        } catch (Exception $e) {
            logger()->error('Failed to send comp ticket confirmation', ['error' => $e->getMessage()]);
        }

        return $comp->fresh();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'performance_id' => 'required|int',
            'pickup_name'    => 'required|string',
        ]);

        $comp = $this->redeemComp($id, $validated['performance_id'], $validated['pickup_name']);

        return response()->json(['rec' => CompTicket::with(['show.performances'])
            ->where('uid', $comp->uid)
            ->firstOrFail()]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rec = CompTicket::where('uid', $id)->firstOrFail();
        $rec->delete();

        return response()->json(['id' => $rec->id]);
    }
}
