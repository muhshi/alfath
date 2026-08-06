<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ImportExcelPemutakhiranKeluarga extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:pemutakhiran-keluarga {file : Path to the Excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import file Excel Progres Pemutakhiran Keluarga ke database fasih tabel se2026_pemutakhiran_keluarga';

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

        $scriptPath = storage_path('app/python-scripts/process_pemutakhiran_keluarga_excel.py');
        if (!file_exists($scriptPath)) {
            $this->error("Script Python tidak ditemukan di: {$scriptPath}");
            return 1;
        }

        $pythonBin = 'python';

        $process = new Process([$pythonBin, $scriptPath, $filePath]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("Gagal menjalankan script Python: " . $process->getErrorOutput());
            return 1;
        }

        $output = trim($process->getOutput());
        $result = json_decode($output, true);

        if ($result && isset($result['success']) && $result['success']) {
            $this->info("✅ SUCCESS!");
            $this->info("Tanggal Data : " . ($result['tanggal_data'] ?? '-'));
            $this->info("Data Diimport : " . ($result['imported'] ?? 0));
            $this->info("Data Dilewati : " . ($result['skipped'] ?? 0));
            $this->info("Waktu Proses  : " . ($result['elapsed_seconds'] ?? 0) . " detik");
            return 0;
        } else {
            $this->error("❌ Error: " . ($result['error'] ?? $output));
            return 1;
        }
    }
}
