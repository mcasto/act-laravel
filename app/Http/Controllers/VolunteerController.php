<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use App\Models\VolunteerSkill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VolunteerController extends Controller
{
    private function validate($data)
    {
        return Validator::make($data, [
            'active' => 'required|boolean',
            'email' => 'required|email',
            'experience' => 'required|string',
            'name' => 'required|string',
            'phone' => 'required|string',
        ]);
    }

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

    public function store(Request $request)
    {
        $validator = $this->validate($request->all()['volunteer']);
        if ($validator->fails()) {
            return ['status' => 'error', 'errors' => $validator->errors()];
        }

        $validated = $validator->validated();
        $volunteer = Volunteer::create($validated);

        // foreach $skills as $skill, insert into volunteer_skills
        foreach ($request->all()['skills'] as $skill_id) {
            VolunteerSkill::create([
                'volunteer_id' => $volunteer->id,
                'skill_id' => $skill_id
            ]);
        }

        return ['status' => 'success'];
    }

    public function update(int $id, Request $request)
    {
        $validator = $this->validate($request->all()['volunteer']);
        if ($validator->fails()) {
            return ['status' => 'error', 'errors' => $validator->errors()];
        }

        $validated = $validator->validated();
        $volunteer = Volunteer::find($id);
        $volunteer->update($validated);
        $volunteer->save();

        // delete all volunteer_skills for volunteer id
        VolunteerSkill::where('volunteer_id', $id)
            ->delete();

        // foreach $skills as $skill, insert into volunteer_skills
        foreach ($request->all()['skills'] as $skill_id) {
            VolunteerSkill::create([
                'volunteer_id' => $id,
                'skill_id' => $skill_id
            ]);
        }

        return ['status' => 'success'];
    }
}
