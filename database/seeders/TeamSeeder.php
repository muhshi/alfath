<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Team::create(['name' => 'IPDS']);
        \App\Models\Team::create(['name' => 'Sosial']);
        \App\Models\Team::create(['name' => 'Distribusi']);
        \App\Models\Team::create(['name' => 'Produksi']);
        \App\Models\Team::create(['name' => 'Neraca']);
    }
}
