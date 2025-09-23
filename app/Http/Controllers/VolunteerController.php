<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VolunteerController extends Controller
{
    public function contactCreate(Request $request): JsonResponse
    {
        return response()->json($request);
    }

    public function index()
    {
        return Volunteer::with('volunteerSkills.skill')
            // orderBy('volunterSkills.skill.name')
            ->get();
    }

    public function destroy(int $id)
    {
        $volunteer = Volunteer::find($id);
        if (!$volunteer) {
            return ['status' => 'error', 'message' => 'Volunteer not found'];
        }
        $volunteer->delete();

        return [['status' => 'success', 'volunteer' => $volunteer]];
    }
}
