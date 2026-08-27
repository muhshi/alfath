<?php

namespace App\Http\Controllers;

use App\Services\PetugasTimelineService;
use Illuminate\Http\Request;

class TimelinePetugasController extends Controller
{
    public function __construct(
        protected PetugasTimelineService $timelineService
    ) {}

    /**
     * Display the Petugas Daily Submit Timeline & Heatmap
     */
    public function index(Request $request)
    {
        ini_set('memory_limit', '512M');

        $data = $this->timelineService->getTimelineData($request);

        return view('dashboard-timeline-petugas', $data);
    }
}
