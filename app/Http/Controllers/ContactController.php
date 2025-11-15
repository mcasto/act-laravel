<?php

namespace App\Http\Controllers;

use App\Mail\ContactMailer;
use App\Models\Contact;
use App\Models\SiteConfig;
use App\Util\SendGridUtil;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Store a contact form submission
     *
     * Validates contact form data, creates a contact record in the database,
     * and sends an email notification to the configured contact address.
     *
     * @param Request $request Contains name, email, subject, body
     * @return array Status and message
     *
     * @source Database Model: Contact (creates)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|required',
            'email' => 'email|required',
            'subject' => 'string|required',
            'body' => 'string|required',
        ]);

        if ($validator->fails()) {
            return ['status' => 'error', 'message' => 'Invalid contact information'];
        }

        $contact = Contact::create($validator->valid());

        // send email about contact
        try {
            Mail::to(config('mail.to.address'))
                ->send(new ContactMailer($contact));
            return ['status' => 'success'];
        } catch (Exception $e) {
            logger()->error($e);
            return ['status' => 'error', 'message' => 'Unable to send contact email'];
        }
    }

    /**
     * Create a contact submission (legacy method)
     *
     * Alternative contact creation method that uses SendGrid for email delivery
     * instead of Laravel's mail system. Validates data, creates contact record,
     * and sends email using SendGrid utility.
     *
     * @param Request $request Contains name, email, subject, body
     * @return JsonResponse Contact details and email recipients or validation errors
     *
     * @source Database Models:
     *   - SiteConfig (reads latest config)
     *   - Contact (creates)
     */
    public function create(Request $request): JsonResponse
    {
        $config    = SiteConfig::orderByDesc('created_at')->first()->toArray();
        $validated = Contact::validate($request->all());
        if (! isset($validated['errors'])) {
            $contact = Contact::create($validated);
            $toName  = 'ACT Contacts';
            $toEmail = env('APP_DEBUG') ? $config['dev_email'] : $config['contact_email'];

            // $response = SendGridUtil::send('Contact Message', $validated['name'], $validated['email'], $toName, $toEmail, $validated['subject'], $validated['body']);

            // $contact->sendgrid_response = json_encode($response, JSON_PRETTY_PRINT);

            $contact->save();

            // if ($response['statusCode'] != 202) {
            //     SendGridUtil::send('Error', $validated['name'], $validated['email'], 'ACT Errors', $config['dev_email'], $validated['subject'], $validated['body']);
            // }

            return response()->json(['toName' => $toName, 'toEmail' => $toEmail, 'contactRec' => $contact]);
        }

        return response()->json($validated);
    }

    /**
     * Get all contact submissions
     *
     * Retrieves all contact form submissions from the database.
     *
     * @return \Illuminate\Database\Eloquent\Collection All contact records
     *
     * @source Database Model: Contact (reads all)
     */
    public function index()
    {
        return Contact::all();
    }

    /**
     * Delete a contact submission
     *
     * Removes a specific contact record from the database by ID.
     *
     * @param int $id The contact ID to delete
     * @return array Status and message
     *
     * @source Database Model: Contact (deletes)
     */
    public function destroy(int $id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return ['status' => 'error', 'message' => 'Contact not found'];
        }

        $contact->delete();

        return ['status' => 'success'];
    }
}
