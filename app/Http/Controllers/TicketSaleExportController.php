<?php

namespace App\Http\Controllers;

use App\Helpers\LocalTime;
use App\Models\Show;
use App\Models\TicketSale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * One show's ticket sales as an .xlsx with three sheets:
 *
 *  - "Sales": one row per sale, ticket numbers listed out ("012, 013, 087").
 *    Listed rather than shown as a first-last range, because a sale whose
 *    quantity grew later picks up numbers from further along the show's
 *    sequence (TicketSale::reconcileTicketCount()), so a range would cover
 *    tickets that belong to other people.
 *  - "Tickets": one row per ticket with its own name and redemption, plus
 *    the parent sale's details. Sale ID links the two sheets.
 *  - "Mailchimp": one row per unique purchaser email (Email Address,
 *    First Name, Last Name), ready to save as CSV for a Mailchimp import.
 *
 * Every cell is written as text, so Excel keeps the leading zeros on ticket
 * numbers and doesn't reformat phone numbers or dates.
 *
 * Optional performance_id / payment_method_id narrow the export the same
 * way the matching filters on the admin Ticket Sales page do.
 */
class TicketSaleExportController extends Controller
{
    public function export(Request $request)
    {
        $validated = $request->validate([
            'show_id'           => ['required', 'integer', 'exists:shows,id'],
            'performance_id'    => ['nullable', 'integer'],
            'payment_method_id' => ['nullable', 'integer'],
        ]);

        $show = Show::findOrFail($validated['show_id']);

        $sales = TicketSale::with([
                'performance',
                'patron',
                'paymentMethod',
                'tickets' => fn ($query) => $query->orderBy('number'),
            ])
            ->join('performances', 'ticket_sales.performance_id', '=', 'performances.id')
            ->where('performances.show_id', $show->id)
            ->when($validated['performance_id'] ?? null, fn ($q, $id) => $q->where('ticket_sales.performance_id', $id))
            ->when($validated['payment_method_id'] ?? null, fn ($q, $id) => $q->where('ticket_sales.payment_method_id', $id))
            ->orderBy('performances.date')
            ->orderBy('performances.start_time')
            ->orderBy('ticket_sales.door_last')
            ->orderBy('ticket_sales.door_first')
            ->select('ticket_sales.*')
            ->get();

        $spreadsheet = new Spreadsheet();

        $salesSheet = $spreadsheet->getActiveSheet();
        $salesSheet->setTitle('Sales');
        $this->fill($salesSheet, [
            'Sale ID', 'Performance Date', 'Performance Time', 'Door Last', 'Door First',
            'Purchaser Last', 'Purchaser First', 'Email', 'Phone', 'Payment Method',
            'Qty', 'Cancelled', 'Returned', 'Resold', 'Ticket Numbers', 'Confirmed', 'Date Sold', 'No Shows',
            'Front Row', 'Special Seating', 'Comments',
        ], $sales->map(fn (TicketSale $sale) => [
            $sale->id,
            $this->performanceDate($sale),
            $this->performanceTime($sale),
            $this->doorLast($sale),
            $this->doorFirst($sale),
            $sale->patron?->last_name,
            $sale->patron?->first_name,
            $sale->patron?->email,
            $sale->patron?->phone,
            $sale->paymentMethod?->label,
            $sale->quantity,
            $sale->cancelled > 0 ? $sale->cancelled : '',
            $sale->cancelled_returned > 0 ? $sale->cancelled_returned : '',
            $sale->resold > 0 ? $sale->resold : '',
            $sale->tickets->pluck('formatted_number')->implode(', '),
            $this->yesNo($sale->confirmed),
            $sale->sold_at ? Carbon::parse($sale->sold_at)->format('Y-m-d g:i A') : '',
            $sale->no_show > 0 ? $sale->no_show : '',
            $sale->front_row > 0 ? $sale->front_row : '',
            $sale->special_seating,
            $sale->comments,
        ]));

        $ticketsSheet = $spreadsheet->createSheet();
        $ticketsSheet->setTitle('Tickets');
        $this->fill($ticketsSheet, [
            'Ticket #', 'Ticket Name', 'Redeemed', 'Redeemed At', 'Sale ID',
            'Performance Date', 'Performance Time', 'Door Last', 'Door First',
            'Purchaser Last', 'Purchaser First', 'Email', 'Payment Method',
            // A ticket doesn't record which seat was the no-show, so each
            // ticket row carries its sale's count.
            'Confirmed', 'No Shows (Sale)',
        ], $sales->flatMap(fn (TicketSale $sale) => $sale->tickets->map(fn ($ticket) => [
            $ticket->formatted_number,
            $ticket->name,
            $this->yesNo($ticket->redeemed_at),
            // redeemed_at is stored in UTC (unlike sold_at, which is
            // already local wall-clock time).
            $ticket->redeemed_at?->setTimezone(LocalTime::zone())->format('Y-m-d g:i A') ?? '',
            $sale->id,
            $this->performanceDate($sale),
            $this->performanceTime($sale),
            $this->doorLast($sale),
            $this->doorFirst($sale),
            $sale->patron?->last_name,
            $sale->patron?->first_name,
            $sale->patron?->email,
            $sale->paymentMethod?->label,
            $this->yesNo($sale->confirmed),
            $sale->no_show > 0 ? $sale->no_show : '',
        ])));

        // One row per unique purchaser email, column names matching
        // Mailchimp's default audience fields so the import maps them
        // automatically. Names come from the purchaser (the email's owner),
        // not the door name, using their most recent sale in this export.
        $mailchimpSheet = $spreadsheet->createSheet();
        $mailchimpSheet->setTitle('Mailchimp');
        $this->fill($mailchimpSheet, [
            'Email Address', 'First Name', 'Last Name',
        ], $sales
            ->filter(fn (TicketSale $sale) => filled($sale->patron?->email))
            ->sortByDesc('sold_at')
            ->unique(fn (TicketSale $sale) => Str::lower(trim($sale->patron->email)))
            ->sortBy(fn (TicketSale $sale) => Str::lower($sale->patron->last_name . ' ' . $sale->patron->first_name))
            ->map(fn (TicketSale $sale) => [
                trim($sale->patron->email),
                $sale->patron->first_name,
                $sale->patron->last_name,
            ]));

        $spreadsheet->setActiveSheetIndex(0);

        $filename = Str::slug($show->name) . '-ticket-sales-' . LocalTime::todayString() . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Header row (bold, frozen, filterable), then each row with every
     * cell written explicitly as a string.
     */
    private function fill(Worksheet $sheet, array $headers, iterable $rows): void
    {
        $sheet->fromArray($headers, null, 'A1');

        $rowNumber = 2;
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $value) {
                $sheet->setCellValueExplicit(
                    [$i + 1, $rowNumber],
                    // Hyphen, not en dash, in any free text pulled from the DB.
                    str_replace(['–', '—'], '-', (string) ($value ?? '')),
                    DataType::TYPE_STRING,
                );
            }
            $rowNumber++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $lastRow = max($rowNumber - 1, 1);

        // Text format too, so anything typed into these cells later in
        // Excel also stays as text.
        $sheet->getStyle("A2:{$lastColumn}" . max($lastRow, 2))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_TEXT);

        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");

        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }

    // Same fallback as the admin page: comps and not-yet-backfilled legacy
    // rows have no door name, so use the purchaser's.
    private function doorLast(TicketSale $sale): ?string
    {
        return $sale->door_last ?: $sale->patron?->last_name;
    }

    private function doorFirst(TicketSale $sale): ?string
    {
        return $sale->door_first ?: $sale->patron?->first_name;
    }

    private function performanceDate(TicketSale $sale): string
    {
        return $sale->performance ? Carbon::parse($sale->performance->date)->format('Y-m-d') : '';
    }

    private function performanceTime(TicketSale $sale): string
    {
        return $sale->performance?->formatted_time ?? '';
    }

    private function yesNo($value): string
    {
        return $value ? 'Yes' : 'No';
    }
}
