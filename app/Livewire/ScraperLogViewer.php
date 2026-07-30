<?php

namespace App\Livewire;

use Filament\Widgets\Widget;

class ScraperLogViewer extends Widget
{
    protected string $view = 'livewire.scraper-log-viewer';
    
    // We override getColumnSpan so it defaults to taking full width
    protected int | string | array $columnSpan = 'full';

    public $logs = '';

    public function render(): \Illuminate\Contracts\View\View
    {
        $logPath = storage_path('logs/scraper.log');
        if (file_exists($logPath)) {
            // Read last 30 lines
            $output = shell_exec('tail -n 30 ' . escapeshellarg($logPath));
            $this->logs = $output ?: '';
        } else {
            $this->logs = 'Belum ada log dari scraper (proses belum jalan).';
        }

        return view($this->view);
    }
}
