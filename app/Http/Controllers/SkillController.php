<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    /**
     * Get all skills ordered by name
     *
     * Retrieves all skill records from the database, sorted alphabetically
     * by name. Used for displaying skill options in forms or lists.
     *
     * @return JsonResponse All skills ordered by name
     *
     * @source Database Model: Skill (reads all)
     */
    public function list(): JsonResponse
    {
        return response()->json(Skill::orderBy('name')->get());
    }
}
