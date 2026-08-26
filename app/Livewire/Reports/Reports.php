<?php

namespace App\Livewire\Reports;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class Reports extends Component
{
    public string $from = '';
    public string $to = '';

    public function mount(): void
    {
        $this->from = now()->startOfYear()->toDateString();
        $this->to = now()->toDateString();
    }

    protected function reportData(): array
    {
        $storeId = StoreContext::id();
        $from = $this->from ?: now()->startOfYear()->toDateString();
        $to = $this->to ?: now()->toDateString();

        $payments = Payment::with(['rental.customer'])
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        $refunds = (int) Payment::when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->where('type', 'refund')
            ->whereBetween('date', [$from, $to])
            ->sum('amount');

        $revenue = (int) $payments->where('type', '!=', 'refund')->sum('amount');
        $count = $payments->where('type', '!=', 'refund')->count();

        $rentals = Rental::when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->whereBetween('start_date', [$from, $to])
            ->get();

        $rentalCount = $rentals->count();
        $average = $rentalCount > 0 ? (int) round($revenue / $rentalCount) : 0;

        $monthly = collect(range(11, 0))
            ->map(function ($i) use ($payments) {
                $date = now()->startOfMonth()->subMonths($i);
                $amount = (int) $payments->where('date', '>=', $date->copy())->where('date', '<=', $date->copy()->endOfMonth())->where('type', '!=', 'refund')->sum('amount');

                return ['label' => $date->translatedFormat('M y'), 'amount' => $amount];
            });
        $maxMonthly = max(1, (int) $monthly->max('amount'));

        $topProducts = RentalItem::query()
            ->whereHas('rental', fn ($q) => $q->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('status', '!=', 'cancelled'))
            ->selectRaw('product_id, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(8)
            ->with('product')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->product?->name ?? '—',
                'qty' => (int) $item->qty,
                'revenue' => (int) $item->revenue,
            ]);

        $maxTop = max(1, (int) $topProducts->max('qty'));

        $topPacks = RentalItem::query()
            ->where('is_pack_component', true)
            ->whereHas('rental', fn ($q) => $q->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('status', '!=', 'cancelled'))
            ->selectRaw('COALESCE(NULLIF(pack_name, \'\'), pack_id) as pack_label, COUNT(DISTINCT rental_id) as rentals_count, SUM(line_total) as revenue')
            ->groupBy('pack_label')
            ->orderByDesc('rentals_count')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'label' => $item->pack_label,
                'rentals' => (int) $item->rentals_count,
                'revenue' => (int) $item->revenue,
            ]);

        $statuses = collect(['reserved', 'active', 'completed', 'cancelled'])->mapWithKeys(function ($status) use ($rentals) {
            return [$status => $rentals->where('status', $status)->count()];
        });

        return compact('from', 'to', 'payments', 'refunds', 'revenue', 'count', 'rentalCount', 'average',
            'monthly', 'maxMonthly', 'topProducts', 'maxTop', 'topPacks', 'statuses');
    }

    public function exportPaymentsCsv(): StreamedResponse
    {
        $storeId = StoreContext::id();
        $from = $this->from ?: now()->startOfYear()->toDateString();
        $to = $this->to ?: now()->toDateString();

        $payments = Payment::with(['rental.customer'])
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Référence', 'Date', 'Client', 'Location', 'Type', 'Mode', 'Montant']);

        foreach ($payments as $payment) {
            fputcsv($csv, [
                $payment->reference,
                $payment->date?->format('d/m/Y'),
                $payment->rental?->customer?->full_name,
                $payment->rental?->reference,
                $payment->type,
                $payment->method,
                ($payment->type === 'refund' ? '-' : '').$payment->amount,
            ]);
        }

        rewind($csv);

        return response()->streamDownload(function () use ($csv) {
            fpassthru($csv);
            fclose($csv);
        }, 'paiements-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(): StreamedResponse
    {
        $data = $this->reportData();

        $rows = [];
        $rows[] = ['Rapport du '.$data['from'].' au '.$data['to']];
        $rows[] = [];
        $rows[] = ['Chiffre d\'affaires', $data['revenue'].' DA'];
        $rows[] = ['Remboursements', $data['refunds'].' DA'];
        $rows[] = ['Nombre de locations', $data['rentalCount']];
        $rows[] = ['Panier moyen', $data['average'].' DA'];
        $rows[] = [];
        $rows[] = ['Top articles (quantité)', ''];
        $rows[] = ['Article', 'Quantité', 'Revenu (DA)'];
        foreach ($data['topProducts'] as $p) {
            $rows[] = [$p['name'], $p['qty'], $p['revenue']];
        }
        $rows[] = [];
        $rows[] = ['Top packs', ''];
        $rows[] = ['Pack', 'Locations', 'Revenu (DA)'];
        foreach ($data['topPacks'] as $p) {
            $rows[] = [$p['label'], $p['rentals'], $p['revenue']];
        }

        $xml = '<?xml version="1.0"?>'."\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>'."\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'."\n";
        $xml .= '<Worksheet ss:Name="Rapport">'."\n";
        $xml .= '<Table>'."\n";
        foreach ($rows as $row) {
            $xml .= '<Row>'."\n";
            foreach ($row as $cell) {
                $type = is_numeric($cell) ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="'.$type.'">'.htmlspecialchars((string) $cell, ENT_XML1).'</Data></Cell>'."\n";
            }
            $xml .= '</Row>'."\n";
        }
        $xml .= '</Table>'."\n";
        $xml .= '</Worksheet>'."\n";
        $xml .= '</Workbook>';

        return response()->streamDownload(fn () => print($xml), 'rapport-'.now()->format('Ymd').'.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    public function exportPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $data = $this->reportData();
        $store = \App\Models\Store::find(StoreContext::id());

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
            'data' => $data,
            'store' => $store,
        ]);

        return $pdf->download('rapport-'.now()->format('Ymd').'.pdf');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $data = $this->reportData();

        return view('livewire.reports.reports', $data);
    }
}
