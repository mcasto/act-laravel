<?php

namespace App\Http\Controllers;

use App\Models\Audition;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuditionController extends Controller
{
    /**
     * Get audition information for a specific show
     *
     * @param int $id The show ID to retrieve audition information for
     * @return array Status and audition data
     *
     * @source Database Model: Audition (reads)
     */
    public function show(int $id)
    {
        $audition = Audition::where('show_id', $id)
            ->first();

        return ['status' => 'success', 'audition' => $audition];
    }

    /**
     * Create a new audition record
     *
     * Validates the incoming request data and creates a new audition
     * using the model's custom createWithHtml method which handles
     * HTML content storage.
     *
     * @param Request $request Contains display_date, end_display_date, html, show_id
     * @return array Status and created audition or error message
     *
     * @source Database Model: Audition (creates)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'display_date' => 'required|date',
            'end_display_date' => 'required|date',
            'html' => 'nullable|string',
            'show_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return ['status' => 'error', 'message' => 'Invalid request'];
        }

        try {
            // Use the custom createWithHtml method
            $audition = Audition::createWithHtml($validator->validated());
            return ['status' => 'success', 'audition' => $audition];
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            return ['status' => 'error', 'message' => 'Unable to create audition record'];
        }
    }

    /**
     * Update an existing audition record
     *
     * Validates the incoming request data and updates the audition
     * using the model's custom updateWithHtml method which handles
     * HTML content storage.
     *
     * @param Request $request Contains display_date, end_display_date, html, show_id
     * @param int $id The audition ID to update
     * @return array Status and updated audition or error message
     *
     * @source Database Model: Audition (updates)
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'display_date' => 'required|date',
            'end_display_date' => 'required|date',
            'html' => 'nullable|string',
            'show_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return ['status' => 'error', 'message' => 'Invalid request'];
        }

        try {
            $audition = Audition::find($id);

            if (!$audition) {
                return ['status' => 'error', 'message' => 'Audition not found'];
            }

            // Use the custom updateWithHtml method
            $audition->updateWithHtml($validator->validated());

            return ['status' => 'success', 'audition' => $audition];
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            return ['status' => 'error', 'message' => 'Unable to update audition record'];
        }
    }

    /**
     * Get the currently active audition
     *
     * Retrieves auditions that are currently within their display date range
     * (between display_date and end_display_date), including the related show data.
     *
     * @return JsonResponse The current audition with show relationship or null
     *
     * @source Database Model: Audition (reads with Show relationship)
     */
    public function current(): JsonResponse
    {
        $currentAudition = Audition::with(['show'])
            ->where('display_date', '<=', Carbon::now())
            ->where('end_display_date', '>=', Carbon::now())
            ->first();

        return response()->json($currentAudition);
    }

    /**
     * Handle audition contact submission
     *
     * Simple passthrough method that returns the request data.
     *
     * @param Request $request The contact data
     * @return JsonResponse The request data as JSON
     *
     * @source None (passthrough)
     */
    public function contact(Request $request): JsonResponse
    {
        $contact = $request->all();
        return response()->json($contact);
    }
}
