<?php

namespace App\Filament\Widgets;

use App\Models\Survey;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SurveyVisitsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Tabel Kunjungan per Dashboard Monitoring';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $today = Carbon::today();

        return $table
            ->query(
                Survey::query()
                    ->with(['category', 'team'])
                    ->withCount([
                        'visits',
                        'visits as visits_today_count' => function (Builder $query) use ($today) {
                            $query->whereDate('created_at', $today);
                        }
                    ])
            )
            ->defaultSort('visits_count', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Survey / Dashboard')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Survey $record) => route('surveys.embed', $record))
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('team.name')
                    ->label('Tim Pengelola')
                    ->sortable(),

                Tables\Columns\TextColumn::make('visits_today_count')
                    ->label('Hari Ini')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('visits_count')
                    ->label('Total Kunjungan')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Periode')
                    ->state(function (Survey $record): string {
                        $now = now();
                        if ($record->start_periode && $record->end_periode) {
                            if ($record->start_periode <= $now && $record->end_periode >= $now) {
                                return 'Aktif';
                            } elseif ($record->start_periode > $now) {
                                return 'Mendatang';
                            }
                        }
                        return 'Selesai';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Mendatang' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('start_periode')
                    ->label('Periode')
                    ->formatStateUsing(fn (Survey $record) => ($record->start_periode ? $record->start_periode->format('d/m/Y') : '-') . ' s.d. ' . ($record->end_periode ? $record->end_periode->format('d/m/Y') : '-')),
            ]);
    }
}
