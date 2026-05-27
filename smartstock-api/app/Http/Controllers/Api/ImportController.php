<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessProductImport;
use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobs = ImportJob::with('user:id,name')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls,txt', 'max:10240'],
            'type' => ['nullable', 'in:PRODUCTS,STOCKS'],
        ]);

        $path = $request->file('file')->store('imports', 'local');

        $job = ImportJob::create([
            'user_id' => $request->user()->id,
            'type' => $request->input('type', 'PRODUCTS'),
            'file_path' => $path,
            'status' => ImportJob::STATUS_QUEUED,
        ]);

        ProcessProductImport::dispatchSync($job->id);

        return response()->json([
            'success' => true,
            'message' => 'Import sedang diproses.',
            'data' => $job->fresh(),
        ], 202);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $job = ImportJob::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function downloadErrors(int $id, Request $request)
    {
        $job = ImportJob::where('user_id', $request->user()->id)->findOrFail($id);
        if (! $job->error_file_path || ! Storage::disk('local')->exists($job->error_file_path)) {
            return response()->json(['success' => false, 'message' => 'File error tidak ada.'], 404);
        }
        return response()->download(Storage::disk('local')->path($job->error_file_path), "errors-{$job->id}.csv");
    }

    public function template(): \Symfony\Component\HttpFoundation\Response
    {
        $headers = ['sku', 'name', 'category', 'unit', 'min_stock', 'price_buy', 'price_sell'];
        $sample = ['ELC-001', 'Contoh Produk', 'Elektronik', 'pcs', 10, 100000, 150000];

        $csv = implode(',', $headers)."\n".implode(',', array_map(fn ($v) => is_string($v) ? "\"{$v}\"" : $v, $sample));

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template-import.csv"',
        ]);
    }
}
