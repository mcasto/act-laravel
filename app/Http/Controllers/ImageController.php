<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
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
 public function store(Request $request)
 {
  //
 }

 /**
  * Show the form for editing the specified resource.
  */
 public function edit(string $id)
 {
  //
 }

 /**
  * Update image in storage (or create a new one if it doesn't exist)
  */
 public function update(Request $request): JsonResponse
 {
  $image     = $request->file('image');
  $extension = $image->guessExtension();

  $filename = $request->input('filename');
  $filename = basename(parse_url($filename, PHP_URL_PATH)); // strip any timestamps generated to refresh the image in the frontend
  $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;

  $pathName = storage_path('public/images/' . $filename);
  $path     = $request->file('image')->storeAs(
   'images', $filename, 'public'
  );

  return response()->json(['filename' => basename($path)]);
 }

 /**
  * Remove the specified resource from storage.
  */
 public function destroy(string $id)
 {
  //
 }
}
