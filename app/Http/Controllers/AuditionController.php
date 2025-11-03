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
    public function show(int $id)
    {
        $audition = Audition::where('show_id', $id)
            ->first();

        return ['status' => 'success', 'audition' => $audition];
    }

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

    public function current(): JsonResponse
    {
        $currentAudition = Audition::with(['show'])
            ->where('display_date', '<=', Carbon::now())
            ->where('end_display_date', '>=', Carbon::now())
            ->first();

        return response()->json($currentAudition);
    }

    public function contact(Request $request): JsonResponse
    {
        $contact = $request->all();
        return response()->json($contact);
    }
}
