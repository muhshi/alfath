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

            Actions\Action::make('upload-excel-usaha')
                ->label('Upload Excel Usaha')
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('excel_file')
                        ->label('File Excel Export Progres Pendataan (.xlsx)')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required()
                        ->disk('local')
                        ->directory('uploads/excel-usaha'),
                ])
                ->action(function (array $data) {
                    $relativeFilePath = $data['excel_file'];
                    $fullPath = storage_path('app/' . $relativeFilePath);

                    if (!file_exists($fullPath)) {
                        Notification::make()
                            ->title('Gagal')
                            ->body("File Excel tidak ditemukan pada server di: {$fullPath}")
                            ->danger()
                            ->send();
                        return;
                    }

                    $scriptPath = storage_path('app/python-scripts/process_usaha_excel.py');
                    $pythonBin = 'python';

                    $process = new \Symfony\Component\Process\Process([$pythonBin, $scriptPath, $fullPath]);
                    $process->setTimeout(300);
                    $process->run();

                    if (!$process->isSuccessful()) {
                        Notification::make()
                            ->title('Proses Gagal')
                            ->body($process->getErrorOutput())
                            ->danger()
                            ->send();
                        return;
                    }

                    $output = trim($process->getOutput());
                    $res = json_decode($output, true);

                    if ($res && isset($res['success']) && $res['success']) {
                        $tgl = $res['tanggal_data'] ?? '-';
                        $upCount = number_format($res['count_perusahaan'] ?? 0);
                        $ukCount = number_format($res['count_keluarga'] ?? 0);

                        Notification::make()
                            ->title('Import Excel Berhasil!')
                            ->body("Tanggal Data: {$tgl}\n• Usaha Perusahaan: {$upCount} baris\n• Usaha Keluarga: {$ukCount} baris")
                            ->success()
                            ->send();
                    } else {
                        $err = $res['error'] ?? $output;
                        Notification::make()
                            ->title('Gagal Mengolah Excel')
                            ->body($err)
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
