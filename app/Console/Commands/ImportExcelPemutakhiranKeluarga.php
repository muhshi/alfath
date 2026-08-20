<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

class ImportExcelPemutakhiranKeluarga extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:pemutakhiran-keluarga {file : Path to the Excel file (.xlsx)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import sheet "KELUARGA" dari file Excel Progres Pemutakhiran Keluarga FASIH ke database fasih tabel se2026_pemutakhiran_keluarga';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return 1;
        }

        $this->info("📂 Membaca file Pemutakhiran Keluarga: {$file}");

        $reader = new Reader();
        $reader->open($file);

        $processed = 0;
        $tanggalData = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() === 'KELUARGA') {
                $processed += $this->importSheetKeluarga($sheet, $tanggalData);
            }
        }

        $reader->close();

        if ($processed === 0) {
            $this->error('❌ Tidak ditemukan sheet "KELUARGA" atau data SLS pada file ini.');
            return 1;
        }

        $this->info("✅ SUCCESS! Berhasil mengimpor {$processed} data Sub-SLS Pemutakhiran Keluarga." . ($tanggalData ? " (Tanggal Data: {$tanggalData})" : ''));

        return 0;
    }

    private function importSheetKeluarga($sheet, &$tanggalData): int
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = $connName ? DB::connection($connName) : DB::connection();

        $rowsToInsert = [];
        $rowCount = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $rowCount++;
            $cells = array_map(fn($cell) => trim((string) $cell->getValue()), $row->getCells());

            // Extract Tanggal Data from Row 2 if available (e.g. "Diperbarui: 6 Agu 2026, 06.16")
            if ($rowCount === 2 && !empty($cells[0])) {
                if (preg_match('/Diperbarui:\s*(\d{1,2})\s*([A-Za-z]+)\s*(\d{4})/i', $cells[0], $matches)) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $monthStr = strtolower($matches[2]);
                    $year = $matches[3];

                    $monthMap = [
                        'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
                        'mei' => '05', 'may' => '05', 'jun' => '06', 'jul' => '07',
                        'agu' => '08', 'aug' => '08', 'sep' => '09', 'okt' => '10',
                        'oct' => '10', 'nov' => '11', 'des' => '12', 'dec' => '12',
                    ];

                    $month = $monthMap[$monthStr] ?? '08';
                    $tanggalData = "{$year}-{$month}-{$day}";
                }
            }

            // Only process data rows starting after row 4
            if ($rowCount <= 4) {
                continue;
            }

            $kode = $cells[0] ?? '';
            $subSls = $cells[1] ?? '';

            // SLS Code must be 16 characters
            if (strlen($kode) !== 16 || !is_numeric($kode)) {
                continue;
            }

            $prelistAwal = (int) str_replace(['.', ','], '', $cells[2] ?? 0);
            $ditemukan = (int) str_replace(['.', ','], '', $cells[3] ?? 0);
            $pctDitemukan = (float) str_replace(',', '.', $cells[4] ?? 0);
            $keluargaBaru = (int) str_replace(['.', ','], '', $cells[5] ?? 0);
            $meninggal = (int) str_replace(['.', ','], '', $cells[6] ?? 0);
            $pctMeninggal = (float) str_replace(',', '.', $cells[7] ?? 0);
            $tidakEligible = (int) str_replace(['.', ','], '', $cells[8] ?? 0);
            $pctTidakEligible = (float) str_replace(',', '.', $cells[9] ?? 0);
            $tidakDitemukan = (int) str_replace(['.', ','], '', $cells[10] ?? 0);
            $pctTidakDitemukan = (float) str_replace(',', '.', $cells[11] ?? 0);
            $tidakDapatDitemui = (int) str_replace(['.', ','], '', $cells[12] ?? 0);
            $pctTidakDapatDitemui = (float) str_replace(',', '.', $cells[13] ?? 0);
            $totalHasilPendataan = (int) str_replace(['.', ','], '', $cells[14] ?? 0);
            $pctTotalHasilPendataan = (float) str_replace(',', '.', $cells[15] ?? 0);

            $rowsToInsert[] = [
                'kode' => $kode,
                'sub_sls' => $subSls,
                'prelist_awal' => $prelistAwal,
                'ditemukan' => $ditemukan,
                'persentase_ditemukan' => $pctDitemukan,
                'keluarga_baru' => $keluargaBaru,
                'meninggal' => $meninggal,
                'persentase_meninggal' => $pctMeninggal,
                'tidak_eligible' => $tidakEligible,
                'persentase_tidak_eligible' => $pctTidakEligible,
                'tidak_ditemukan' => $tidakDitemukan,
                'persentase_tidak_ditemukan' => $pctTidakDitemukan,
                'tidak_dapat_ditemui' => $tidakDapatDitemui,
                'persentase_tidak_dapat_ditemui' => $pctTidakDapatDitemui,
                'total_hasil_pendataan' => $totalHasilPendataan,
                'persentase_total_hasil_pendataan' => $pctTotalHasilPendataan,
                'tanggal_data' => $tanggalData,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        if (!empty($rowsToInsert)) {
            foreach (array_chunk($rowsToInsert, 500) as $chunk) {
                $db->table('se2026_pemutakhiran_keluarga')->upsert(
                    $chunk,
                    ['kode'],
                    [
                        'sub_sls', 'prelist_awal', 'ditemukan', 'persentase_ditemukan',
                        'keluarga_baru', 'meninggal', 'persentase_meninggal',
                        'tidak_eligible', 'persentase_tidak_eligible',
                        'tidak_dapat_ditemui', 'persentase_tidak_dapat_ditemui',
                        'tidak_ditemukan', 'persentase_tidak_ditemukan',
                        'total_hasil_pendataan', 'persentase_total_hasil_pendataan',
                        'tanggal_data', 'updated_at'
                    ]
                );
            }
        }

        try {
            \Illuminate\Support\Facades\Cache::store('file')->increment('se2026_dash_version');
        } catch (\Throwable $e) {}

        return count($rowsToInsert);
    }
}
