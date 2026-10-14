<?php

namespace App\Http\Controllers;

use App\Helpers\PatronMail;
use App\Mail\FlexRedemptionVerifyMailer;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\PendingFlexRedemption;
use App\Models\Performance;
use App\Models\TicketSale;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Public "Use Your Flex Package" redemptions. When
 * config('app.flex_requires_email_confirmation') is false they book
 * immediately (bookNow()). When true, they are held until the patron
 * clicks a link emailed to the address on file — otherwise anyone who
 * knows a flex holder's email could spend their tickets. The link lands on
 * FlexRedemptionConfirm.vue, which needs an explicit Confirm click rather
 * than confirming on page load, because some mail security scanners
 * (Outlook Safe Links etc.) open every link in incoming mail — a
 * GET-confirms link would let a scanner approve a request the patron
 * never made.
 */
class FlexRedemptionController extends Controller
{
    /**
     * How long the confirm link stays valid (capped at the performance's
     * start time, see request()).
     */
    private const EXPIRES_AFTER_HOURS = 48;

    /**
     * Called from TicketSaleController::publicStore() for type: flex, after
     * it has validated the request and dropped admin-only fields.
     */
    public function request(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'performance_id'  => 'required|integer|exists:performances,id',
            'first_name'      => 'required|string',
            'last_name'       => 'required|string',
            'email'           => 'required|email',
            'phone'           => 'nullable|string',
            'quantity'        => 'required|integer|min:1',
            'special_request' => 'sometimes|nullable|string',
        ]);

        $patron = Patron::where('email', $validated['email'])->first();
        $this->ensureBalance($patron, $validated['quantity']);

        $performance = Performance::with('show')->find($validated['performance_id']);
        $startsAt = $this->startsAt($performance);

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages([
                'performance_id' => 'That performance has already started.',
            ]);
        }

        if (! config('app.flex_requires_email_confirmation')) {
            return $this->bookNow($patron, $performance, $validated);
        }

        $pending = PendingFlexRedemption::create([
            'token'           => Str::random(64),
            'patron_id'       => $patron->id,
            'performance_id'  => $performance->id,
            'quantity'        => $validated['quantity'],
            'first_name'      => $validated['first_name'],
            'last_name'       => $validated['last_name'],
            'phone'           => $validated['phone'] ?? null,
            'special_request' => $validated['special_request'] ?? null,
            'expires_at'      => now()->addHours(self::EXPIRES_AFTER_HOURS)->min($startsAt->copy()->utc()),
        ]);

        try {
            PatronMail::to($patron->email)->send(new FlexRedemptionVerifyMailer([
                'view'             => 'flex-redemption-verify',
                'name'             => trim("{$patron->first_name} {$patron->last_name}"),
                'show_name'        => $performance->show?->name,
                'num_tickets'      => $pending->quantity,
                'performance_date' => Carbon::parse($performance->date)->format('F j, Y'),
                'performance_time' => Carbon::parse($performance->start_time)->format('g:i A'),
                'link'             => rtrim(config('app.frontend_url'), '/') . '/flex-confirm/' . $pending->token,
            ]));
        } catch (Exception $e) {
            logger()->error('Failed to send flex redemption verification email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Without the email there's no way to confirm — don't leave the
            // patron thinking a link is on its way. A 422 so
            // notifySubmitError() shows this message instead of its generic
            // network-error text.
            $pending->delete();

            throw ValidationException::withMessages([
                'email' => "We couldn't send your confirmation email. Please try again, or contact us.",
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Details for the confirm page. Token-gated, so it's fine to include
     * the request's show/date/quantity.
     */
    public function show(string $token): JsonResponse
    {
        $pending = $this->find($token);

        return response()->json($this->present($pending));
    }

    public function confirm(string $token): JsonResponse
    {
        // Locked so a double-click (or two tabs) can't book it twice.
        return DB::transaction(function () use ($token) {
            $pending = PendingFlexRedemption::where('token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pending->status() === 'confirmed') {
                return response()->json([
                    'transaction_id' => $pending->ticketSale?->transaction_id,
                ]);
            }

            if ($pending->status() !== 'pending') {
                throw ValidationException::withMessages([
                    'token' => $pending->status() === 'expired'
                        ? 'This confirmation link has expired. Please submit your request again.'
                        : 'This request was cancelled.',
                ]);
            }

            // Re-checked — the balance or the performance may have changed
            // since the request was submitted.
            $this->ensureBalance($pending->patron, $pending->quantity);

            $this->ensureNotSoldOut($pending->performance);

            $transactionId = $this->book([
                'performance_id'  => $pending->performance_id,
                'first_name'      => $pending->first_name,
                'last_name'       => $pending->last_name,
                'email'           => $pending->patron->email,
                'phone'           => $pending->phone,
                'quantity'        => $pending->quantity,
                'special_request' => $pending->special_request,
            ]);

            $pending->update([
                'confirmed_at'   => now(),
                'ticket_sale_id' => TicketSale::where('transaction_id', $transactionId)->value('id'),
            ]);

            return response()->json(['transaction_id' => $transactionId]);
        });
    }

    /**
     * "This wasn't me" — kills the link so it can't be confirmed later.
     */
    public function cancel(string $token): JsonResponse
    {
        $pending = $this->find($token);

        if ($pending->status() === 'pending') {
            $pending->update(['cancelled_at' => now()]);
        }

        return response()->json($this->present($pending->fresh()));
    }

    /**
     * Used when email confirmation is off: books straight away.
     */
    private function bookNow(Patron $patron, Performance $performance, array $validated): JsonResponse
    {
        // Patron row locked so a double-submit can't spend the same balance twice.
        return DB::transaction(function () use ($patron, $performance, $validated) {
            $patron = Patron::whereKey($patron->id)->lockForUpdate()->first();

            $this->ensureBalance($patron, $validated['quantity']);
            $this->ensureNotSoldOut($performance);

            $transactionId = $this->book([
                'performance_id'  => $performance->id,
                'first_name'      => $validated['first_name'],
                'last_name'       => $validated['last_name'],
                'email'           => $patron->email,
                'phone'           => $validated['phone'] ?? null,
                'quantity'        => $validated['quantity'],
                'special_request' => $validated['special_request'] ?? null,
            ]);

            return response()->json(['transaction_id' => $transactionId]);
        });
    }

    /**
     * Creates the flex ticket sale and returns its transaction_id.
     */
    private function book(array $data): string
    {
        $response = app(TicketSaleController::class)->store(new Request(['type' => 'flex'] + $data));

        return $response->getData(true)['transaction_id'];
    }

    private function ensureNotSoldOut(?Performance $performance): void
    {
        if ($performance?->sold_out) {
            throw ValidationException::withMessages([
                'performance_id' => 'Sorry, that performance has sold out.',
            ]);
        }
    }

    private function find(string $token): PendingFlexRedemption
    {
        return PendingFlexRedemption::with(['patron', 'performance.show'])
            ->where('token', $token)
            ->firstOrFail();
    }

    private function present(PendingFlexRedemption $pending): array
    {
        return [
            'status'           => $pending->status(),
            'first_name'       => $pending->patron?->first_name,
            'show_name'        => $pending->performance?->show?->name,
            'performance_date' => $pending->performance?->formatted_date,
            'performance_time' => $pending->performance?->formatted_time,
            'quantity'         => $pending->quantity,
            'remaining'        => PatronFlexPackage::remainingForPatron($pending->patron),
        ];
    }

    private function ensureBalance(?Patron $patron, int $quantity): void
    {
        $remaining = PatronFlexPackage::remainingForPatron($patron);

        if ($quantity > $remaining) {
            throw ValidationException::withMessages([
                'quantity' => $remaining > 0
                    ? "You only have {$remaining} Flex " . ($remaining === 1 ? 'ticket' : 'tickets') . ' remaining.'
                    : 'You have no remaining Flex tickets for this season.',
            ]);
        }
    }

    private function startsAt(Performance $performance): Carbon
    {
        // Performance date/time are local Cuenca time; the app runs in UTC.
        return Carbon::parse("{$performance->date} {$performance->start_time}", 'America/Guayaquil');
    }
}
