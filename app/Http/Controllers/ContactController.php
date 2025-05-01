<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\SiteConfig;
use App\Util\SendGridUtil;
// use App\Util\SendGridUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $config    = SiteConfig::orderByDesc('created_at')->first()->toArray();
        $validated = Contact::validate($request->all());
        if (! isset($validated['errors'])) {
            $contact = Contact::create($validated);
            $toName  = 'ACT Contacts';
            $toEmail = env('APP_DEBUG') ? $config['dev_email'] : $config['contact_email'];

            $response = SendGridUtil::send('Contact Message', $validated['name'], $validated['email'], $toName, $toEmail, $validated['subject'], $validated['body']);

            $contact->sendgrid_response = json_encode($response, JSON_PRETTY_PRINT);

            $contact->save();

            if ($response['statusCode'] != 202) {
                SendGridUtil::send('Error', $validated['name'], $validated['email'], 'ACT Errors', $config['dev_email'], $validated['subject'], $validated['body']);
            }

            return response()->json(['toName' => $toName, 'toEmail' => $toEmail, 'contactRec' => $contact]);
        }

        return response()->json($validated);
    }
}
