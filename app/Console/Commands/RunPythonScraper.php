<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class RunPythonScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menjalankan script Python GC-PLN untuk menarik data dari FASIH';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi data FASIH (GC-PLN)...');
        Log::info('Trigger scraper Python GC-PLN...');

        // Tentukan path absolut ke script Python
        $pythonScriptPath = storage_path('app/python-scripts/gc-pln/fasih_dashboard.py');
        $scriptDir = dirname($pythonScriptPath);
        $pythonBin = storage_path('app/python-scripts/gc-pln/venv/bin/python3');

        // Menjalankan script fasih_dashboard.py
        // Set timeout ke 0 (unlimited) karena bisa memakan waktu
        $process = new Process([$pythonBin, 'fasih_dashboard.py']);
        $process->setWorkingDirectory($scriptDir);
        $process->setTimeout(0); 

        try {
            $process->mustRun(function ($type, $buffer) {
                // Tampilkan log output Python secara realtime di terminal (jika dijalankan manual)
                $this->output->write($buffer);
                
                // Simpan ke log spesifik scraper.log agar bisa dibaca Livewire
                file_put_contents(storage_path('logs/scraper.log'), $buffer, FILE_APPEND);
            });

            $this->info('Sinkronisasi selesai!');
            file_put_contents(storage_path('logs/scraper.log'), "[COMPLETED] Scraper Python selesai dijalankan.\n", FILE_APPEND);
            
        } catch (ProcessFailedException $e) {
            $this->error('Proses gagal: ' . $e->getMessage());
            Log::error('ProcessFailedException: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
