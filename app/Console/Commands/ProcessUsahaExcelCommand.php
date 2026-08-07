<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ProcessUsahaExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usaha:import-excel {file : Path to the Excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengolah file Excel Progres Pendataan Usaha Perusahaan dan Usaha Keluarga ke Database FASIH';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return 1;
        }

        $this->info("Memulai pengolahan file Excel: {$filePath}");

        $exitCode = \Illuminate\Support\Facades\Artisan::call('import:usaha', [
            'file' => $filePath,
        ]);

        $output = \Illuminate\Support\Facades\Artisan::output();
        if ($exitCode === 0) {
            $this->info($output);
            return 0;
        } else {
            $this->error($output);
            return 1;
        }
    }
}
