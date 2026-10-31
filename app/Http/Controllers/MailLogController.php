<?php

namespace App\Http\Controllers;

use App\Models\MailLog;
use Illuminate\Http\JsonResponse;

class MailLogController extends Controller
{
    /**
     * Most recent outgoing emails (see App\Services\MailLogger). Capped
     * rather than paginated, same as ChangeLogController::index().
     */
    public function index(): JsonResponse
    {
        return response()->json(
            MailLog::orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(1000)
                ->get()
        );
    }
}
