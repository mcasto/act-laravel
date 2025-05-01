<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function list(): JsonResponse
    {
        return response()->json(Skill::orderBy('name')->get());
    }
}
