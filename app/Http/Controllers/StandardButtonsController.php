<?php

namespace App\Http\Controllers;

use App\Models\StandardButton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StandardButtonsController extends Controller
{
    /**
     * Get standard buttons by type
     *
     * Retrieves all standard buttons of a specific type (e.g., 'show', 'flex')
     * ordered by their sort_order field. Standard buttons are reusable
     * call-to-action buttons used throughout the application.
     *
     * @param string $type The button type to filter by
     * @return \Illuminate\Database\Eloquent\Collection Buttons of specified type
     *
     * @source Database Model: StandardButton (reads filtered by type)
     */
    public function index(int $amount)
    {
        logger()->info(__FILE__);
        logger()->info(__LINE__);
        $buttons = StandardButton::orderBy('sort_order')
            ->get()
            ->map(function ($rec) use ($amount) {
                $rec->popupText = view("standard-buttons.{$rec->key}", [
                    'price' => $amount['price'],
                ])->render();

                return $rec;
            });

        return $buttons;
    }
}
