<?php

namespace Database\Seeders;

use App\Models\Survey;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Survey::create([
            'name' => 'Wilkerstat Updating SE2026',
            'description' => 'Dashboard Wilkerstat Updating SE2026',
            'start_periode' => '2025-08-01',
            'end_periode' => '2025-08-31',
            'category_id' => '1', // Assuming category_id 1 exists
            'team_id' => '1', // Assuming team_id 1 exists
            'metabase_dashboard_id' => 5,

        ]);

    }
}
