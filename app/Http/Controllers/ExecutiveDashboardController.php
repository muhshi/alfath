<?php

namespace App\Http\Controllers;

use App\Services\ExecutiveDashboardService;
use Illuminate\Http\Request;

class ExecutiveDashboardController extends Controller
{
    protected ExecutiveDashboardService $dashboardService;

    public function __construct(ExecutiveDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the Executive Public Dashboard for Sensus Ekonomi 2026.
     */
    public function index()
    {
        $data = $this->dashboardService->getDashboardData();

        return view('dashboard-se2026', $data);
    }
}
