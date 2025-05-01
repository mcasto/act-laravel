<?php
namespace App\Http\Controllers;

use App\Models\Show;
use Illuminate\Http\JsonResponse;

class GalleryController extends Controller
{
 public function index(): JsonResponse
 {
  $shows = Show::with('performances', 'galleryImages')->has('galleryImages')->orderByDesc('ticket_sales_start')->get();

  return response()->json($shows);
 }
}
