<?php
namespace App\Http\Controllers;

use App\Models\Performance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerformanceController extends Controller
{
 /**
  * $request->input('performances') = an array of performances
  *
  * In the frontend, since the user might delete an existing record *or* add one then delete it before submitting, I just wrote it so it adds a "deleted" property to the record object.
  */
 public function upsert(Request $request): JsonResponse
 {
  $performances = $request->input('performances');

  // find records that have deleted property *and* an id property, which means they need to be deleted from the database
  $deleteRecs = array_filter($performances, function ($performance) {
   return isset($performance['deleted']) && isset($performance['id']);
  });

  // delete those from the database
  foreach ($deleteRecs as $rec) {
   $performance = Performance::find($rec['id']);
   if ($performance) {
    $performance->delete();
   }
  }

  // find records that don't have a deleted property, which means they need to be upserted
  $upserts = array_filter($performances, function ($performance) {
   return ! isset($performance['deleted']);
  });

  foreach ($upserts as $upsert) {
   $validatedData = Performance::validate($upsert);

   if (isset($validatedData['show_id'])) {
    // Validation passed
    $performance = isset($validatedData['id']) ? Performance::find($validatedData['id']) : new Performance();

    if (! $performance) {
     $performance = new Performance();
    }

    $performance->fill($validatedData);
    $performance->save(); // Laravel handles updated_at automatically
   } else {
    Log::warning('Performance validation failed', ['errors' => $validatedData, 'record' => $upsert]);
   }
  }

  return response()->json(['deleted' => count($deleteRecs), 'upserted' => count($upserts)]);
 }
}
