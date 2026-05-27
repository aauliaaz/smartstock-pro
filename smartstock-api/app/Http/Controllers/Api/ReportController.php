<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateStockReport;
use App\Models\ReportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobs = ReportJob::where('user_id', $request->user()->id)
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

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'report_type' => ['required', 'in:STOCK,MOVEMENT,TRANSFER,VALUATION'],
            'params' => ['nullable', 'array'],
            'format' => ['nullable', 'in:PDF,EXCEL'],
        ]);

        $job = ReportJob::create([
            'user_id' => $request->user()->id,
            'report_type' => $data['report_type'],
            'params' => $data['params'] ?? [],
            'format' => $data['format'] ?? 'PDF',
            'status' => ReportJob::STATUS_QUEUED,
        ]);

        // Sync dispatch for demo (in production, queue:work would handle this)
        GenerateStockReport::dispatchSync($job->id);

        return response()->json([
            'success' => true,
            'message' => 'Laporan sedang diproses.',
            'data' => $job->fresh(),
        ], 202);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $job = ReportJob::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function download(int $id, Request $request)
    {
        $job = ReportJob::where('user_id', $request->user()->id)->findOrFail($id);
        if (! $job->file_path || ! Storage::disk('local')->exists($job->file_path)) {
            return response()->json(['success' => false, 'message' => 'File tidak ditemukan.'], 404);
        }
        return response()->download(Storage::disk('local')->path($job->file_path), "report-{$job->id}.pdf");
    }
}
