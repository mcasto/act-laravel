<?php

namespace App\Http\Controllers;

use App\Models\AuditionContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditionContactController extends Controller
{
    /**
     * Create an audition contact submission
     *
     * Validates and creates a contact record for someone interested in auditioning
     * for a specific role. Retrieves related audition and show information,
     * prepares an email notification to the director, and stores the contact
     *
     * @param Request $request Contains contact info (name, email, phone) and role details
     * @return JsonResponse The created audition contact record or validation errors
     *
     * @source Database Models:
     *   - Audition (reads)
     *   - Show (reads)
     *   - SiteConfig (reads latest config)
     *   - AuditionContact (creates)
     */
    public function create(Request $request): JsonResponse
    {
        $role = $request->input('role');

        $contact = [
            'audition_id' => $role['audition_id'],
            'name'        => $request->input('name'),
            'role'        => ($request->input('role'))['name'],
            'email'       => $request->input('email'),
            'phone'       => $request->input('phone'),
        ];

        $validator = validator($contact, [
            'audition_id' => ['required', 'integer'],
            'name'        => ['required', 'string', 'max:255'],
            'role'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255'],
            'phone'       => ['required', 'string', 'max:255'],
            'body'        => ['string'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => 'error', 'message' => array_values($errors)]);
        }

        $auditionContact = AuditionContact::create($contact);

        return response()->json(['response' => $auditionContact]);
    }
}
