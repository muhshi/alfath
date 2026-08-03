<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class CategoryDistributionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Survey per Kategori';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $categories = Category::withCount('surveys')->get();

        $labels = $categories->pluck('name')->toArray();
        $counts = $categories->pluck('surveys_count')->toArray();

        $colors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
            '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Survey',
                    'data' => $counts,
                    'backgroundColor' => array_slice($colors, 0, count($counts)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
