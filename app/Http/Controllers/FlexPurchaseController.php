<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FlexPurchaseController extends Controller
{
    public function show()
    {
        return [
            'title' => 'Purchase Flex Tickets',
            'subtitle' => '6 admissions for the price of 5',
            'image' => '/images/uploaded/2025-26-season.jpg',
            'body' => "6 admissions to use as you wish to attend any of ACT's 6 regular 2025-26 season productions (<a href='/season'>SEASON DETAILS</a>). After you purchase your Flex Ticket, simply contact <a href='mailto:actseats@gmail.com'>actseats@gmail.com</a> with your reservation requests prior to any the performance(s) you wish to attend. We will keep track of your Flex Ticket admissions usage for you.",
            'fixr' => [
                'id' => '87475505',
                'label' => 'Pay with Credit / Debit Card'
            ],
            'buttons' => config('standard-buttons')
        ];
    }
}
