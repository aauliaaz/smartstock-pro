<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Product;
use App\Models\ReportJob;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenerateStockReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public int $reportJobId) {}

    public function handle(): void
    {
        $job = ReportJob::find($this->reportJobId);
        if (! $job) return;

        $job->update(['status' => ReportJob::STATUS_PROCESSING, 'started_at' => now()]);

        try {
            $data = match ($job->report_type) {
                'STOCK' => $this->stockReport($job->params ?? []),
                'MOVEMENT' => $this->movementReport($job->params ?? []),
                'TRANSFER' => $this->transferReport($job->params ?? []),
                'VALUATION' => $this->valuationReport($job->params ?? []),
                default => ['title' => 'Unknown', 'rows' => []],
            };

            $data['generated_at'] = now()->format('d M Y H:i');
            $data['generated_by'] = $job->user->name ?? 'System';

            $pdf = Pdf::loadView('pdf.stock-report', $data)->setPaper('A4', 'landscape');
            $filename = "reports/report-{$job->id}-".now()->format('YmdHis').'.pdf';
            Storage::disk('local')->put($filename, $pdf->output());

            $job->update([
                'status' => ReportJob::STATUS_DONE,
                'file_path' => $filename,
                'finished_at' => now(),
            ]);

            Notification::create([
                'user_id' => $job->user_id,
                'type' => 'REPORT',
                'severity' => 'INFO',
                'title' => 'Laporan siap diunduh',
                'message' => "Laporan {$job->report_type} #{$job->id} sudah selesai diproses.",
                'data' => ['report_job_id' => $job->id],
            ]);
        } catch (\Throwable $e) {
            $job->update(['status' => ReportJob::STATUS_FAILED, 'finished_at' => now()]);
            throw $e;
        }
    }

    private function stockReport(array $params): array
    {
        $rows = DB::table('product_warehouse_stocks as pws')
            ->join('products as p', 'p.id', '=', 'pws.product_id')
            ->join('warehouses as w', 'w.id', '=', 'pws.warehouse_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->when(! empty($params['warehouse_ids']), fn ($q) => $q->whereIn('pws.warehouse_id', $params['warehouse_ids']))
            ->when(! empty($params['category_ids']), fn ($q) => $q->whereIn('p.category_id', $params['category_ids']))
            ->where('p.is_active', true)
            ->select(
                'p.sku', 'p.name as product_name', 'c.name as category',
                'w.code as warehouse_code', 'w.name as warehouse_name',
                'p.unit', 'p.min_stock', 'pws.quantity',
                'p.price_buy', 'p.price_sell',
                DB::raw('pws.quantity * p.price_buy as total_value')
            )
            ->orderBy('w.name')
            ->orderBy('p.name')
            ->get();

        return [
            'title' => 'Laporan Stok Inventaris',
            'subtitle' => 'Per '.now()->format('d M Y'),
            'rows' => $rows,
            'summary' => [
                'total_items' => $rows->count(),
                'total_quantity' => (int) $rows->sum('quantity'),
                'total_value' => (float) $rows->sum('total_value'),
            ],
        ];
    }

    private function movementReport(array $params): array
    {
        $rows = StockMovement::with(['product:id,sku,name', 'warehouse:id,code,name', 'user:id,name'])
            ->when(! empty($params['date_from']), fn ($q) => $q->whereDate('movement_date', '>=', $params['date_from']))
            ->when(! empty($params['date_to']), fn ($q) => $q->whereDate('movement_date', '<=', $params['date_to']))
            ->when(! empty($params['warehouse_ids']), fn ($q) => $q->whereIn('warehouse_id', $params['warehouse_ids']))
            ->orderByDesc('movement_date')
            ->limit(1000)
            ->get();

        return [
            'title' => 'Laporan Mutasi Stok',
            'subtitle' => ($params['date_from'] ?? '-').' s/d '.($params['date_to'] ?? '-'),
            'rows' => $rows,
            'summary' => ['total_movements' => $rows->count()],
        ];
    }

    private function transferReport(array $params): array
    {
        $rows = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'requester', 'items'])
            ->when(! empty($params['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $params['date_from']))
            ->when(! empty($params['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $params['date_to']))
            ->orderByDesc('created_at')
            ->get();

        return [
            'title' => 'Laporan Transfer Antar Gudang',
            'subtitle' => ($params['date_from'] ?? '-').' s/d '.($params['date_to'] ?? '-'),
            'rows' => $rows,
            'summary' => ['total_transfers' => $rows->count()],
        ];
    }

    private function valuationReport(array $params): array
    {
        $rows = DB::table('product_warehouse_stocks as pws')
            ->join('products as p', 'p.id', '=', 'pws.product_id')
            ->join('warehouses as w', 'w.id', '=', 'pws.warehouse_id')
            ->where('p.is_active', true)
            ->where('pws.quantity', '>', 0)
            ->select(
                'p.sku', 'p.name as product_name',
                'w.name as warehouse_name',
                'pws.quantity', 'p.price_buy',
                DB::raw('pws.quantity * p.price_buy as total_value')
            )
            ->orderByDesc(DB::raw('pws.quantity * p.price_buy'))
            ->limit(500)
            ->get();

        return [
            'title' => 'Valuasi Inventaris',
            'subtitle' => 'Per '.now()->format('d M Y'),
            'rows' => $rows,
            'summary' => [
                'total_items' => $rows->count(),
                'total_value' => (float) $rows->sum('total_value'),
            ],
        ];
    }
}
