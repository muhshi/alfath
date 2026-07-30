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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.fasih-scraper';

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
            )
            ->columns([
                TextColumn::make('unitup')
                    ->label('UNITUP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email_biller')
                    ->label('Email Biller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('open_count')
                    ->label('OPEN')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('submitted_count')
                    ->label('SUBMITTED')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rejected_count')
                    ->label('REJECTED')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fetch_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('fetched_at')
                    ->label('Terakhir Ditarik')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('fetch_date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('update-cookies')
                ->label('Ubah Cookies & CSRF')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    Forms\Components\Textarea::make('cookie_string')
                        ->label('Cookie String')
                        ->required()
                        ->rows(4)
                        ->default(function () {
                            $cookie = ScraperCookie::first();
                            return $cookie ? $cookie->cookie_string : '';
                        }),
                    Forms\Components\Textarea::make('csrf_token')
                        ->label('X-CSRFToken')
                        ->helperText('Ambil dari header X-CSRFToken di request browser (bukan dari cookies)')
                        ->rows(2)
                        ->default(function () {
                            $path = storage_path('app/python-scripts/gc-pln/csrf_token.txt');
                            return file_exists($path) ? file_get_contents($path) : '';
                        }),
                ])
                ->action(function (array $data) {
                    $cookie = ScraperCookie::firstOrNew(['id' => 1]);
                    $cookie->cookie_string = $data['cookie_string'];
                    $cookie->save();

                    // Update file cookies.txt
                    $cookiePath = storage_path('app/python-scripts/gc-pln/cookies.txt');
                    if (!is_dir(dirname($cookiePath))) {
                        mkdir(dirname($cookiePath), 0755, true);
                    }
                    file_put_contents($cookiePath, $data['cookie_string']);

                    // Update file csrf_token.txt
                    if (!empty($data['csrf_token'])) {
                        $csrfPath = storage_path('app/python-scripts/gc-pln/csrf_token.txt');
                        file_put_contents($csrfPath, trim($data['csrf_token']));
                    }

                    Notification::make()->title('Cookies & CSRF Token berhasil diperbarui!')->success()->send();
                }),
                
            Actions\Action::make('sync-fasih')
                ->label('Tarik Data FASIH')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Mulai Sinkronisasi Data?')
                ->modalDescription('Proses ini akan menjalankan script Python untuk menarik data terbaru dari FASIH Dashboard API. Log dapat dilihat di bagian atas halaman.')
                ->action(function () {
                    // Clear log file if exists
                    $logPath = storage_path('logs/scraper.log');
                    file_put_contents($logPath, '');

                    DispatchPythonScraper::dispatch();
                    
                    Notification::make()
                        ->title('Sinkronisasi Dimulai')
                        ->body('Proses penarikan data sedang berjalan di latar belakang.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
