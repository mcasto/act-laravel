<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource
     *
     * @return void
     * @source None (empty method)
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource
     *
     * @return void
     * @source None (empty method)
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage
     *
     * @param Request $request
     * @return void
     * @source None (empty method)
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource
     *
     * @param int $id
     * @return void
     * @source None (empty method)
     */
    public function edit(int $id)
    {
        //
    }

    /**
     * Update or create an image in storage
     *
     * Accepts an uploaded image file and a target filename. The image is stored
     * in the public images directory with the specified filename. If an image
     * with that filename already exists, it will be replaced.
     *
     * The filename parameter may include timestamp query strings (for cache busting)
     * which are stripped before saving.
     *
     * @param Request $request Contains image file and filename
     * @return JsonResponse The saved filename
     *
     * @source File: storage/app/public/images/{filename} (writes)
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
            'images',
            $filename,
            'public'
        );

        return response()->json(['filename' => basename($path)]);
    }

    /**
     * Remove the specified resource from storage
     *
     * @param int $id
     * @return void
     * @source None (empty method)
     */
    public function destroy(int $id)
    {
        //
    }
}
