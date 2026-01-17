<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Survey;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_real_metrics_on_the_landing_page()
    {
        // Setup data
        $team = Team::create(['name' => 'Tim Test']);
        $category = Category::create(['name' => 'Kategori Test']);

        Survey::create([
            'name' => 'Survey Aktif',
            'team_id' => $team->id,
            'category_id' => $category->id,
            'start_periode' => now()->subDays(1),
            'end_periode' => now()->addDays(1),
            'metabase_dashboard_id' => 1,
        ]);

        Survey::create([
            'name' => 'Survey Selesai',
            'team_id' => $team->id,
            'category_id' => $category->id,
            'start_periode' => now()->subDays(10),
            'end_periode' => now()->subDays(5),
            'metabase_dashboard_id' => 2,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');

        // Assert data passed to view
        $response->assertViewHas('totalSurveys', 2);
        $response->assertViewHas('totalTeams', 1);
        $response->assertViewHas('activeSurveys', 1);
        $response->assertViewHas('recentSurveys');

        // Assert content
        $response->assertSee('2 Total Survey');
        $response->assertSee('1 Tim Kerja');
        $response->assertSee('1 Survey Aktif');
        $response->assertSee('Survey Aktif');
        $response->assertSee('Tim Test');
    }
}
