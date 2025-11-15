<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use App\Models\VolunteerSkill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VolunteerController extends Controller
{
    /**
     * Validate volunteer data
     *
     * Private helper method to validate volunteer information including
     * active status, email, experience, name, and phone number.
     *
     * @param array $data The volunteer data to validate
     * @return \Illuminate\Validation\Validator The validator instance
     *
     * @source None (utility method)
     */
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

    /**
     * Handle volunteer contact form submission
     *
     * Simple passthrough method that returns the request data.
     *
     * @param Request $request The contact data
     * @return JsonResponse The request data
     *
     * @source None (passthrough)
     */
    public function contactCreate(Request $request): JsonResponse
    {
        return response()->json($request);
    }

    /**
     * Get all volunteers with their skills
     *
     * Retrieves all volunteer records including their associated skills
     * through the volunteerSkills relationship.
     *
     * @return \Illuminate\Database\Eloquent\Collection All volunteers with skills
     *
     * @source Database Model: Volunteer (reads with volunteerSkills.skill relationship)
     */
    public function index()
    {
        return Volunteer::with('volunteerSkills.skill')
            // orderBy('volunterSkills.skill.name')
            ->get();
    }

    /**
     * Delete a volunteer
     *
     * Removes a volunteer record from the database by ID.
     *
     * @param int $id The volunteer ID to delete
     * @return array Status and deleted volunteer data or error message
     *
     * @source Database Model: Volunteer (deletes)
     */
    public function destroy(int $id)
    {
        $volunteer = Volunteer::find($id);
        if (!$volunteer) {
            return ['status' => 'error', 'message' => 'Volunteer not found'];
        }
        $volunteer->delete();

        return [['status' => 'success', 'volunteer' => $volunteer]];
    }

    /**
     * Create a new volunteer with skills
     *
     * Validates volunteer data, creates the volunteer record, and associates
     * it with the specified skills by creating VolunteerSkill records.
     *
     * @param Request $request Contains 'volunteer' object and 'skills' array of skill IDs
     * @return array Status message or validation errors
     *
     * @source Database Models:
     *   - Volunteer (creates)
     *   - VolunteerSkill (creates for each skill)
     */
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

    /**
     * Update a volunteer and their skills
     *
     * Validates and updates volunteer data, then replaces all skill associations
     * by deleting existing VolunteerSkill records and creating new ones based
     * on the provided skills array.
     *
     * @param int $id The volunteer ID to update
     * @param Request $request Contains 'volunteer' object and 'skills' array of skill IDs
     * @return array Status message or validation errors
     *
     * @source Database Models:
     *   - Volunteer (updates)
     *   - VolunteerSkill (deletes all for volunteer, then creates new)
     */
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
