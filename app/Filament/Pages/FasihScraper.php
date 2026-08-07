<?php

namespace App\Filament\Pages;

use App\Models\UsahaPerusahaan;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class FasihScraper extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected string $view = 'filament.pages.fasih-scraper';

    protected static ?string $title = 'Import Excel Usaha';

    protected static ?string $navigationLabel = 'Import Excel Usaha';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UsahaPerusahaan::query()
            )
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode SLS')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sub_sls')
                    ->label('Wilayah / Sub SLS')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jumlah_prelist_usaha')
                    ->label('Prelist')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status___ditemukan')
                    ->label('Ditemukan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status___tutup')
                    ->label('Tutup')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status___baru')
                    ->label('Baru')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('jumlah_usaha_bku')
                    ->label('Usaha BKU')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tanggal_data')
                    ->label('Tanggal Data')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('tanggal_data', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('upload-excel-usaha')
                ->label('Upload Excel Usaha')
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('excel_file')
                        ->label('File Excel Export Progres Pendataan (.xlsx)')
                        ->maxSize(65536) // Allow up to 64 MB
                        ->helperText('File format .xlsx. Maksimal ukuran 64 MB.')
                        ->required()
                        ->disk('local')
                        ->directory('uploads/excel-usaha'),
                ])
                ->action(function (array $data) {
                    set_time_limit(600);
                    ini_set('memory_limit', '512M');

                    $relativeFilePath = $data['excel_file'];
                    $fullPath = Storage::disk('local')->path($relativeFilePath);

                    if (!file_exists($fullPath)) {
                        Notification::make()
                            ->title('Gagal')
                            ->body("File Excel tidak ditemukan pada server di: {$fullPath}")
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $exitCode = Artisan::call('import:usaha', [
                            'file' => $fullPath,
                        ]);

                        $output = Artisan::output();

                        if ($exitCode === 0) {
                            Notification::make()
                                ->title('Import Excel Berhasil!')
                                ->body($output ?: 'Data Excel Usaha Perusahaan dan Usaha Keluarga telah berhasil diimpor.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Mengolah Excel')
                                ->body($output ?: 'Terjadi kesalahan saat memproses file Excel.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Mengolah Excel')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
