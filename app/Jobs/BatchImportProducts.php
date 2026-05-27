<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class BatchImportProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        // Simulate parallel processing by handling in chunks
        foreach (array_chunk($this->data, 10) as $chunk) {
            foreach ($chunk as $item) {
                Product::updateOrCreate(
                    ['sku' => $item['sku']],
                    [
                        'name' => $item['name'],
                        'category_id' => $item['category_id'],
                        'unit_price' => $item['unit_price'],
                        'description' => $item['description'] ?? ''
                    ]
                );
            }
            // Artificial delay to simulate large processing
            usleep(500000); 
        }
    }
}
