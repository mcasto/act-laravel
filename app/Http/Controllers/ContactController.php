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

    public function index()
    {
        return Contact::all();
    }

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
