<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use Illuminate\Http\JsonResponse;

class ChangeLogController extends Controller
{
    /**
     * Most recent change log entries. Capped rather than paginated, matching
     * the other admin list pages (Contacts, Quick Messages, Ticket Sales),
     * which all load everything and search/paginate client-side.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            ChangeLog::with('user')
                ->orderByDesc('created_at')
                ->limit(500)
                ->get()
        );
    }
}
