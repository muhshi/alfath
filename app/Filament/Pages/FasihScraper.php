<?php

namespace App\Filament\Pages;

use App\Models\GcPln;
use App\Models\ScraperCookie;
use App\Jobs\DispatchPythonScraper;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;

class FasihScraper extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.fasih-scraper';

    protected static ?string $title = 'Fasih Scraper';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Livewire\ScraperLogViewer::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GcPln::query()
                    ->selectRaw('MAX(id) as id, DATE(created_at) as date, count(*) as total_data')
                    ->groupBy('date')
                    ->orderByDesc('date')
            )
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal (created_at)')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('total_data')
                    ->label('Total Records Ditarik')
                    ->numeric(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('update-cookies')
                ->label('Ubah Cookies')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    Forms\Components\Textarea::make('cookie_string')
                        ->label('Cookie String')
                        ->required()
                        ->default(function () {
                            $cookie = ScraperCookie::first();
                            return $cookie ? $cookie->cookie_string : '';
                        }),
                ])
                ->action(function (array $data) {
                    $cookie = ScraperCookie::firstOrNew(['id' => 1]);
                    $cookie->cookie_string = $data['cookie_string'];
                    $cookie->save();

                    // Update file cookies.txt
                    $path = storage_path('app/python-scripts/gc-pln/cookies.txt');
                    if (!is_dir(dirname($path))) {
                        mkdir(dirname($path), 0755, true);
                    }
                    file_put_contents($path, $data['cookie_string']);

                    Notification::make()->title('Cookies berhasil diperbarui!')->success()->send();
                }),
                
            Actions\Action::make('sync-fasih')
                ->label('Tarik Data FASIH')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Mulai Sinkronisasi Data?')
                ->modalDescription('Proses ini akan menjalankan script Python di latar belakang untuk menarik data terbaru. Log dapat dilihat di bagian atas "Live Scraper Progress".')
                ->action(function () {
                    // Clear log file if exists
                    $logPath = storage_path('logs/scraper.log');
                    file_put_contents($logPath, '');

                    DispatchPythonScraper::dispatch();
                    
                    Notification::make()
                        ->title('Sinkronisasi Dimulai')
                        ->body('Proses scraping sedang berjalan di latar belakang.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
