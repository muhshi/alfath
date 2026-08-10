<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'fasih';

    public function up(): void
    {
        $db = DB::connection($this->connection);

        // Add index on alokasi_pengawas.email_pengawas (used in JOIN to master_petugas)
        if (Schema::connection($this->connection)->hasTable('alokasi_pengawas')) {
            $existing = $db->select("SHOW INDEX FROM alokasi_pengawas WHERE Column_name = 'email_pengawas'");
            if (empty($existing)) {
                $db->statement('ALTER TABLE alokasi_pengawas ADD INDEX idx_email_pengawas (email_pengawas)');
            }
        }

        // Add index on monitoring_se2026 (tanggal_tarik, email_pencacah) for faster GROUP BY
        if (Schema::connection($this->connection)->hasTable('monitoring_se2026')) {
            $existing = $db->select("SHOW INDEX FROM monitoring_se2026 WHERE Key_name = 'idx_tgl_email'");
            if (empty($existing)) {
                $db->statement('ALTER TABLE monitoring_se2026 ADD INDEX idx_tgl_email (tanggal_tarik, email_pencacah)');
            }
        }
    }

    public function down(): void
    {
        $db = DB::connection($this->connection);

        try {
            $db->statement('ALTER TABLE alokasi_pengawas DROP INDEX idx_email_pengawas');
        } catch (\Exception $e) {}

        try {
            $db->statement('ALTER TABLE monitoring_se2026 DROP INDEX idx_tgl_email');
        } catch (\Exception $e) {}
    }
};
