<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\SheetInterface;
use OpenSpout\Reader\XLSX\Reader;

class ImportExcelPemutakhiranKeluarga extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:pemutakhiran-keluarga {file : Path to the Excel file (.xlsx)}
                            {--no-truncate : Skip truncating existing data before import}
                            {--date= : Tanggal data (YYYY-MM-DD)}';

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

        $file = (string) $this->argument('file');

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
            $sheetName = trim(strtoupper($sheet->getName()));
            // Hanya import sheet utama 'KELUARGA', BUKAN 'ANGGOTA KELUARGA' atau 'KELUARGA KHUSUS'
            if ($sheetName === 'KELUARGA') {
                $processed = $this->importSheetKeluarga($sheet, $tanggalData);
                break;
            }
        }

        $reader->close();

        if ($processed === 0) {
            $this->error('❌ Tidak ditemukan sheet "KELUARGA" atau data SLS pada file ini.');
            return 1;
        }

        try {
            Cache::store('file')->increment('se2026_dash_version');
        } catch (\Throwable $e) {}

        $this->info("✅ SUCCESS! Berhasil mengimpor {$processed} data Sub-SLS Pemutakhiran Keluarga." . ($tanggalData ? " (Tanggal Data: {$tanggalData})" : ''));

        return 0;
    }

    /**
     * Process and import the KELUARGA sheet.
     */
    private function importSheetKeluarga(SheetInterface $sheet, ?string &$tanggalData): int
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = $connName ? DB::connection($connName) : DB::connection();

        if (!$this->option('no-truncate')) {
            $db->table('se2026_pemutakhiran_keluarga')->truncate();
            $this->warn('   🗑️  Tabel se2026_pemutakhiran_keluarga di-truncate.');
        }

        $rowsToInsert = [];
        $rowCount = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $rowCount++;
            $cells = $row->toArray();

            // Validasi baris 1 adalah header Progres Pemutakhiran Keluarga
            if ($rowCount === 1 && !empty($cells[0])) {
                $r1 = strtoupper((string) $cells[0]);
                if (!str_contains($r1, 'PEMUTAKHIRAN KELUARGA')) {
                    $this->error("❌ File ini bukan file Progres Pemutakhiran Keluarga! Terdeteksi: {$r1}");
                    return 0;
                }
            }

            // Extract Tanggal Data from Row 2 if available or from --date option
            if ($rowCount === 2) {
                if ($this->option('date')) {
                    $tanggalData = (string) $this->option('date');
                } elseif (!empty($cells[0])) {
                    $cellText = (string) $cells[0];
                    if (preg_match('/Diperbarui:\s*(\d{1,2})\s*([A-Za-z]+)\s*(\d{4})/i', $cellText, $matches)) {
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
            }

            // Only process data rows starting after row 4
            if ($rowCount <= 4) {
                continue;
            }

            $kode = trim((string) ($cells[0] ?? ''));
            $subSls = trim((string) ($cells[1] ?? ''));

            // Clean trailing decimal (.0) if Excel exported numeric cell as float string
            $kode = preg_replace('/\.0+$/', '', $kode);

            // SLS Code must be 16 characters numeric
            if (!preg_match('/^\d{16}$/', $kode)) {
                continue;
            }

            $prelistAwal = $this->cleanNumeric($cells[2] ?? 0);
            $ditemukan = $this->cleanNumeric($cells[3] ?? 0);
            $pctDitemukan = $this->cleanFloat($cells[4] ?? 0);
            $keluargaBaru = $this->cleanNumeric($cells[5] ?? 0);
            $meninggal = $this->cleanNumeric($cells[6] ?? 0);
            $pctMeninggal = $this->cleanFloat($cells[7] ?? 0);
            $tidakEligible = $this->cleanNumeric($cells[8] ?? 0);
            $pctTidakEligible = $this->cleanFloat($cells[9] ?? 0);
            $tidakDitemukan = $this->cleanNumeric($cells[10] ?? 0);
            $pctTidakDitemukan = $this->cleanFloat($cells[11] ?? 0);
            $tidakDapatDitemui = $this->cleanNumeric($cells[12] ?? 0);
            $pctTidakDapatDitemui = $this->cleanFloat($cells[13] ?? 0);
            $totalHasilPendataan = $this->cleanNumeric($cells[14] ?? 0);
            $pctTotalHasilPendataan = $this->cleanFloat($cells[15] ?? 0);

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

        return count($rowsToInsert);
    }

    /**
     * Clean integer values.
     */
    private function cleanNumeric(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $cleaned = str_replace(['.', ',', ' '], '', (string) $value);
        return is_numeric($cleaned) ? (int) $cleaned : 0;
    }

    /**
     * Clean float values.
     */
    private function cleanFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $cleaned = str_replace(',', '.', trim((string) $value));
        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }
}
