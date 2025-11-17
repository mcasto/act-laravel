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
     * Store a new site configuration
     *
     * Creates a new site configuration record with ticket email, contact email,
     * dev email, sold out target, and ticket price. Also updates associated
     * standard buttons if provided. The configuration table is insert-only,
     * so this creates a new version rather than updating existing records.
     *
     * @param Request $request Contains 'config' object and optional 'buttons' array
     * @return JsonResponse Created config and count of updated buttons, or validation errors
     *
     * @source Database Models:
     *   - SiteConfig (creates)
     *   - StandardButton (updates via updateButtons method)
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
     * Update multiple standard buttons
     *
     * Protected helper method that updates button records including their
     * popup text. Handles the popupText field specially as it may involve
     * file operations through the model's mutator.
     *
     * @param array $buttons Array of button data with id, label, key, sort_order, popupText
     * @return void
     *
     * @source Database Model: StandardButton (updates)
     */
    protected function updateButtons(array $buttons): void
    {
        // mc-todo: make sure this works properly (probably needs fixed)

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
     * Display the most recent site configuration
     *
     * Retrieves the latest site configuration record along with all
     * standard buttons ordered by their sort order. The configuration
     * table is insert-only, so historical configurations are retained.
     *
     * @return JsonResponse Latest config and all buttons
     *
     * @source Database Models:
     *   - SiteConfig (reads latest)
     *   - StandardButton (reads all ordered by sort_order)
     */
    public function show(): JsonResponse
    {
        $config = SiteConfig::latest()->first();
        $buttons = StandardButton::orderBy('sort_order')->get();

        return response()->json(['config' => $config, 'buttons' => $buttons]);
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
     * Update the specified resource in storage
     *
     * @param Request $request
     * @return void
     * @source None (empty method)
     */
    public function update(Request $request) {}

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
