<?php

namespace App\Http\Controllers;

use App\Models\StandardButton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class FlexPurchaseController extends Controller
{
    public function show()
    {
        $config = json_decode(Storage::disk('local')
            ->get('flex-purchase-config.json'), true);

        $config['buttons'] = StandardButton::orderBy('sort_order')->get();

        return $config;
    }

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
