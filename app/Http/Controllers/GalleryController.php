<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use App\Models\Show;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(): JsonResponse
    {
        $shows = Show::with('performances', 'galleryImages')
            ->has('galleryImages')
            ->orderByDesc('ticket_sales_start')
            ->get();

        return response()->json($shows);
    }

    /**
     * Store iamges for a show
     */
    public function store(Request $request)
    {
        // Get the file(s)
        $files = $request->file('galleryUpload');

        // Make it always an array for consistent handling
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file->isValid()) {
                $path = $file->store('gallery-temp', 'public');


                $rec = [
                    'show_id' => $request->show_id,
                    'image' => $path
                ];

                $image = GalleryImage::create($rec);

                $uploadedFiles[] = $image;
            }
        }

        return response()->json([
            'status' => 'success',
            'files' => $uploadedFiles,
        ]);
    }

    public function delete(Request $request, int $id)
    {
        try {
            $rec = GalleryImage::find($id);
            if ($rec) {
                $rec->delete();
            }

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Unable to delete gallery image'];
        }
    }

    public function update(Request $request)
    {
        foreach ($request->all() as $rec) {
            $image = GalleryImage::find($rec['id']);
            $image->sort_order = $rec['sort_order'];
            $image->save();
        }

        return ['status' => 'success'];
    }
}
