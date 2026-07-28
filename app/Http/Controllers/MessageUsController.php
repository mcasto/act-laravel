<?php

namespace App\Http\Controllers;

use App\Mail\MessageUsMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MessageUsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'body'  => 'required|string',
        ]);

        Mail::to(config('mail.admin_to.address'))
            ->send(new MessageUsMailer(
                email: $validated['email'],
                body: $validated['body'],
            ));

        return response()->json(['status' => 'success']);
    }
}
