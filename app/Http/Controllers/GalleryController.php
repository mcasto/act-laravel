<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use App\Models\Show;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class GalleryController extends Controller
{
    /**
     * Get all shows with gallery images
     *
     * Retrieves shows that have at least one gallery image, ordered by
     * ticket sales start date (most recent first), including performances
     * and gallery image data.
     *
     * @return JsonResponse Shows with performances and gallery images
     *
     * @source Database Model: Show (reads with performances and galleryImages relationships)
     */
    public function index(): JsonResponse
    {
        $shows = Show::with('performances', 'galleryImages')
            ->has('galleryImages')
            ->orderByDesc('ticket_sales_start')
            ->get();

        return response()->json($shows);
    }

    /**
     * Store gallery images for a show
     *
     * Accepts one or more image uploads, stores them in temporary storage,
     * and creates gallery image records associated with a show.
     *
     * @param Request $request Contains galleryUpload file(s) and show_id
     * @return JsonResponse Status and array of created image records
     *
     * @source
     *   Database Model: GalleryImage (creates)
     *   File: storage/app/public/gallery-temp/{filename} (writes)
     */
    public function store(Request $request)
    {
        $files = $request->file('galleryUpload');
        if (!is_array($files)) {
            $files = [$files];
        }

        $show = Show::findOrFail($request->show_id);
        $showSlug = Str::slug($show->name);
        $showYear = Carbon::parse($show->ticket_sales_start)->year;

        Storage::disk('public')->makeDirectory('gallery');

        $uploadedFiles = [];

        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $tempPath = $file->store('gallery-temp', 'public');

            $ext = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
            $filename = "gallery-{$showSlug}-{$showYear}-" . uniqid() . ".{$ext}";
            $permanentPath = "gallery/{$filename}";

            Image::read(Storage::disk('public')->path($tempPath))
                ->scaleDown(width: 1920, height: 1920)
                ->save(Storage::disk('public')->path($permanentPath), quality: 80);

            Storage::disk('public')->delete($tempPath);

            $image = GalleryImage::create([
                'show_id' => $request->show_id,
                'image'   => $permanentPath,
            ]);

            $uploadedFiles[] = $image;
        }

        return response()->json([
            'status' => 'success',
            'files'  => $uploadedFiles,
        ]);
    }

    /**
     * Delete a gallery image
     *
     * Removes a specific gallery image record from the database.
     * The actual image file is not deleted by this method.
     *
     * @param Request $request The request object (unused)
     * @param int $id The gallery image ID to delete
     * @return array Status message
     *
     * @source Database Model: GalleryImage (deletes)
     */
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

    /**
     * Update gallery image sort order
     *
     * Updates the sort_order field for multiple gallery images.
     * Used for reordering images in the gallery display.
     *
     * @param Request $request Contains array of image objects with id and sort_order
     * @return array Status message
     *
     * @source Database Model: GalleryImage (updates)
     */
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
