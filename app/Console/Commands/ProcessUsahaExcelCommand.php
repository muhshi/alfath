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

        $scriptPath = storage_path('app/python-scripts/process_usaha_excel.py');
        if (!file_exists($scriptPath)) {
            $this->error("Script Python tidak ditemukan di: {$scriptPath}");
            return 1;
        }

        // System Python executable
        $pythonBin = 'python';

        $process = new Process([$pythonBin, $scriptPath, $filePath]);
        $process->setTimeout(300); // 5 mins max
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("Gagal menjalankan script Python: " . $process->getErrorOutput());
            return 1;
        }

        $output = trim($process->getOutput());
        $result = json_decode($output, true);

        if ($result && isset($result['success']) && $result['success']) {
            $this->info("SUCCESS! Data tanggal: " . ($result['tanggal_data'] ?? '-'));
            $this->info("Usaha Perusahaan terproses: " . ($result['count_perusahaan'] ?? 0));
            $this->info("Usaha Keluarga terproses: " . ($result['count_keluarga'] ?? 0));
            return 0;
        } else {
            $this->error("Error: " . ($result['error'] ?? $output));
            return 1;
        }
    }
}
