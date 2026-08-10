<?php

namespace App\Http\Controllers;

use App\Services\PengolahanDataService;
use App\Services\PengolahanExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengolahanController extends Controller
{
    protected PengolahanDataService $dataService;
    protected PengolahanExportService $exportService;

    public function __construct(
        PengolahanDataService $dataService,
        PengolahanExportService $exportService
    ) {
        $this->dataService = $dataService;
        $this->exportService = $exportService;
    }

    /**
     * Display the SE2026 Data Petugas Dashboard Table.
     */
    public function index(Request $request)
    {
        $data = $this->dataService->getFilteredQuery($request);
        $records = $data['query']->get();
        $lightCounts = $this->dataService->getLightweightCounts($request, $data['selectedDate']);

        $kpiSummary = [
            'total_petugas' => $records->count(),
            'total_pml' => $lightCounts['total_pml'],
            'total_beban' => $records->sum('beban_saat_ini'),
            'total_submit' => $records->sum('total_submit'),
            'total_belum_dikerjakan' => $records->sum('belum_dikerjakan'),
            'total_usaha_ditemukan' => $records->sum('jumlah_usaha_ditemukan'),
            'total_usaha_keluarga' => $records->sum('jumlah_usaha_keluarga'),
            'total_keluarga_ditemukan' => $records->sum('jumlah_keluarga_ditemukan'),
            'total_muatan_murni' => $records->sum('muatan_murni'),
            'total_sls' => $lightCounts['total_sls'],
            'pct_overall_submit' => $records->sum('beban_saat_ini') > 0
                ? round(($records->sum('total_submit') / $records->sum('beban_saat_ini')) * 100, 2)
                : 0
        ];

        return view('dashboard-pengolahan', [
            'kecNameMap' => $this->dataService->kecNameMap,
            'availableDates' => $data['availableDates'],
            'selectedDate' => $data['selectedDate'],
            'search' => $data['search'],
            'kodekec' => $data['kodekec'],
            'sortBy' => $data['sortBy'],
            'sortDir' => $data['sortDir'],
            'perPage' => $data['perPage'],
            'records' => $records,
            'kpiSummary' => $kpiSummary,
        ]);
    }

    /**
     * AJAX endpoint: Server-side DataTables for PML (Pengawas) tab.
     */
    public function pmlData(Request $request): JsonResponse
    {
        return response()->json($this->dataService->getPmlDataTablesResponse($request));
    }

    /**
     * AJAX endpoint: Server-side DataTables for SLS (Alokasi) tab.
     */
    public function slsData(Request $request): JsonResponse
    {
        return response()->json($this->dataService->getSlsDataTablesResponse($request));
    }

    /**
     * Export the filtered SE2026 Data Petugas to native Excel (.xlsx).
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->exportService->exportToExcel($request);
    }
}
