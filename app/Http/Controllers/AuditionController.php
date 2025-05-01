<?php
namespace App\Http\Controllers;

use App\Models\Audition;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;

class AuditionController extends Controller
{
 public function show(): JsonResponse
 {
  $currentAudition = Audition::with(['show', 'sessions', 'roles'])
   ->where('display_date', '<=', Carbon::now())
   ->whereHas('sessions', function ($query) {
    $query->where('session', '>=', now());
   })
   ->first();

  return response()->json($currentAudition);
 }

 public function contact(HttpRequest $request): JsonResponse
 {
  $contact = $request->all();
  return response()->json($contact);
 }
}
