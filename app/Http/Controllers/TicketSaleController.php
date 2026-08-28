<?php

namespace App\Http\Controllers;

use App\Helpers\RefId;
use App\Helpers\TheaterSeason;
use App\Mail\PurchaseConfirmationMailer;
use App\Mail\TicketSaleMailer;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
use App\Models\Performance;
use App\Models\CompTicket;
use App\Models\StandardButton;
use App\Models\TicketSale;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketSaleController extends Controller
{
    public function index()
    {
        return response()->json($this->allSales());
    }

    private function allSales()
    {
        $compPaymentMethod = PaymentMethod::where('value', 'comp')->first();

        $ticketSales = TicketSale::with('performance.show', 'patron', 'paymentMethod')
            ->join('performances', 'ticket_sales.performance_id', '=', 'performances.id')
            ->orderBy('performances.date', 'desc')
            ->orderBy('performances.start_time', 'asc')
            ->select('ticket_sales.*')
            ->get()
            ->map(fn($sale) => $sale->toArray());

        $compTickets = CompTicket::with('performance.show')
            ->join('performances', 'comp_tickets.performance_id', '=', 'performances.id')
            ->select('comp_tickets.*')
            ->get()
            ->map(fn($comp) => [
                'id'             => $comp->id,
                'quantity'       => 1,
                'sold_at'        => $comp->redeemed_at ?? $comp->sent_at,
                'transaction_id' => $comp->uid,
                'patron'         => [
                    'first_name' => $comp->name,
                    'last_name'  => '',
                    'email'      => $comp->email,
                    'phone'      => null,
                ],
                'payment_method' => $compPaymentMethod,
                'performance'    => $comp->performance,
            ]);

        return $ticketSales->concat($compTickets)
            ->sortByDesc(fn($item) => $item['performance']['date'] ?? '')
            ->values();
    }

    public function store(Request $request)
    {
        $request->mergeIfMissing(['send_mail' => true]);

        $validated = $request->validate([
            'type' => 'required|string',
            'performance_id' => 'required|integer',
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'required|email',
            'phone'      => 'required|string',
            'quantity' => 'required|integer|min:1',
            'transfer_date' => 'sometimes|nullable|date',
            'special_request' => 'sometimes|nullable|string',
            'send_mail' => 'sometimes|boolean'
        ]);

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'phone'      => $validated['phone'],
            ]
        );

        if ($validated['type'] === 'comp') {
            $performance = Performance::with('show')->find($validated['performance_id']);
            $pickupName  = $patron->first_name . ' ' . $patron->last_name;

            $comp = CompTicket::where('email', $patron->email)
                ->where('show_id', $performance?->show_id)
                ->whereNull('redeemed_at')
                ->first();

            if (! $comp) {
                $comp = CompTicket::create([
                    'name'    => $pickupName,
                    'email'   => $patron->email,
                    'show_id' => $performance?->show_id,
                ]);
                $comp->uid = RefId::ref_id($comp->id);
                $comp->save();
            }

            app(CompTixController::class)->redeemComp(
                $comp->uid,
                $validated['performance_id'],
                $pickupName,
                $validated['send_mail']
            );

            return response()->json(['status' => 'success']);
        }

        $paymentMethod = PaymentMethod::where('value', $validated['type'])
            ->first();

        $rec = [
            'patron_id'         => $patron->id,
            'transfer_date'     => $validated['transfer_date'] ?? null,
            'performance_id'    => $validated['performance_id'],
            'sold_at'           => now(),
            'quantity'          => $validated['quantity'],
            'payment_method_id' => $paymentMethod->id,
        ];

        $ticketSale = TicketSale::create($rec);
        $ticketSale->transaction_id = RefId::ref_id($ticketSale->id);
        $ticketSale->save();

        try {
            $performance = Performance::with('show')->find($validated['performance_id']);

            $ticketData = [
                'show'           => $performance?->show?->name,
                'performance'    => $performance ? $performance->date . ' ' . $performance->start_time : null,
                'first_name'     => $patron->first_name,
                'last_name'      => $patron->last_name,
                'email'          => $patron->email,
                'mobile_number'  => $patron->phone,
                'payment_method' => $paymentMethod?->label,
                'quantity'       => $validated['quantity'],
                'sold_at'        => $rec['sold_at'],
                'special_request' => $validated['special_request'] ?? null,
            ];

            $confirmationData = [
                'name'             => $patron->first_name . ' ' . $patron->last_name,
                'show_name'        => $performance?->show?->name,
                'num_tickets'      => $validated['quantity'],
                'performance_date' => $performance ? Carbon::parse($performance->date)->format('F j, Y') : null,
                'performance_time' => $performance ? Carbon::parse($performance->start_time)->format('g:i A') : null,
            ];

            if ($validated['type'] === 'flex') {
                $season = TheaterSeason::currentString();
                $flexPackage = PatronFlexPackage::where('patron_id', $patron->id)
                    ->where('season', $season)
                    ->first();

                $flexConfig = json_decode(Storage::disk('local')->get('flex-purchase-config.json'), true);

                $confirmationData['view']           = 'flex-confirmation';
                $confirmationData['remaining_flex'] = $flexPackage?->ticketsRemaining() ?? 0;
                $confirmationData['season']         = $season;

                $rawConfirmationBody = $flexConfig['confirmation_body'] ?? null;
                $confirmationData['confirmation_body'] = $rawConfirmationBody
                    ? Blade::render($rawConfirmationBody, $confirmationData, true)
                    : null;
            } else {
                $confirmationData['view'] = 'purchase-confirmation';

                $standardButton = StandardButton::where('key', $validated['type'])->first();
                $confirmationPath = $standardButton
                    ? resource_path("views/standard-buttons/{$standardButton->key}-confirmation.blade.php")
                    : null;

                $rawConfirmationBody = ($confirmationPath && file_exists($confirmationPath))
                    ? file_get_contents($confirmationPath)
                    : null;
                $confirmationData['confirmation_body'] = $rawConfirmationBody
                    ? Blade::render($rawConfirmationBody, $confirmationData, true)
                    : null;
            }

            if ($validated['send_mail']) {
                Mail::to(config('mail.admin_to.address'))->send(new TicketSaleMailer($ticketData));
                Mail::to($patron->email)->send(new PurchaseConfirmationMailer($confirmationData));
            }
        } catch (Exception $e) {
            logger()->error('Failed to send ticket sale email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['transaction_id' => $ticketSale->transaction_id]);
    }

    public function updateNoShow(Request $request, string $id)
    {
        $rec = TicketSale::findOrFail($id);
        $rec->no_show = $request->input('no_show');
        $rec->save();

        return response()->json(['rec' => $rec, 'id' => $id]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'            => 'required|integer|exists:ticket_sales,id',
            'type'          => 'required|string',
            'performance_id' => 'required|integer',
            'first_name'    => 'required|string',
            'last_name'     => 'required|string',
            'email'         => 'required|email',
            'phone'         => 'required|string',
            'quantity'      => 'required|integer|min:1',
            'transfer_date' => 'sometimes|nullable|date',
            'no_show'       => 'sometimes|boolean',
            'confirmed'     => 'sometimes|boolean',
        ]);

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'phone'      => $validated['phone'],
            ]
        );

        $paymentMethod = PaymentMethod::where('value', $validated['type'])->first();

        $ticketSale = TicketSale::findOrFail($validated['id']);
        $ticketSale->update([
            'patron_id'         => $patron->id,
            'performance_id'    => $validated['performance_id'],
            'quantity'          => $validated['quantity'],
            'payment_method_id' => $paymentMethod->id,
            'transfer_date'     => $validated['transfer_date'] ?? null,
            'no_show'           => $validated['no_show'] ?? false,
            'confirmed'         => $validated['confirmed'] ?? false,
        ]);

        return response()->json($this->allSales());
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        if ($request->input('payment_method.value') === 'comp') {
            CompTicket::findOrFail($id)->delete();
        } else {
            TicketSale::findOrFail($id)->delete();
        }

        return response()->json($this->allSales());
    }

    // ===== TEMP-NIGHTINGALES-SYNC =====================================
    // The "Nightingales" tracking workbook (uploaded as-is, one tab per
    // performance) is the accepted source of truth for this show's ticket
    // sales — even over whatever's already in the DB. So this OVERWRITES:
    // every ticket_sales row for the show's performances is deleted and
    // replaced with exactly what's on the sheet, rather than upserted
    // per-row. That's deliberate — an upsert keyed on (patron, performance,
    // method) can't detect or clean up a row that's now wrong because a
    // patron's date/method changed between two uploads of an evolving
    // sheet; a full replace can't leave stale rows behind no matter how
    // much the sheet has changed since the last upload.
    //
    // Remove this method + its private helpers below, the route
    // (routes/api.php), and the "Sync Nightingales" button + dialog in
    // AdminTicketSales.vue once this show closes.
    //
    // Sheet column label => payment_methods.value. "Comp" is intentionally
    // not handled here — comps live in a separate table with their own
    // redemption flow, not ticket_sales.
    private const NIGHTINGALES_CHANNEL_COLUMNS = [
        // Fixr is just the payment processor for credit card purchases, not
        // a distinct payment method — the sheet's column is still "FixR",
        // but it maps to the same payment method as any other card sale.
        'FixR' => 'credit_card',
        'Flex' => 'flex',
        'Pay Pal' => 'paypal',
        'Bank Trans' => 'transfer',
        'Door' => 'door',
        'Walk-in' => 'cash',
    ];

    public function syncNightingalesSheet(Request $request)
    {
        $validated = $request->validate([
            'show_id' => 'required|integer|exists:shows,id',
            'xlsx' => 'required|file',
        ]);

        // These sheets declare a used range of 1000+ rows (leftover fill/border
        // formatting) even though real data ends around row 40 — reading that
        // whole phantom range across every tab exhausts PHP's memory limit, so
        // cap what PhpSpreadsheet actually materializes into cell objects.
        $readFilter = new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= 200;
            }
        };

        try {
            $path = $validated['xlsx']->getRealPath();
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $reader->setReadFilter($readFilter);
            $spreadsheet = $reader->load($path);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not read that file as a spreadsheet: ' . $e->getMessage(),
            ], 422);
        }

        $performancesByDate = Performance::where('show_id', $validated['show_id'])->get()->keyBy('date');

        $paymentMethods = PaymentMethod::whereIn('value', array_values(self::NIGHTINGALES_CHANNEL_COLUMNS))
            ->get()->keyBy('value');

        // Parse every sheet first, without touching the DB — if anything
        // can't be read, we bail before deleting a single existing row.
        $parsed = [];
        $skippedSheets = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = $sheet->getTitle();

            $date = $this->findNightingalesSheetDate($sheet);
            if (! $date) {
                $skippedSheets[] = "{$sheetName} (no performance date found)";
                continue;
            }

            $performance = $performancesByDate->get($date);
            if (! $performance) {
                $skippedSheets[] = "{$sheetName} (no performance on {$date} for this show)";
                continue;
            }

            $colMap = $this->findNightingalesHeaderRow($sheet);
            if (! $colMap) {
                $skippedSheets[] = "{$sheetName} (no header row found — expected a \"Last Name\" column)";
                continue;
            }
            [$headerRow, $colMap] = $colMap;

            $required = array_merge(['Last Name', 'First Name', 'email address'], array_keys(self::NIGHTINGALES_CHANNEL_COLUMNS));
            $missing = array_diff($required, array_keys($colMap));
            if (! empty($missing)) {
                $skippedSheets[] = "{$sheetName} (missing column(s): " . implode(', ', $missing) . ')';
                continue;
            }

            $cell = fn (int $row, string $label) => trim((string) $sheet->getCell([$colMap[$label], $row])->getValue());

            // Aggregate per (email|last|first|channel) in case a name
            // appears more than once in the sheet (it happens).
            $aggregate = [];
            $highestRow = $sheet->getHighestDataRow();

            for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
                $last = $cell($r, 'Last Name');
                $first = $cell($r, 'First Name');
                $email = $cell($r, 'email address');

                if ($last === '' && $first === '') {
                    continue;
                }

                foreach (self::NIGHTINGALES_CHANNEL_COLUMNS as $sheetLabel => $methodValue) {
                    $raw = $cell($r, $sheetLabel);
                    $qty = is_numeric($raw) ? (int) $raw : 0;

                    if ($qty <= 0) {
                        continue;
                    }

                    $key = strtolower($email) . '|' . strtolower($last) . '|' . strtolower($first) . '|' . $methodValue;

                    $aggregate[$key] ??= [
                        'last' => $last, 'first' => $first, 'email' => $email,
                        'method' => $methodValue, 'qty' => 0,
                    ];
                    $aggregate[$key]['qty'] += $qty;
                }
            }

            $parsed[] = [
                'sheet' => $sheetName,
                'performance' => $performance,
                'aggregate' => $aggregate,
            ];
        }

        // Every performance for this show needs a successfully-parsed sheet
        // before we delete anything — otherwise a skipped/malformed tab
        // would wipe that performance's sales with nothing to replace them.
        $coveredPerformanceIds = collect($parsed)->pluck('performance.id');
        $missingPerformances = $performancesByDate->reject(fn ($p) => $coveredPerformanceIds->contains($p->id));

        if ($missingPerformances->isNotEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Refused to sync — no usable sheet found for: '
                    . $missingPerformances->map(fn ($p) => "{$p->formatted_date} {$p->formatted_time}")->implode(', ')
                    . '. Nothing was changed.',
                'skipped_sheets' => $skippedSheets,
            ], 422);
        }

        $results = [];
        $deletedCount = 0;

        DB::transaction(function () use ($parsed, $paymentMethods, $validated, &$results, &$deletedCount) {
            $deletedCount = TicketSale::whereHas('performance', fn ($q) => $q->where('show_id', $validated['show_id']))->delete();

            foreach ($parsed as $sheet) {
                $results[] = array_merge(
                    [
                        'sheet' => $sheet['sheet'],
                        'performance_id' => $sheet['performance']->id,
                        'performance' => "{$sheet['performance']->formatted_date} {$sheet['performance']->formatted_time}",
                    ],
                    $this->applyNightingalesAggregate($sheet['aggregate'], $sheet['performance'], $paymentMethods)
                );
            }
        });

        return response()->json([
            'status' => 'success',
            'deleted_count' => $deletedCount,
            'results' => $results,
            'skipped_sheets' => $skippedSheets,
            'sales' => $this->allSales(),
        ]);
    }

    /** Scans the first few rows/columns for a cell formatted as a date — that's the performance's date. Returns Y-m-d or null. */
    private function findNightingalesSheetDate(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): ?string
    {
        for ($r = 1; $r <= 6; $r++) {
            for ($c = 1; $c <= 6; $c++) {
                $cellObj = $sheet->getCell([$c, $r]);
                $raw = $cellObj->getValue();

                if (is_numeric($raw) && \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cellObj)) {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($raw)->format('Y-m-d');
                }
            }
        }

        return null;
    }

    /** Scans the first ~10 rows for a "Last Name" cell (the real header row, below a few metadata/totals rows) and maps column labels to column indexes. */
    private function findNightingalesHeaderRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): ?array
    {
        $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($r = 1; $r <= 10; $r++) {
            $rowLabels = [];
            for ($c = 1; $c <= $highestCol; $c++) {
                $rowLabels[$c] = trim((string) $sheet->getCell([$c, $r])->getValue());
            }

            if (in_array('Last Name', $rowLabels, true)) {
                // Flip to label => column index, dropping blank-label columns.
                return [$r, array_flip(array_filter($rowLabels, fn ($label) => $label !== ''))];
            }
        }

        return null;
    }

    /** Slugifies a name for a placeholder email's local part — lowercase, non-alphanumerics collapsed to hyphens. */
    private function slug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)));
        return trim($slug, '-');
    }

    /** Patron-matches and creates ticket_sales for one sheet's aggregated (patron, channel) => qty entries. Existing rows for the performance are already gone by the time this runs — see syncNightingalesSheet(). */
    private function applyNightingalesAggregate(array $aggregate, Performance $performance, \Illuminate\Support\Collection $paymentMethods): array
    {
        $inserted = 0;
        $tickets = 0;
        $newPatrons = 0;
        $placeholderEmails = [];
        $skipped = [];

        foreach ($aggregate as $entry) {
            $method = $paymentMethods->get($entry['method']);
            if (! $method) {
                $skipped[] = "{$entry['first']} {$entry['last']} ({$entry['method']} payment method not configured)";
                continue;
            }

            $email = $entry['email'];
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // No usable email on the sheet — don't drop the attendee,
                // tag them with an obviously-fake placeholder instead so
                // box office can find and fix these later. Slugged from
                // their name so the same no-email person across sheets
                // still resolves to one patron.
                $name = trim("{$entry['first']} {$entry['last']}");
                $slug = $this->slug($name) ?: 'unknown-' . uniqid();
                $email = "no-email+{$slug}@address.com";
                $placeholderEmails[] = "{$name} -> {$email}";
            }

            $patron = Patron::firstOrCreate(
                ['email' => $email],
                ['first_name' => $entry['first'], 'last_name' => $entry['last']]
            );

            if ($patron->wasRecentlyCreated) {
                $newPatrons++;
            }

            $ticketSale = TicketSale::create([
                'patron_id' => $patron->id,
                'performance_id' => $performance->id,
                'payment_method_id' => $method->id,
                'quantity' => $entry['qty'],
                'sold_at' => $performance->date . ' ' . $performance->start_time,
                'confirmed' => true,
            ]);
            $ticketSale->transaction_id = RefId::ref_id($ticketSale->id);
            $ticketSale->save();

            $inserted++;
            $tickets += $entry['qty'];
        }

        return [
            'inserted' => $inserted,
            'tickets' => $tickets,
            'new_patrons' => $newPatrons,
            'placeholder_emails' => $placeholderEmails,
            'skipped' => $skipped,
        ];
    }
    // ===== END TEMP-NIGHTINGALES-SYNC ==================================
}
