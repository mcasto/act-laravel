<?php

namespace App\Http\Controllers;

use App\Helpers\LocalTime;
use App\Helpers\TheaterSeason;
use App\Models\Performance;
use App\Models\Show;
use App\Models\SiteConfig;
use App\Models\TicketSale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The board's ticket reports for a show (Shows → gear menu → Export Board
 * Reports), as one .xlsx with two tabs:
 *
 *  - "Aggregate Totals": one row per show for the show's season and the
 *    season before, with season totals.
 *  - "Detail Totals": one block per show (the chosen show, then earlier
 *    shows newest first, back to the start of the previous season), one row
 *    per performance.
 *
 * Shows later than the chosen one are listed on the aggregate tab with
 * blank rows, so the report reads "as of" that show.
 *
 * How each column is counted — see COLUMNS / methodColumn() / stats():
 *  - Credit Card … Comp: tickets sold by those methods ("pre-paid";
 *    Angel, Trade and Raffle tickets count as Comp).
 *  - Pay at Door: Door and Cash tickets (and any other method), less
 *    cancellations — an unpaid cancelled ticket simply isn't reserved.
 *  - Reserved: the six columns above.
 *  - Walk-ins: Walk In tickets, less cancellations.
 *  - Canx pre-paid: cancelled pre-paid tickets.
 *  - No Show pre-paid / UnPaid: No Show counts on pre-paid / unpaid sales.
 *  - Attended = Reserved + Walk-ins − Canx − both No Shows.
 *  - Seats Avail: each performance's sold-out target
 *    (Performance::effectiveSoldOutTarget()).
 *  - Projected Income = each reserved ticket and walk-in × the show's
 *    ticket price × its payment method's revenue multiplier (the show's own
 *    rate when it has one), less No Show UnPaid tickets.
 */
class BoardReportController extends Controller
{
    /** Ticket columns, keyed by payment_methods.value; anything else is Pay at Door. */
    private const METHOD_COLUMNS = [
        'credit_card' => 'card',
        'flex'        => 'flex',
        'paypal'      => 'paypal',
        'transfer'    => 'transfer',
        'comp'        => 'comp',
        'angel'       => 'comp',
        'trade'       => 'comp',
        'raffle'      => 'comp',
        'door'        => 'door',
        'cash'        => 'door',
        'walk-in'     => 'walkins',
    ];

    private const PREPAID = ['card', 'flex', 'paypal', 'transfer', 'comp'];

    /** Column key => [header, kind]. */
    private const COLUMNS = [
        'card'        => ["Credit\nCard", 'count'],
        'flex'        => ["Flex\nTix", 'count'],
        'paypal'      => ["Pay\nPal", 'count'],
        'transfer'    => ["Bank\nTrans", 'count'],
        'comp'        => ['Comp', 'count'],
        'door'        => ["Pay at\nDoor", 'count'],
        'reserved'    => ['Reserved', 'total'],
        'walkins'     => ['Walk-ins', 'count'],
        'canx'        => ["Canx\npre-paid", 'loss'],
        'ns_prepaid'  => ["No Show\npre-paid", 'loss'],
        'ns_unpaid'   => ["No Show\nUnPaid", 'loss'],
        'attended'    => ['Attended', 'total'],
        'seats'       => ["Seats\nAvail", 'count'],
        'pct_resrv'   => ["%\nResrv", 'pct'],
        'pct_attnd'   => ["%\nAttnd", 'pct'],
        'income'      => ["Projected\nIncome", 'money'],
    ];

    private const FORMATS = [
        'count' => '#,##0;(#,##0);"-"',
        'total' => '#,##0;(#,##0);"-"',
        'loss'  => '#,##0;(#,##0);"-"',
        'pct'   => '0%',
        'money' => '"$"#,##0',
    ];

    private const LIGHT_GREY = 'FFF2F2F2';
    private const BLUE = 'FF2F75B5';

