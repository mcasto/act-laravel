<?php

namespace App\Http\Controllers;

use App\Mail\VolunteerContactMailer;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class VolunteerController extends Controller
{
    /**
     * Handle volunteer contact form submission
     *
     * Simple passthrough method that returns the request data.
     *
     * @param Request $request The contact data
     * @return JsonResponse The request data
     *
     * @source None (passthrough)
     */
    public function contactCreate(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email',
            'phone'    => 'required|string',
            'skills'   => 'array',
            'skills.*' => 'integer|exists:skills,id',
        ]);

        $skills = Skill::whereIn('id', $request->input('skills', []))->get();

        Mail::to(config('mail.volunteer_to.address'))
            ->send(new VolunteerContactMailer(
                name:   $request->input('name'),
                email:  $request->input('email'),
                phone:  $request->input('phone'),
                skills: $skills,
            ));

        return response()->json(['status' => 'success']);
    }
}
