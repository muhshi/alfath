<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Se2026AnomaliCatatanResource\Pages;
use App\Models\Se2026AnomaliCatatan;
use Filament\Actions;
use Filament\Actions\BulkActionGroup;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class Se2026AnomaliCatatanResource extends Resource
{
    protected static ?string $model = Se2026AnomaliCatatan::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Sensus Ekonomi 2026';
    protected static ?string $navigationLabel = 'Approval Anomali SLS';
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        $kecMap = [
            '3321010' => 'Mranggen',
            '3321020' => 'Karangawen',
            '3321030' => 'Guntur',
            '3321040' => 'Sayung',
            '3321050' => 'Karangtengah',
            '3321060' => 'Bonang',
            '3321070' => 'Demak',
            '3321080' => 'Wonosalam',
            '3321090' => 'Dempet',
            '3321091' => 'Kebonagung',
            '3321100' => 'Gajah',
            '3321110' => 'Karanganyar',
            '3321120' => 'Mijen',
            '3321130' => 'Wedung',
        ];

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_kec')
                    ->label('Kecamatan')
                    ->state(function (Se2026AnomaliCatatan $record) use ($kecMap): string {
                        $code = substr((string)$record->region_code, 0, 7);
                        return $kecMap[$code] ?? "Kec. {$code}";
                    })
                    ->badge()
                    ->color('info')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('region_code', $direction);
                    }),
                Tables\Columns\TextColumn::make('region_code')
                    ->label('Kode SLS (16 Digit)')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->copyable(),
                Tables\Columns\TextColumn::make('nama_petugas')
                    ->label('Petugas Pengaju')
                    ->searchable()
                    ->description(fn(Se2026AnomaliCatatan $record): ?string => $record->email_petugas),
                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan Klarifikasi')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Approval')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'approved' => '✅ Disetujui',
                        'pending' => '⏳ Menunggu Approval',
                        'rejected' => '❌ Ditolak',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('catatan_admin')
                    ->label('Catatan Admin')
                    ->placeholder('-')
                    ->wrap(),
                Tables\Columns\TextColumn::make('approved_by')
                    ->label('Penyetuju')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('kecamatan')
                    ->label('Kecamatan')
                    ->options($kecMap)
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('region_code', 'LIKE', $data['value'] . '%');
                        }
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => '⏳ Menunggu Approval',
                        'approved' => '✅ Disetujui',
                        'rejected' => '❌ Ditolak',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Persetujuan Catatan Anomali SLS')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui catatan klarifikasi anomali untuk SLS ini? Catatan ini akan ditampilkan di Dashboard.')
                    ->form([
                        Forms\Components\Textarea::make('catatan_admin')
                            ->label('Catatan Tambahan Admin (Opsional)')
                            ->placeholder('Misal: Disetujui berdasarkan konfirmasi lapangan.'),
                    ])
                    ->action(function (Se2026AnomaliCatatan $record, array $data): void {
                        $user = auth()->user();
                        $record->update([
                            'status' => 'approved',
                            'catatan_admin' => $data['catatan_admin'] ?? null,
                            'approved_at' => now(),
                            'approved_by' => $user ? $user->name : 'Admin',
                        ]);

                        Notification::make()
                            ->title('Catatan Anomali Disetujui')
                            ->body("Catatan untuk SLS {$record->region_code} berhasil disetujui.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Se2026AnomaliCatatan $record): bool => $record->status !== 'approved'),

                Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Catatan Anomali SLS')
                    ->form([
                        Forms\Components\Textarea::make('catatan_admin')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Jelaskan alasan penolakan agar petugas dapat memperbaiki.'),
                    ])
                    ->action(function (Se2026AnomaliCatatan $record, array $data): void {
                        $user = auth()->user();
                        $record->update([
                            'status' => 'rejected',
                            'catatan_admin' => $data['catatan_admin'],
                            'approved_at' => now(),
                            'approved_by' => $user ? $user->name : 'Admin',
                        ]);

                        Notification::make()
                            ->title('Catatan Anomali Ditolak')
                            ->body("Catatan untuk SLS {$record->region_code} telah ditolak.")
                            ->danger()
                            ->send();
                    })
                    ->visible(fn(Se2026AnomaliCatatan $record): bool => $record->status !== 'rejected'),

                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Actions\BulkAction::make('approve_selected')
                        ->label('Approve Terpilih')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Persetujuan Massal Catatan Anomali SLS')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui seluruh catatan anomali SLS yang dipilih?')
                        ->form([
                            Forms\Components\Textarea::make('catatan_admin_bulk')
                                ->label('Catatan Admin (Opsional untuk semua)')
                                ->placeholder('Misal: Disetujui secara massal.'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $user = auth()->user();
                            $name = $user ? $user->name : 'Admin';
                            $count = 0;
                            foreach ($records as $record) {
                                $updateData = [
                                    'status' => 'approved',
                                    'approved_at' => now(),
                                    'approved_by' => $name,
                                ];
                                if (!empty($data['catatan_admin_bulk'])) {
                                    $updateData['catatan_admin'] = $data['catatan_admin_bulk'];
                                }
                                $record->update($updateData);
                                $count++;
                            }

                            Notification::make()
                                ->title('Catatan Berhasil Disetujui')
                                ->body("Sebanyak {$count} catatan anomali SLS terpilih berhasil disetujui.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSe2026AnomaliCatatans::route('/'),
        ];
    }
}