    public function export(int $showId)
    {
        $show = Show::with('performances')->findOrFail($showId);
        $asOf = $show->performances->max('date') ?? LocalTime::todayString();

        $season = TheaterSeason::seasonForDate($show->performances->min('date') ?? $asOf);
        $previous = $this->previousSeason($season);

        // Every show with a performance in either season, newest first.
        $seasons = collect([$season, $previous])->mapWithKeys(function ($s) {
            $dates = TheaterSeason::datesForSeason($s);
            $shows = Show::with(['performances', 'paymentRates', 'performances.show'])
                ->whereHas('performances', fn ($q) => $q->whereBetween('date', [$dates['start'], $dates['end']]))
                ->get()
                ->sortByDesc(fn (Show $sh) => $sh->performances->min('date'))
                ->values();

            return [$s => $shows];
        });

        $spreadsheet = new Spreadsheet();

        $aggregate = $spreadsheet->getActiveSheet();
        $aggregate->setTitle('Aggregate Totals');
        $this->buildAggregate($aggregate, $seasons, $asOf);

        $detail = $spreadsheet->createSheet();
        $detail->setTitle('Detail Totals');
        $this->buildDetail($detail, $seasons, $asOf);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = Str::slug($show->name) . '-board-reports-' . LocalTime::todayString() . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── Sheets ──────────────────────────────────────────────────────────

    private function buildAggregate(Worksheet $sheet, Collection $seasons, string $asOf): void
    {
        $this->setup($sheet, PageSetup::ORIENTATION_LANDSCAPE);
        $this->title($sheet, 'Aggregate Show Totals by Season');
        $row = 4;

        foreach ($seasons as $season => $shows) {
            $sheet->setCellValue("A{$row}", $this->seasonLabel($season) . ' Season');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $row++;

            $top = $row;
            $this->headerRow($sheet, $row, 'Show Totals');
            $row++;

            $included = [];
            foreach ($shows as $s) {
                $sheet->setCellValue("A{$row}", $s->name);
                if ($this->reportable($s, $asOf)) {
                    $totals = $this->sumStats($this->performanceStats($s));
                    $included[] = $totals;
                    $this->valuesRow($sheet, $row, $totals);
                }
                $row++;
            }

            $seasonTotals = $this->sumStats($included);
            $this->totalsRow($sheet, $row, 'Season Totals:', $seasonTotals);
            $this->box($sheet, $top, $row);
            $row++;
            $this->shareRow($sheet, $row, $seasonTotals);
            $row += 3;
        }
    }

    private function buildDetail(Worksheet $sheet, Collection $seasons, string $asOf): void
    {
        $this->setup($sheet, PageSetup::ORIENTATION_PORTRAIT);
        $this->title($sheet, 'Show Totals by Performance Date');
        $row = 4;

        foreach ($seasons->flatten(1) as $s) {
            if (! $this->reportable($s, $asOf)) {
                continue;
            }

            $top = $row;
            $this->headerRow($sheet, $row, $s->name);
            $row++;

            $stats = $this->performanceStats($s);
            foreach ($stats as $perfStats) {
                $sheet->setCellValue("A{$row}", '  ' . Carbon::parse($perfStats['date'])->format('M. d D.'));
                $this->valuesRow($sheet, $row, $perfStats);
                $sheet->getStyle("M{$row}")->getFont()->setBold(true);
                $row++;
            }

            $totals = $this->sumStats($stats);
            $this->totalsRow($sheet, $row, 'Show Totals:', $totals);
            $this->box($sheet, $top, $row);
            $row++;
            $this->shareRow($sheet, $row, $totals);
            $row += 3;
        }
    }

    // ── Numbers ─────────────────────────────────────────────────────────

    /** A show is reported once it has started on or before the chosen show's last date. */
    private function reportable(Show $show, string $asOf): bool
    {
        $first = $show->performances->min('date');

        return $first !== null && $first <= $asOf;
    }

    /** One stats array per performance, in date order. */
    private function performanceStats(Show $show): array
    {
        $price = (float) ($show->ticket_price ?: $this->globalPrice());
        $rates = $show->paymentRates->pluck('revenue_multiplier', 'payment_method_id');

        $sales = TicketSale::with('paymentMethod')
            ->whereIn('performance_id', $show->performances->pluck('id'))
            ->get()
            ->groupBy('performance_id');

        return $show->performances
            ->sortBy(fn (Performance $p) => $p->date . ' ' . $p->start_time)
            ->map(fn (Performance $p) => $this->stats($p, $sales->get($p->id, collect()), $price, $rates))
            ->values()
            ->all();
    }

    private function stats(Performance $performance, Collection $sales, float $price, Collection $rates): array
    {
        $s = array_fill_keys(array_keys(self::COLUMNS), 0) + ['date' => $performance->date];
        $s['income'] = 0.0;

        foreach ($sales as $sale) {
            $column = $this->methodColumn($sale);
            $qty = (int) $sale->quantity;
            $cancelled = min((int) $sale->cancelled, $qty);
            $noShow = (int) $sale->no_show;
            $multiplier = $this->multiplier($sale, $rates);
            $prepaid = in_array($column, self::PREPAID, true);

            // Pre-paid tickets stay reserved when cancelled (shown under
            // Canx pre-paid); an unpaid cancelled ticket isn't reserved.
            $counted = $prepaid ? $qty : $qty - $cancelled;
            $s[$column] += $counted;

            if ($prepaid) {
                $s['canx'] -= $cancelled;
                $s['ns_prepaid'] -= $noShow;
            } else {
                $s['ns_unpaid'] -= $noShow;
            }

            $s['income'] += $counted * $price * $multiplier;
            if (! $prepaid) {
                $s['income'] -= $noShow * $price * $multiplier;
            }
        }

        $s['seats'] = $performance->effectiveSoldOutTarget();

        return $this->derive($s);
    }

    /** Reserved, Attended and the percentages, from the counted columns. */
    private function derive(array $s): array
    {
        $s['reserved'] = $s['card'] + $s['flex'] + $s['paypal'] + $s['transfer'] + $s['comp'] + $s['door'];
        // Losses are stored as negatives, so they're added.
        $s['attended'] = $s['reserved'] + $s['walkins'] + $s['canx'] + $s['ns_prepaid'] + $s['ns_unpaid'];
        $s['pct_resrv'] = $s['seats'] ? $s['reserved'] / $s['seats'] : 0;
        $s['pct_attnd'] = $s['seats'] ? $s['attended'] / $s['seats'] : 0;

        return $s;
    }

    private function sumStats(array $rows): array
    {
        $sum = array_fill_keys(array_keys(self::COLUMNS), 0);
        foreach ($rows as $r) {
            foreach (['card', 'flex', 'paypal', 'transfer', 'comp', 'door', 'walkins', 'canx', 'ns_prepaid', 'ns_unpaid', 'seats', 'income'] as $k) {
                $sum[$k] += $r[$k];
            }
        }

        return $this->derive($sum);
    }

    private function methodColumn(TicketSale $sale): string
    {
        return self::METHOD_COLUMNS[$sale->paymentMethod?->value] ?? 'door';
    }

    private function multiplier(TicketSale $sale, Collection $rates): float
    {
        $own = $rates->get($sale->payment_method_id);
        if ($own !== null) {
            return (float) $own;
        }

        $global = $sale->paymentMethod?->revenue_multiplier;

        return $global === null ? 1.0 : (float) $global;
    }

    private function globalPrice(): float
    {
        $config = Cache::remember('site-config', 3600, fn () => SiteConfig::latest()->first());

        return (float) ($config?->ticket_price ?? 0);
    }

    private function previousSeason(string $season): string
    {
        [$start] = explode('-', $season);
        $prev = (int) $start - 1;

        return sprintf('%02d-%02d', $prev, $prev + 1);
    }

    private function seasonLabel(string $season): string
    {
        [$start] = explode('-', $season);
        $year = 2000 + (int) $start;

        return $year . '-' . ($year + 1);
    }

    // ── Layout ──────────────────────────────────────────────────────────

    private function col(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }

    private function lastCol(): string
    {
        return $this->col(count(self::COLUMNS) + 1);
    }

    private function setup(Worksheet $sheet, string $orientation): void
    {
        $sheet->getPageSetup()->setOrientation($orientation)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.5);
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
        $sheet->getColumnDimension('A')->setWidth(22);
        foreach (array_keys(self::COLUMNS) as $i => $key) {
            $sheet->getColumnDimension($this->col($i + 2))->setWidth($key === 'income' ? 11 : 8.5);
        }
        $sheet->setShowGridlines(false);
    }

