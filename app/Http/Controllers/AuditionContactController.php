<?php

namespace App\Http\Controllers;

use App\Models\Audition;
use App\Models\AuditionContact;
use App\Models\Show;
use App\Models\SiteConfig;
use App\Util\SendGridUtil;
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
     * with the SendGrid response.
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

        $audition = Audition::find($role['audition_id']);
        $show     = Show::find($audition->show_id);
        $to       = $audition->director_email;

        $auditionContact = AuditionContact::create($contact);

        $config = SiteConfig::orderByDesc('created_at')->first()->toArray();

        $toName  = $show->director;
        $toEmail = env('APP_DEBUG') ? $config['dev_email'] : $audition->director_email;

        $body = "Name: " . $auditionContact['name'] . "<br />"
            . "Email: " . $auditionContact['email'] . "<br />"
            . "Phone: " . $auditionContact['phone']
            . "<br /><br />"
            . "Auditioning for " . $auditionContact['role']
            . "<br /><br />"
            . $auditionContact['body'];

        //   $response = SendGridUtil::send('Audition Contact', $auditionContact['name'], $auditionContact['email'], $toName, $toEmail, 'Audition Request for ' . $show->name, $body);

        //   $auditionContact->sendgrid_response = json_encode($response, JSON_PRETTY_PRINT);

        $auditionContact->save();

        // if ($response['statusCode'] != 202) {
        //     SendGridUtil::send('Error', $auditionContact['name'], $auditionContact['email'], 'ACT Errors', $config['dev_email'], 'Error with Audition Request for ' . $show->name, $body);
        // }

        return response()->json(['response' => $auditionContact]);
    }
}
