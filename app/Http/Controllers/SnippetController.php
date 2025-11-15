<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SnippetController extends Controller
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
     * Display the specified HTML snippet
     *
     * Retrieves an HTML snippet file by slug from the snippets directory.
     * Snippets are reusable HTML content blocks stored as individual files.
     *
     * @param string $slug The snippet identifier (filename without .html extension)
     * @return JsonResponse Object containing the HTML content
     *
     * @source File: storage/app/public/snippets/{slug}.html
     */
    public function show(string $slug): JsonResponse
    {
        $filePath = storage_path("/app/public/snippets/{$slug}.html");
        return response()->json(['html' => file_get_contents(storage_path("app/public/snippets/{$slug}.html"))]);
    }

    /**
     * Update the specified resource in storage
     *
     * @param Request $request
     * @param int $id
     * @return void
     * @source None (empty method)
     */
    public function update(Request $request, int $id)
    {
        //
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
