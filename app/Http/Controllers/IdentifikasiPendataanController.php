<?php

namespace App\Http\Controllers;

use App\Services\IdentifikasiPendataanService;
use Illuminate\Http\Request;

class IdentifikasiPendataanController extends Controller
{
    public function __construct(
        protected IdentifikasiPendataanService $identifikasiService
    ) {}

    /**
     * Tampilkan halaman identifikasi dan evaluasi kualitas hasil pendataan SLS.
     */
    public function index(Request $request)
    {
        set_time_limit(180);
        ini_set('memory_limit', '512M');

        $data = $this->identifikasiService->getIdentifikasiData($request);

        return view('identifikasi-pendataan', $data);
    }

    /**
     * Ekspor daftar SLS teridentifikasi / anomali ke Excel.
     */
    public function export(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $data = $this->identifikasiService->getIdentifikasiData($request);

        return $this->identifikasiService->exportToExcel($data);
    }
}
