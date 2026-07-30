<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

class ImportExcelUsaha extends Command
{
    protected $signature = 'import:usaha {file : Path to the Excel file (.xlsx)}
                            {--no-truncate : Skip truncating existing data before import}';

    protected $description = 'Import sheet "USAHA PERUSAHAAN" dan "USAHA KELUARGA" dari file Excel FASIH ke database fasih';

    private int $headerRow = 5; // Row number where column numbers (1), (2), etc. are located. Data starts after this.

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return 1;
        }

        $this->info("📂 Membaca file: {$file}");
        $this->newLine();

        $reader = new Reader();
        $reader->open($file);

        $sheetsProcessed = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            $name = $sheet->getName();

            if ($name === 'USAHA PERUSAHAAN') {
                $this->importUsahaPerusahaan($sheet);
                $sheetsProcessed++;
            } elseif ($name === 'USAHA KELUARGA') {
                $this->importUsahaKeluarga($sheet);
                $sheetsProcessed++;
            }
        }

        $reader->close();

        if ($sheetsProcessed === 0) {
            $this->error('❌ Tidak ditemukan sheet "USAHA PERUSAHAAN" atau "USAHA KELUARGA" di file ini.');
            return 1;
        }

        $this->newLine();
        $this->info('✅ Import selesai!');

        return 0;
    }

    private function importUsahaPerusahaan($sheet): void
    {
        $this->info('📊 Importing sheet: USAHA PERUSAHAAN...');

        $db = DB::connection('fasih');
        $tanggalData = now()->toDateString();

        if (!$this->option('no-truncate')) {
            $db->table('usaha_perusahaan')->truncate();
            $this->warn('   🗑️  Tabel usaha_perusahaan di-truncate.');
        }

        $rowNum = 0;
        $imported = 0;
        $skipped = 0;
        $batch = [];

        foreach ($sheet->getRowIterator() as $row) {
            $rowNum++;

            // Skip header rows (rows 1-5)
            if ($rowNum <= $this->headerRow) {
                continue;
            }

            $cells = $row->toArray();
            $kode = trim((string) ($cells[0] ?? ''));
            $subSls = trim((string) ($cells[1] ?? ''));

            // Skip empty rows or summary rows
            if (empty($kode) || $kode === '' || str_contains($subSls, 'INDONESIA')
                || str_contains($subSls, 'JAWA') || str_contains($subSls, 'TOTAL')) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'kode' => $kode,
                'sub_sls' => $subSls,
                'jumlah_prelist_usaha' => $this->cleanNumeric($cells[2] ?? null),
                'status___ditemukan' => $this->cleanNumeric($cells[3] ?? null),
                'status___persentase_ditemukan' => $this->cleanNumeric($cells[4] ?? null),
                'status___tutup' => $this->cleanNumeric($cells[5] ?? null),
                'status___persentase_tutup' => $this->cleanNumeric($cells[6] ?? null),
                'status___ganda' => $this->cleanNumeric($cells[7] ?? null),
                'status___persentase_ganda' => $this->cleanNumeric($cells[8] ?? null),
                'status___tidak_ditemukan' => $this->cleanNumeric($cells[9] ?? null),
                'status___persentase_tidak_ditemukan' => $this->cleanNumeric($cells[10] ?? null),
                'status___baru' => $this->cleanNumeric($cells[11] ?? null),
                'status___persentase_baru' => $this->cleanNumeric($cells[12] ?? null),
                'jumlah_usaha_bku' => $this->cleanNumeric($cells[13] ?? null),
                'persentase_usaha_bku' => $this->cleanNumeric($cells[14] ?? null),
                'tanggal_data' => $tanggalData,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $imported++;

            // Upsert in batches of 500
            if (count($batch) >= 500) {
                $db->table('usaha_perusahaan')->upsert($batch, ['kode'], [
                    'sub_sls', 'jumlah_prelist_usaha',
                    'status___ditemukan', 'status___persentase_ditemukan',
                    'status___tutup', 'status___persentase_tutup',
                    'status___ganda', 'status___persentase_ganda',
                    'status___tidak_ditemukan', 'status___persentase_tidak_ditemukan',
                    'status___baru', 'status___persentase_baru',
                    'jumlah_usaha_bku', 'persentase_usaha_bku',
                    'tanggal_data', 'updated_at',
                ]);
                $batch = [];
                $this->output->write("\r   ⏳ Imported: {$imported} rows...");
            }
        }

        // Upsert remaining
        if (!empty($batch)) {
            $db->table('usaha_perusahaan')->upsert($batch, ['kode'], [
                'sub_sls', 'jumlah_prelist_usaha',
                'status___ditemukan', 'status___persentase_ditemukan',
                'status___tutup', 'status___persentase_tutup',
                'status___ganda', 'status___persentase_ganda',
                'status___tidak_ditemukan', 'status___persentase_tidak_ditemukan',
                'status___baru', 'status___persentase_baru',
                'jumlah_usaha_bku', 'persentase_usaha_bku',
                'tanggal_data', 'updated_at',
            ]);
        }

        $this->newLine();
        $this->info("   ✅ USAHA PERUSAHAAN: {$imported} rows imported, {$skipped} rows skipped.");
    }

    private function importUsahaKeluarga($sheet): void
    {
        $this->info('📊 Importing sheet: USAHA KELUARGA...');

        $db = DB::connection('fasih');
        $tanggalData = now()->toDateString();

        if (!$this->option('no-truncate')) {
            $db->table('usaha_keluarga')->truncate();
            $this->warn('   🗑️  Tabel usaha_keluarga di-truncate.');
        }

        $rowNum = 0;
        $imported = 0;
        $skipped = 0;
        $batch = [];

        foreach ($sheet->getRowIterator() as $row) {
            $rowNum++;

            // Skip header rows (rows 1-5)
            if ($rowNum <= $this->headerRow) {
                continue;
            }

            $cells = $row->toArray();
            $kode = trim((string) ($cells[0] ?? ''));
            $subSls = trim((string) ($cells[1] ?? ''));

            // Skip empty rows or summary rows
            if (empty($kode) || $kode === '' || str_contains($subSls, 'INDONESIA')
                || str_contains($subSls, 'JAWA') || str_contains($subSls, 'TOTAL')) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'kode' => $kode,
                'sub_sls' => $subSls,
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka' => $this->cleanNumeric($cells[2] ?? null),
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tutup' => $this->cleanNumeric($cells[3] ?? null),
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ganda' => $this->cleanNumeric($cells[4] ?? null),
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tidak_di' => $this->cleanNumeric($cells[5] ?? null),
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru' => $this->cleanNumeric($cells[6] ?? null),
                'jumlah_usaha_dalam_keluarga' => $this->cleanNumeric($cells[7] ?? null),
                'tanggal_data' => $tanggalData,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $imported++;

            // Upsert in batches of 500
            if (count($batch) >= 500) {
                $db->table('usaha_keluarga')->upsert($batch, ['kode'], [
                    'sub_sls',
                    'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka',
                    'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tutup',
                    'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ganda',
                    'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tidak_di',
                    'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru',
                    'jumlah_usaha_dalam_keluarga',
                    'tanggal_data', 'updated_at',
                ]);
                $batch = [];
                $this->output->write("\r   ⏳ Imported: {$imported} rows...");
            }
        }

        // Upsert remaining
        if (!empty($batch)) {
            $db->table('usaha_keluarga')->upsert($batch, ['kode'], [
                'sub_sls',
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka',
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tutup',
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ganda',
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tidak_di',
                'jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru',
                'jumlah_usaha_dalam_keluarga',
                'tanggal_data', 'updated_at',
            ]);
        }

        $this->newLine();
        $this->info("   ✅ USAHA KELUARGA: {$imported} rows imported, {$skipped} rows skipped.");
    }

    private function cleanNumeric($value): ?string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return (string) $value;
    }
}
