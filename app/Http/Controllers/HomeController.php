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
        $totalSurveys = \App\Models\Survey::count();
        $totalTeams = \App\Models\Team::count();
        $activeSurveys = \App\Models\Survey::whereDate('start_periode', '<=', now())
            ->whereDate('end_periode', '>=', now())
            ->count();
        $recentSurveys = \App\Models\Survey::with('team')->latest()->take(5)->get();

        return view('home', compact('totalSurveys', 'totalTeams', 'activeSurveys', 'recentSurveys'));
    }
}
