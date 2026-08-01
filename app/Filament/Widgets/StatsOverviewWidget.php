<?php

namespace App\Filament\Widgets;

use App\Models\Survey;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $visitsToday = Visit::whereDate('created_at', $today)->count();
        $visitsYesterday = Visit::whereDate('created_at', $yesterday)->count();

        // Calculate difference & percentage
        $diff = $visitsToday - $visitsYesterday;
        $trendIcon = $diff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendColor = $diff >= 0 ? 'success' : 'danger';
        $trendText = ($diff >= 0 ? '+' : '') . $diff . ' dari kemarin';

        // Total survey visits
        $totalSurveyVisits = Visit::whereNotNull('survey_id')->count();

        // Active surveys
        $now = now();
        $activeSurveysCount = Survey::whereDate('start_periode', '<=', $now)
            ->whereDate('end_periode', '>=', $now)
            ->count();

        // Top survey
        $topSurvey = Survey::withCount('visits')
            ->orderBy('visits_count', 'desc')
            ->first();

        $topSurveyName = $topSurvey ? $topSurvey->name : 'Belum Ada Data';
        $topSurveyVisits = $topSurvey ? $topSurvey->visits_count . ' kunjungan' : '0';

        return [
            Stat::make('Pengunjung Hari Ini', number_format($visitsToday))
                ->description($trendText)
                ->descriptionIcon($trendIcon)
                ->color($trendColor),

            Stat::make('Total Kunjungan Survey', number_format($totalSurveyVisits))
                ->description('Total pembukaan detail/embed survey')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),

            Stat::make('Survey Aktif', number_format($activeSurveysCount))
                ->description('Survey berjalan periode ini')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('warning'),

            Stat::make('Survey Terpopuler', $topSurveyName)
                ->description($topSurveyVisits)
                ->descriptionIcon('heroicon-m-fire')
                ->color('primary'),
        ];
    }
}
