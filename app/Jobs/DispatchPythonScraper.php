<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class DispatchPythonScraper implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Tambahkan timeout yang panjang (misalnya 4 jam / 14400 detik)
    public $timeout = 14400;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Memanggil Artisan Command secara synchronous di dalam worker Queue
        Artisan::call('scraper:run');
    }
}
