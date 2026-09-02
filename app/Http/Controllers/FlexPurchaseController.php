<?php

namespace App\Http\Controllers;

use App\Helpers\TheaterSeason;
use App\Mail\FlexPurchaseConfirmationMailer;
use App\Mail\FlexPurchaseMailer;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
use App\Models\StandardButton;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
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

        $price = $config['price'];
        $config['buttons'] = Cache::remember('standard-buttons', 3600, fn() => StandardButton::orderBy('sort_order')->get())
            ->whereNotIn('key', ['questions', 'flex'])
            ->map(function ($rec) use ($price) {
                $rec->popupText = Cache::remember(
                    "standard-button-{$rec->key}-{$price}-flex",
                    3600,
                    fn() => view("standard-buttons.{$rec->key}", [
                        'param' => "{$price}",
                        'subject' => "you purchased Flex Tickets"
                    ])->render()
                );
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
        $tempPath = $request->uploadedImage ?? false;

        // Update flex-image & delete temp file
        if ($tempPath && Storage::disk('public')->exists($tempPath)) {
            $image = Image::read(Storage::disk('public')->path($tempPath));
            $encoded = $image->toJpeg(90);

            Storage::disk('public')->put('images/flex-image.jpeg', $encoded);
            Storage::disk('public')->delete($tempPath);
        }

        // Set up config
        $config = $request->all();
        logger()->info($config['image']);
        unset($config['buttons']);
        unset($config['uploadedImage']);

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

    /**
     * Public "Buy a Flex Package" form submission (PayPal/Bank Transfer).
     * Records a season-scoped patron_flex_packages entitlement — deliberately
     * not tied to any show/performance, since a Flex package is used across
     * a season, not purchased for one specific show. The FixR path for the
     * same purchase is handled separately in FixrWebhooksController.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'payment_method_value' => 'required|string|exists:payment_methods,value',
        ]);

        $paymentMethod = PaymentMethod::where('value', $validated['payment_method_value'])->first();

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
            ]
        );

        $numTickets = json_decode(Storage::disk('local')->get('flex-purchase-config.json'), true)['num_tickets'];

        $package = PatronFlexPackage::create([
            'patron_id' => $patron->id,
            'season' => TheaterSeason::currentString(),
            'tickets_purchased' => $numTickets,
            'payment_method_id' => $paymentMethod->id,
            'purchased_at' => now(),
        ]);

        try {
            Mail::to(config('mail.admin_to.address'))
                ->send(new FlexPurchaseMailer($package));
        } catch (Exception $e) {
            logger()->error('Failed to send Flex purchase notification email', [
                'error' => $e->getMessage(),
                'patron_flex_package_id' => $package->id,
            ]);
        }

        try {
            Mail::to($patron->email)
                ->send(new FlexPurchaseConfirmationMailer($package));
        } catch (Exception $e) {
            logger()->error('Failed to send Flex purchase confirmation email', [
                'error' => $e->getMessage(),
                'patron_flex_package_id' => $package->id,
            ]);
        }

        return response()->json(['status' => 'success']);
    }
}
