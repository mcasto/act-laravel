<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\TicketSale;
use Illuminate\Http\Request;

class TicketSaleController extends Controller
{
    public function index()
    {
        return TicketSale::with('performance.show')
            ->join('performances', 'ticket_sales.performance_id', '=', 'performances.id')
            ->orderBy('performances.date', 'desc')
            ->orderBy('performances.start_time', 'asc')
            ->select('ticket_sales.*')
            ->get();
    }
}
