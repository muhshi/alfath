<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Survey;
use App\Models\Team;
use App\Models\User;
use App\Models\Visit;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\DailyVisitorsChartWidget;
use App\Filament\Widgets\SurveyVisitsTableWidget;
use App\Filament\Widgets\CategoryDistributionChartWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_can_be_created_and_associated_with_survey()
    {
        $team = Team::create(['name' => 'Tim Pertanian']);
        $category = Category::create(['name' => 'Pertanian']);

        $survey = Survey::create([
            'name' => 'Survei Ubinan 2026',
            'team_id' => $team->id,
            'category_id' => $category->id,
            'start_periode' => now()->subDays(2),
            'end_periode' => now()->addDays(5),
            'metabase_dashboard_id' => 10,
        ]);

        $visit = Visit::create([
            'survey_id' => $survey->id,
            'ip_address' => '127.0.0.1',
            'path' => 'surveys/1/embed',
            'user_agent' => 'PHPUnit Test',
        ]);

        $this->assertDatabaseHas('visits', [
            'survey_id' => $survey->id,
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertEquals(1, $survey->visits()->count());
    }

    public function test_survey_embed_records_visit()
    {
        $team = Team::create(['name' => 'Tim Produksi']);
        $category = Category::create(['name' => 'Ekonomi']);

        $survey = Survey::create([
            'name' => 'Survei Industri',
            'team_id' => $team->id,
            'category_id' => $category->id,
            'start_periode' => now()->subDays(1),
            'end_periode' => now()->addDays(2),
            'metabase_dashboard_id' => 5,
        ]);

        $response = $this->get('/surveys/' . $survey->id . '/embed');
        $response->assertStatus(200);

        $this->assertDatabaseHas('visits', [
            'survey_id' => $survey->id,
        ]);
    }
}
