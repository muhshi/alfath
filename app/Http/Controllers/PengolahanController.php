<?php

namespace App\Http\Controllers;

use App\Services\PengolahanExportService;
use App\Services\PetugasPerformanceRankingService;
use App\Services\Se2026MonitoringService;
use Illuminate\Http\Request;

class PengolahanController extends Controller
{
    public function __construct(
        protected Se2026MonitoringService $monitoringService,
        protected PetugasPerformanceRankingService $rankingService,
        protected PengolahanExportService $exportService
    ) {}

    /**
     * Display the SE2026 Data Petugas Dashboard Table with 4 Tabs (PPL, PML, SLS, Ranking Kinerja).
     */
    public function index(Request $request)
    {
        ini_set('memory_limit', '512M');

        $data = $this->monitoringService->getFilteredQuery($request);
        $records = $data['query']->get();

        $pmlRecords = $this->monitoringService->getPmlQuery($request, $data['selectedDate']);
        $slsRecords = $this->monitoringService->getSlsQuery($request, $data['selectedDate']);
        $rankingData = $this->rankingService->calculateRankingData($records, $data['selectedDate']);
        $kpiSummary = $this->monitoringService->getKpiSummary($records, $pmlRecords, $slsRecords);

        return view('dashboard-pengolahan', [
            'kecNameMap' => $this->monitoringService->getKecNameMap(),
            'availableDates' => $data['availableDates'],
            'selectedDate' => $data['selectedDate'],
            'search' => $data['search'],
            'kodekec' => $data['kodekec'],
            'sortBy' => $data['sortBy'],
            'sortDir' => $data['sortDir'],
            'perPage' => $data['perPage'],
            'records' => $records,
            'pmlRecords' => $pmlRecords,
            'slsRecords' => $slsRecords,
            'rankingRecords' => $rankingData['rankingRecords'],
            'dynamicTargetPct' => $rankingData['dynamicTargetPct'],
            'rankingSummary' => $rankingData['rankingSummary'],
            'kpiSummary' => $kpiSummary,
        ]);
    }

    /**
     * Export the filtered SE2026 Data Petugas to native Excel (.xlsx).
     */
    public function export(Request $request)
    {
        ini_set('memory_limit', '512M');

        $filtered = $this->monitoringService->getFilteredQuery($request);
        return $this->exportService->exportToExcel($filtered, $this->monitoringService->getKecNameMap());
    }
}
