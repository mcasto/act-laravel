<?php

namespace App\Http\Controllers;

use App\Mail\MessageUsMailer;
use App\Models\MessageUsSubmission;
use Exception;
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

        $submission = MessageUsSubmission::create($validated);

        try {
            Mail::to(config('mail.admin_to.address'))
                ->send(new MessageUsMailer(
                    email: $validated['email'],
                    body: $validated['body'],
                ));
        } catch (Exception $e) {
            logger()->error('Failed to send Message Us notification email', [
                'error' => $e->getMessage(),
                'submission_id' => $submission->id,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    public function index(): JsonResponse
    {
        return response()->json(MessageUsSubmission::orderBy('created_at', 'desc')->get());
    }

    public function destroy(int $id): JsonResponse
    {
        $submission = MessageUsSubmission::find($id);
        if (! $submission) {
            return response()->json(['status' => 'error', 'message' => 'Submission not found']);
        }

        $submission->delete();

        return response()->json(['status' => 'success']);
    }
}
