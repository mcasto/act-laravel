<?php

namespace App\Http\Controllers;

use App\Models\CourseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CourseSessionController extends Controller
{
    /**
     * Upsert multiple course session records
     *
     * Handles batch creation, updates, and deletions of a course's sessions
     * in one call — same shape as PerformanceController::upsert(). Records
     * marked with a 'deleted' property (and an existing 'id') are removed;
     * everything else is created or updated depending on whether it already
     * has an 'id'. Letting the frontend stage adds/deletes locally and only
     * submit once here keeps this in sync with the rest of the course form,
     * which saves as a single "Save Course" action rather than its own
     * separate save step.
     *
     * @source Database Model: CourseSession (creates, updates, deletes)
     */
    public function upsert(Request $request): JsonResponse
    {
        $sessions = $request->input('sessions', []);

        $deleteRecs = array_filter($sessions, function ($session) {
            return isset($session['deleted']) && isset($session['id']);
        });

        foreach ($deleteRecs as $rec) {
            $session = CourseSession::find($rec['id']);
            if ($session) {
                $session->delete();
            }
        }

        $upserts = collect($sessions)->filter(function ($session) {
            return ! isset($session['deleted']);
        });

        foreach ($upserts as $upsert) {
            $validator = Validator::make($upsert, [
                'id' => 'sometimes|integer|exists:course_sessions,id',
                'course_id' => 'required|integer|exists:courses,id',
                'date' => 'required|date',
                'start' => 'required',
                'end' => 'required',
            ]);

            if ($validator->fails()) {
                Log::warning('Course session validation failed', [
                    'errors' => $validator->errors(),
                    'record' => $upsert,
                ]);

                continue;
            }

            $validatedData = $validator->validated();
            $session = isset($validatedData['id']) ? CourseSession::find($validatedData['id']) : new CourseSession();

            if (! $session) {
                $session = new CourseSession();
            }

            $session->fill($validatedData);
            $session->save();
        }

        return response()->json([
            'deleted' => count($deleteRecs),
            'upserted' => $upserts->count(),
        ]);
    }
}
