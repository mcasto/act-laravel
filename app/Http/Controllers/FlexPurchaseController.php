<?php

namespace App\Http\Controllers;

use App\Models\StandardButton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class FlexPurchaseController extends Controller
{
    /**
     * Get flex purchase configuration
     *
     * Retrieves the flex purchase configuration from JSON file storage
     * and includes all standard buttons ordered by sort_order.
     *
     * @return array Configuration data with buttons
     *
     * @source
     *   File: storage/app/flex-purchase-config.json
     *   Database Model: StandardButton (reads ordered by sort_order)
     */
    public function show()
    {
        $config = json_decode(Storage::disk('local')
            ->get('flex-purchase-config.json'), true);

        $config['buttons'] = StandardButton::orderBy('sort_order')
            ->get()
            ->map(function ($rec) use ($config) {
                $rec->popupText = view("standard-buttons.{$rec->key}", [
                    'price' => $config['price']
                ])->render();

                return $rec;
            });

        return $config;
    }

    /**
     * Update flex purchase configuration
     *
     * Performs multiple operations:
     * 1. Cleans up temporary image files older than 24 hours
     * 2. Processes and stores a new image if provided
     * 3. Updates the configuration JSON file
     *
     * @param Request $request Contains config data and optional image path
     * @return array Status and updated configuration
     *
     * @source Files:
     *   - storage/app/public/flex-image-temp/* (reads and deletes old files)
     *   - storage/app/public/flex-image.jpg (writes)
     *   - storage/app/flex-purchase-config.json (writes)
     */
    public function update(Request $request)
    {
        // Clean up flex-image-temp directory by removing files that are older than 24 hours
        $files = Storage::disk('public')->files('flex-image-temp');
        $cutoffTime = now()->subHours(24)->timestamp;

        foreach ($files as $file) {
            if (Storage::disk('public')->lastModified($file) < $cutoffTime) {
                Storage::disk('public')->delete($file);
            }
        }

        // Check for image update
        $tempPath = $request->image ?? false;

        // Update flex-image & delete temp file
        if ($tempPath) {
            $image = Image::read(Storage::disk('public')->path($tempPath));
            $encoded = $image->toJpeg(90);

            Storage::disk('public')->put('flex-image.jpg', $encoded);
            Storage::disk('public')->delete($tempPath);
        }

        // Set up config
        $config = $request->all();
        unset($config['buttons']);
        unset($config['image']);

        Storage::disk('local')
            ->put('flex-purchase-config.json', json_encode($config));

        return ['status' => 'success', 'config' => $config];
    }

    /**
     * Upload temporary image for flex purchase
     *
     * Accepts an uploaded image file, validates it, and stores it
     * in a temporary location. The path is returned to be used
     * in a subsequent update() call.
     *
     * @param Request $request Contains the image file
     * @return array Status and temporary file path or error message
     *
     * @source File: storage/app/public/flex-image-temp/{filename} (writes)
     */
    public function image(Request $request)
    {
        // Get the file
        $file = $request->file('image');
        if (!$file->isValid()) {
            return ['status' => 'error', 'message' => 'Invalid Image File'];
        }

        $path = $file->store('flex-image-temp', 'public');

        return ['status' => 'success', 'path' => $path];
    }
}
