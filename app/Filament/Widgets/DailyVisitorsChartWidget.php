<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DailyVisitorsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Grafik Pengunjung Harian (14 Hari Terakhir)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect();
        $visitCounts = collect();

        // Loop for the last 14 days
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $label = $date->translatedFormat('d M');

            $count = Visit::whereDate('created_at', $dateString)->count();

            $days->push($label);
            $visitCounts->push($count);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pengunjung',
                    'data' => $visitCounts->toArray(),
                    'fill' => 'start',
                    'tension' => 0.4,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
            'labels' => $days->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
