<?php

namespace App\Http\Controllers;

use App\Mail\ContactMailer;
use App\Models\Contact;
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