    private function title(Worksheet $sheet, string $title): void
    {
        $last = $this->lastCol();
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(10);

        $sheet->setCellValue('F1', 'Azuay Community Theater');
        $sheet->mergeCells('F1:L1');
        $sheet->getStyle('F1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("{$last}1", LocalTime::now()->format('M. Y'));
        $sheet->getStyle("{$last}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function headerRow(Worksheet $sheet, int $row, string $label): void
    {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10);

        foreach (array_values(self::COLUMNS) as $i => [$header]) {
            $sheet->setCellValue($this->col($i + 2) . $row, $header);
        }

        $range = "B{$row}:" . $this->lastCol() . $row;
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(8);
        $sheet->getStyle($range)->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$row}:" . $this->lastCol() . $row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(26);
    }

    private function valuesRow(Worksheet $sheet, int $row, array $stats): void
    {
        foreach (self::COLUMNS as $key => [, $kind]) {
            $i = array_search($key, array_keys(self::COLUMNS), true);
            $cell = $this->col($i + 2) . $row;
            $value = $kind === 'money' ? round($stats[$key]) : $stats[$key];
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(self::FORMATS[$kind]);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle('B' . $row . ':' . $this->lastCol() . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($this->lastCol() . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function totalsRow(Worksheet $sheet, int $row, string $label, array $stats): void
    {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $this->valuesRow($sheet, $row, $stats);
        $range = "A{$row}:" . $this->lastCol() . $row;
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
    }

    /** "% of resrvs" — each ticket column's share of Reserved. */
    private function shareRow(Worksheet $sheet, int $row, array $stats): void
    {
        $sheet->setCellValue("A{$row}", '% of resrvs:');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        foreach (['card', 'flex', 'paypal', 'transfer', 'comp', 'door'] as $i => $key) {
            $cell = $this->col($i + 2) . $row;
            $sheet->setCellValue($cell, $stats['reserved'] ? $stats[$key] / $stats['reserved'] : 0);
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0%');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setSize(7)->getColor()->setARGB(self::BLUE);
    }

    /** Outer border, shaded Reserved/Attended columns and dividers, like the paper report. */
    private function box(Worksheet $sheet, int $top, int $bottom): void
    {
        $last = $this->lastCol();
        $keys = array_keys(self::COLUMNS);
        $colOf = fn ($key) => $this->col(array_search($key, $keys, true) + 2);

        $sheet->getStyle("A{$top}:{$last}{$bottom}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

        foreach (['reserved', 'attended'] as $key) {
            $c = $colOf($key);
            $sheet->getStyle("{$c}{$top}:{$c}{$bottom}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::LIGHT_GREY);
            $sheet->getStyle("{$c}{$top}:{$c}{$bottom}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("{$c}{$top}:{$c}{$bottom}")->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (['card', 'income'] as $key) {
            $c = $colOf($key);
            $sheet->getStyle("{$c}{$top}:{$c}{$bottom}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
        }
    }
}
