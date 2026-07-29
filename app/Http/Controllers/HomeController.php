<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $totalSurveys = \Illuminate\Support\Facades\Schema::hasTable('surveys') ? \App\Models\Survey::count() : 0;
        $totalTeams = \Illuminate\Support\Facades\Schema::hasTable('teams') ? \App\Models\Team::count() : 0;
        $activeSurveys = \Illuminate\Support\Facades\Schema::hasTable('surveys') 
            ? \App\Models\Survey::whereDate('start_periode', '<=', now())
                ->whereDate('end_periode', '>=', now())
                ->count()
            : 0;
        $recentSurveys = \Illuminate\Support\Facades\Schema::hasTable('surveys') 
            ? \App\Models\Survey::with('team')->latest()->take(5)->get()
            : collect();

        return view('home', compact('totalSurveys', 'totalTeams', 'activeSurveys', 'recentSurveys'));
    }
}
