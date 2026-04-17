<?php

namespace App\Http\Controllers;

use App\Mail\CompTicketMailer;
use App\Models\CompTicket;
use Illuminate\Http\Request;
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
        $rec['uid'] = Str::uuid();
        $rec['sent_at'] = now();

        CompTicket::create($rec);

        Mail::to($rec['email'])->send(new CompTicketMailer([
            'view'  => 'comp-ticket-notice',
            'name'  => $rec['name'],
            'uid'   => $rec['uid'],
        ]));

        return response()->json(
            [
                'status' => 'success',
                'list' =>
                CompTicket::where('show_id', $rec['show_id'])
                    ->get()
            ]
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $compTicket = CompTicket::with(['show.performances'])
            ->where('uid', $id)
            ->firstOrFail();

        return response()->json($compTicket);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'performance_id' => 'required|int',
            'pickup_name' => 'required|string'
        ]);

        $update = $validated;
        $update['redeemed_at'] = now();

        $rec = CompTicket::where('uid', $id)
            ->firstOrFail();

        $rec->update($update);
        $rec->save();

        return response()->json(['rec' => $compTicket = CompTicket::with(['show.performances'])
            ->where('uid', $id)
            ->firstOrFail()]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
