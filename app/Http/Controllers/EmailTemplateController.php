<?php

namespace App\Http\Controllers;

use App\Services\ChangeLogger;
use App\Support\EmailTemplates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * The admin Email Templates page: every email the site sends, what
 * triggers it, its template (edit / reset / preview), and an on/off switch.
 * See App\Support\EmailTemplates.
 */
class EmailTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = collect(EmailTemplates::all())->map(function (array $t, string $key) {
            $subject = null;
            try {
                $subject = $t['sample']()->envelope()->subject;
            } catch (Throwable) {
                // Shown without a sample subject.
            }

            return [
                'key'        => $key,
                'name'       => $t['name'],
                'audience'   => $t['audience'],
                'trigger'    => $t['trigger'],
                'recipient'  => $t['recipient'],
                'notes'      => $t['notes'] ?? null,
                'section'    => $t['section'] ?? false,
                'subject'    => $subject,
                'enabled'    => EmailTemplates::isEnabled($key),
                'customized' => EmailTemplates::isCustomized($key),
            ];
        })->values();

        return response()->json($rows);
    }

    public function show(string $key): JsonResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);

        return response()->json([
            'key'        => $key,
            'source'     => EmailTemplates::source($key),
            'default'    => EmailTemplates::defaultSource($key),
            'customized' => EmailTemplates::isCustomized($key),
        ]);
    }

    /** Renders the sample email with $source (the draft) in place. */
    public function preview(Request $request, string $key): JsonResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);
        $validated = $request->validate(['source' => 'nullable|string']);

        try {
            return response()->json(['status' => 'success'] + EmailTemplates::preview($key, $validated['source'] ?? ''));
        } catch (Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $this->problem($e)]);
        }
    }

    /** Saves an edit — refused if the template doesn't render with sample data. */
    public function update(Request $request, string $key): JsonResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);
        $validated = $request->validate(['source' => 'nullable|string']);
        $source = $validated['source'] ?? '';

        try {
            EmailTemplates::preview($key, $source);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Not saved — the template has a problem: ' . $this->problem($e),
            ]);
        }

        $old = EmailTemplates::source($key);
        EmailTemplates::save($key, $source);

        ChangeLogger::record('Edited email template "' . EmailTemplates::all()[$key]['name'] . '"', [
            'old' => $old,
            'new' => $source,
        ]);

        return response()->json(['status' => 'success', 'customized' => true]);
    }

    /** Back to the original template. */
    public function reset(string $key): JsonResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);

        $old = EmailTemplates::source($key);
        EmailTemplates::reset($key);

        ChangeLogger::record('Reset email template "' . EmailTemplates::all()[$key]['name'] . '" to default', [
            'old' => $old,
        ]);

        return response()->json([
            'status'     => 'success',
            'source'     => EmailTemplates::source($key),
            'customized' => false,
        ]);
    }

    public function updateEnabled(Request $request, string $key): JsonResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);
        $validated = $request->validate(['enabled' => 'required|boolean']);

        EmailTemplates::setEnabled($key, $validated['enabled']);

        ChangeLogger::record(
            ($validated['enabled'] ? 'Switched on' : 'Switched off') . ' email "' . EmailTemplates::all()[$key]['name'] . '"'
        );

        return response()->json(['status' => 'success', 'enabled' => $validated['enabled']]);
    }

    private function problem(Throwable $e): string
    {
        // Blade wraps errors in a ViewException; the original is clearer.
        $root = $e->getPrevious() ?? $e;

        return preg_replace('/ \(View: .*\)$/', '', $root->getMessage());
    }
}
