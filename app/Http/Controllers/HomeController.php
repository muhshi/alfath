<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Survey;
use App\Models\Team;
use App\Models\Visit;

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
     * Show the application dashboard with sorted & paginated surveys.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        if (Schema::hasTable('visits')) {
            Visit::create([
                'survey_id' => null,
                'ip_address' => $request->ip(),
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $now = now();

        $totalSurveys = Schema::hasTable('surveys') ? Survey::count() : 0;
        $totalTeams = Schema::hasTable('teams') ? Team::count() : 0;
        $activeSurveys = Schema::hasTable('surveys') 
            ? Survey::whereDate('start_periode', '<=', $now)
                ->whereDate('end_periode', '>=', $now)
                ->count()
            : 0;

        if (Schema::hasTable('surveys')) {
            // Sort Order: 
            // 1. Status Aktif (0) -> Mendatang (1) -> Selesai (2)
            // 2. Tanggal Mulai Periode Terbaru (start_periode DESC)
            $surveys = Survey::with('team')
                ->orderByRaw("
                    CASE 
                        WHEN start_periode <= ? AND end_periode >= ? THEN 0
                        WHEN start_periode > ? THEN 1
                        ELSE 2
                    END ASC
                ", [$now, $now, $now])
                ->orderBy('start_periode', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(5);
        } else {
            $surveys = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 5);
        }

        return view('home', compact('totalSurveys', 'totalTeams', 'activeSurveys', 'surveys'));
    }
}
