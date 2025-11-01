<?php

namespace App\Http\Controllers;

use App\Models\SiteConfig;
use App\Models\StandardButton;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        try {
            // Validate the incoming request data from the config array
            $validated = $request->validate([
                'config.ticket_email'    => 'required|string|max:255',
                'config.contact_email'   => 'required|string|max:255',
                'config.dev_email'       => 'required|string|max:255',
                'config.sold_out_target' => 'required|integer',
                'config.ticket_price'    => 'required|integer',
                'buttons'                => 'sometimes|array',
                'buttons.*.id'           => 'sometimes|integer|exists:standard_buttons,id',
                'buttons.*.label'        => 'sometimes|string|max:255',
                'buttons.*.key'          => 'sometimes|string|max:255',
                'buttons.*.sort_order'   => 'sometimes|integer',
                'buttons.*.popupText'    => 'sometimes|nullable|string',
            ]);

            // Extract just the config data for creation
            $configData = $validated['config'];

            // Store the validated data
            $response = SiteConfig::create($configData);

            // Update buttons if they exist in the request
            if (isset($validated['buttons']) && is_array($validated['buttons'])) {
                $this->updateButtons($validated['buttons']);
            }

            return response()->json([
                'config' => $response,
                'buttons_updated' => isset($validated['buttons']) ? count($validated['buttons']) : 0
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors if any
            Log::error('Validation failed: ', $e->errors());
            return response()->json($e->errors(), 200);
        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('Store operation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Operation failed'], 500);
        }
    }

    /**
     * Update multiple buttons
     */
    protected function updateButtons(array $buttons): void
    {
        foreach ($buttons as $buttonData) {
            // Find the button by ID
            $button = StandardButton::find($buttonData['id']);

            if ($button) {
                // Extract the popupText if it exists
                $popupText = $buttonData['popupText'] ?? null;
                unset($buttonData['popupText']);

                // Update the button attributes
                $button->update($buttonData);

                // Update the popupText (this will automatically handle file writing)
                if (array_key_exists('popupText', $buttonData) || $popupText !== null) {
                    $button->popup_text = $popupText;
                    $button->save(); // Save again to trigger file operations if needed
                }
            }
        }
    }

    /**
     * Display the specified resource.
     * Retrieves the most recent config
     * Table is insert-only, so historical configurations are retained
     */
    public function show(): JsonResponse
    {
        $config = SiteConfig::latest()->first();
        $buttons = StandardButton::orderBy('sort_order')->get();

        return response()->json(['config' => $config, 'buttons' => $buttons]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        //
    }
}
