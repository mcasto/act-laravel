<?php
namespace App\Http\Controllers;

use App\Models\SiteConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SiteConfigController extends Controller
{
 /**
  * Display a listing of the resource.
  */
 public function index()
 {
  //
 }

 /**
  * Show the form for creating a new resource.
  */
 public function create()
 {
  //
 }

 /**
  * Store a newly created resource in storage.
  */
 public function store(Request $request): JsonResponse
 {
  Log::info($request->all());

  try {
   // Validate the incoming request
   $validated = $request->validate([
    'ticket_email'    => 'required|string|max:255',
    'contact_email'   => 'required|string|max:255',
    'dev_email'       => 'required|string|max:255',
    'sold_out_target' => 'required|integer',
    'ticket_price'    => 'required|integer',
   ]);

   // Store the validated data
   $response = SiteConfig::create($validated);

   return response()->json($response);
  } catch (\Illuminate\Validation\ValidationException $e) {
   // Return validation errors if any
   Log::error('Validation failed: ', $e->errors());
   return response()->json($e->errors(), 200);
  }
 }

 /**
  * Display the specified resource.
  * Retrieves the most recent config
  * Table is insert-only, so historical configurations are retained
  */
 public function show(): JsonResponse
 {
  return response()->json(SiteConfig::latest()->first());
 }

 /**
  * Show the form for editing the specified resource.
  */
 public function edit(string $id)
 {
  //
 }

 /**
  * Update the specified resource in storage.
  */
 public function update(Request $request)
 {

 }

 /**
  * Remove the specified resource from storage.
  */
 public function destroy(string $id)
 {
  //
 }
}
