<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use Illuminate\Support\Facades\DB;

$outputFile = __DIR__ . '/Export_Usaha_Bersih_Petugas.xlsx';

echo "⏳ Generating Excel file: {$outputFile}\n";

$writer = new Writer();
$writer->openToFile($outputFile);

// ==========================================
// SHEET 1: USAHA PERUSAHAAN
// ==========================================
$sheet1 = $writer->getCurrentSheet();
$sheet1->setName('USAHA PERUSAHAAN');

$header1 = [
    'Nama Desa', 'Kode (16 Digit)', 'Sub-SLS', 'Nama Pencacah', 'Nama Pengawas',
    'Prelist', 'Ditemukan', 'Tutup', 'Ganda', 'Tidak Ditemukan', 'Baru', 'Usaha BKU'
];
$writer->addRow(Row::fromValues($header1));

echo "⏳ Querying Usaha Perusahaan...\n";
$dataUP = DB::connection('fasih')->select("
    SELECT 
        (SELECT sub_sls FROM usaha_perusahaan d WHERE d.kode = LEFT(up.kode, 10) LIMIT 1) AS nama_desa,
        up.kode,
        up.sub_sls,
        IFNULL(p_cacah.nama_lengkap, ml.email_pencacah) AS pencacah,
        GROUP_CONCAT(DISTINCT p_awas.nama_lengkap SEPARATOR ', ') AS pengawas,
        up.jumlah_prelist_usaha, up.status___ditemukan, up.status___tutup, 
        up.status___ganda, up.status___tidak_ditemukan, up.status___baru, up.jumlah_usaha_bku
    FROM usaha_perusahaan up
    LEFT JOIN (
        SELECT region_code, SUBSTRING_INDEX(GROUP_CONCAT(email_pencacah ORDER BY tanggal_tarik DESC SEPARATOR '||'), '||', 1) AS email_pencacah
        FROM monitoring_se2026 GROUP BY region_code
    ) ml ON ml.region_code = up.kode
    LEFT JOIN alokasi_pengawas a ON up.kode = a.region_code
    LEFT JOIN master_petugas p_cacah ON ml.email_pencacah = p_cacah.email
    LEFT JOIN master_petugas p_awas ON a.email_pengawas = p_awas.email
    WHERE LENGTH(up.kode) = 16
    GROUP BY up.kode, up.sub_sls, ml.email_pencacah, p_cacah.nama_lengkap, 
             up.jumlah_prelist_usaha, up.status___ditemukan, up.status___tutup, 
             up.status___ganda, up.status___tidak_ditemukan, up.status___baru, up.jumlah_usaha_bku
    ORDER BY up.kode
");

echo "⏳ Writing Usaha Perusahaan (" . count($dataUP) . " rows)...\n";
foreach($dataUP as $row) {
    $writer->addRow(Row::fromValues([
        $row->nama_desa, $row->kode, $row->sub_sls, $row->pencacah, $row->pengawas,
        $row->jumlah_prelist_usaha, $row->status___ditemukan, $row->status___tutup,
        $row->status___ganda, $row->status___tidak_ditemukan, $row->status___baru, $row->jumlah_usaha_bku
    ]));
}


// ==========================================
// SHEET 2: USAHA KELUARGA
// ==========================================
$writer->addNewSheetAndMakeItCurrent();
$sheet2 = $writer->getCurrentSheet();
$sheet2->setName('USAHA KELUARGA');

$header2 = [
    'Nama Desa', 'Kode (16 Digit)', 'Sub-SLS', 'Nama Pencacah', 'Nama Pengawas',
    'Ditemukan', 'Tutup', 'Ganda', 'Tidak Ditemukan', 'Baru', 'Dalam Keluarga'
];
$writer->addRow(Row::fromValues($header2));

echo "⏳ Querying Usaha Keluarga...\n";
$dataUK = DB::connection('fasih')->select("
    SELECT 
        (SELECT sub_sls FROM usaha_keluarga d WHERE d.kode = LEFT(uk.kode, 10) LIMIT 1) AS nama_desa,
        uk.kode,
        uk.sub_sls,
        IFNULL(p_cacah.nama_lengkap, ml.email_pencacah) AS pencacah,
        GROUP_CONCAT(DISTINCT p_awas.nama_lengkap SEPARATOR ', ') AS pengawas,
        uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka,
        uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tutup,
        uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ganda,
        uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tidak_di,
        uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru,
        uk.jumlah_usaha_dalam_keluarga
    FROM usaha_keluarga uk
    LEFT JOIN (
        SELECT region_code, SUBSTRING_INDEX(GROUP_CONCAT(email_pencacah ORDER BY tanggal_tarik DESC SEPARATOR '||'), '||', 1) AS email_pencacah
        FROM monitoring_se2026 GROUP BY region_code
    ) ml ON ml.region_code = uk.kode
    LEFT JOIN alokasi_pengawas a ON uk.kode = a.region_code
    LEFT JOIN master_petugas p_cacah ON ml.email_pencacah = p_cacah.email
    LEFT JOIN master_petugas p_awas ON a.email_pengawas = p_awas.email
    WHERE LENGTH(uk.kode) = 16
    GROUP BY uk.kode, uk.sub_sls, ml.email_pencacah, p_cacah.nama_lengkap,
             uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka,
             uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tutup,
             uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ganda,
             uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tidak_di,
             uk.jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru,
             uk.jumlah_usaha_dalam_keluarga
    ORDER BY uk.kode
");

echo "⏳ Writing Usaha Keluarga (" . count($dataUK) . " rows)...\n";
foreach($dataUK as $row) {
    $writer->addRow(Row::fromValues([
        $row->nama_desa, $row->kode, $row->sub_sls, $row->pencacah, $row->pengawas,
        $row->jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka,
        $row->jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tutup,
        $row->jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ganda,
        $row->jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tidak_di,
        $row->jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru,
        $row->jumlah_usaha_dalam_keluarga
    ]));
}

$writer->close();
echo "✅ Export selesai! File disimpan di: {$outputFile}\n";
