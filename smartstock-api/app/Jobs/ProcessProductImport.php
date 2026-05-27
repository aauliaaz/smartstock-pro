<?php

namespace App\Jobs;

use App\Imports\ProductsImport;
use App\Models\ImportJob;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $importJobId) {}

    public function handle(): void
    {
        $job = ImportJob::find($this->importJobId);
        if (! $job) return;

        $job->update(['status' => ImportJob::STATUS_PROCESSING, 'started_at' => now()]);

        try {
            $import = new ProductsImport();
            Excel::import($import, Storage::disk('local')->path($job->file_path));

            $errorFilePath = null;
            if (count($import->errors) > 0) {
                $errorFilePath = "imports/errors-{$job->id}.csv";
                $csv = "line,sku,name,error\n";
                foreach ($import->errors as $err) {
                    $csv .= sprintf(
                        "%d,\"%s\",\"%s\",\"%s\"\n",
                        $err['line'],
                        addslashes($err['sku']),
                        addslashes($err['name']),
                        addslashes($err['error'])
                    );
                }
                Storage::disk('local')->put($errorFilePath, $csv);
            }

            $job->update([
                'status' => ImportJob::STATUS_DONE,
                'success_rows' => $import->success,
                'error_rows' => $import->failed,
                'processed_rows' => $import->success + $import->failed,
                'total_rows' => $import->success + $import->failed,
                'error_file_path' => $errorFilePath,
                'finished_at' => now(),
            ]);

            Notification::create([
                'user_id' => $job->user_id,
                'type' => 'IMPORT',
                'severity' => $import->failed > 0 ? 'WARNING' : 'INFO',
                'title' => 'Import selesai',
                'message' => "Berhasil: {$import->success}, Gagal: {$import->failed}",
                'data' => ['import_job_id' => $job->id],
            ]);
        } catch (\Throwable $e) {
            $job->update(['status' => ImportJob::STATUS_FAILED, 'finished_at' => now()]);
            throw $e;
        }
    }
}
