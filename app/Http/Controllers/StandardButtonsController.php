<?php

namespace App\Http\Controllers;

use App\Models\StandardButton;
use App\Services\ChangeLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Patron purchase options (standard_buttons), managed on the admin Payment
 * Methods page under the payment-methods permission.
 */
class StandardButtonsController extends Controller
{
    /**
     * Every button, hidden ones included, with its on-site popup template
     * for editing. (Confirmation emails are on the Email Templates page.)
     */
    public function index(): JsonResponse
    {
        $buttons = StandardButton::orderBy('sort_order')
            ->get()
            ->map(function ($rec) {
                $templatePath = resource_path("views/standard-buttons/{$rec->key}.blade.php");

                $rec->template = file_exists($templatePath) ? file_get_contents($templatePath) : '';

                return $rec;
            });

        return response()->json($buttons);
    }

    /**
     * Save a button's label, sort order, display toggle and templates. The
     * key is never changed here — it names the template files and other
     * code looks buttons up by it.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $button = StandardButton::findOrFail($id);

        $validated = $request->validate([
            'label'                 => 'required|string|max:255',
            'sort_order'            => 'required|integer',
            'display_to_patrons'    => 'required|boolean',
            'template'              => 'required|string',
        ]);

        $button->update([
            'label'              => $validated['label'],
            'sort_order'         => $validated['sort_order'],
            'display_to_patrons' => $validated['display_to_patrons'],
        ]);

        $templatePath = resource_path("views/standard-buttons/{$button->key}.blade.php");
        $oldTemplate = file_exists($templatePath) ? file_get_contents($templatePath) : null;

        file_put_contents($templatePath, $validated['template']);

        ChangeLogger::record("Updated \"{$validated['label']}\" payment button template", [
            'template' => ['old' => $oldTemplate, 'new' => $validated['template']],
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Flip just the display-to-patrons toggle from the list view.
     */
    public function updateDisplay(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'display_to_patrons' => 'required|boolean',
        ]);

        StandardButton::findOrFail($id)->update($validated);

        return response()->json(['status' => 'success']);
    }
}
