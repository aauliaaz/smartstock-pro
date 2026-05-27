<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function health(): JsonResponse
    {
        // Pseudo-metric: pakai sys_getloadavg jika tersedia, else simulasi
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0.5, 0.4, 0.3];
        $memUsageBytes = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);

        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            $dbStatus = 'OK';
            $dbLatency = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $dbStatus = 'DOWN';
            $dbLatency = -1;
        }

        // Simulasi CPU/Memory pct (Windows tidak punya getloadavg)
        $cpuPercent = min(100, ($loadAvg[0] ?? 0.5) * 25);

        return response()->json([
            'success' => true,
            'data' => [
                'cpu_percent' => round($cpuPercent, 1),
                'memory_used_mb' => round($memUsageBytes / 1024 / 1024, 2),
                'memory_peak_mb' => round($memPeak / 1024 / 1024, 2),
                'memory_limit' => ini_get('memory_limit'),
                'response_time_ms' => $dbLatency,
                'db_status' => $dbStatus,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'uptime_check' => now()->toIso8601String(),
                'load_avg_1m' => $loadAvg[0] ?? null,
                'load_avg_5m' => $loadAvg[1] ?? null,
                'load_avg_15m' => $loadAvg[2] ?? null,
            ],
        ]);
    }
}
