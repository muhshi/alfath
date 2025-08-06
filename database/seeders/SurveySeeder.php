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
            'name' => 'Survey Ekonomi Q1-2024',
            'description' => 'Dashboard ekonomi triwulan 1',
            'start_periode' => '2024-01-01',
            'end_periode' => '2024-03-31',
            'metabase_dashboard_id' => 6,
            'metabase_params' => ['tahun' => 2024],
            'img_survey' => 'surveys/ekonomi.jpg',
        ]);

    }
}
