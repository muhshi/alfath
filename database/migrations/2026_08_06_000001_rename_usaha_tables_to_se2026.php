<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Connection used by this migration.
     */
    protected $connection = 'fasih';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $db = DB::connection($this->connection);

        // 1. Rename usaha_perusahaan -> se2026_usaha_perusahaan and create view usaha_perusahaan
        if (Schema::connection($this->connection)->hasTable('usaha_perusahaan') && !Schema::connection($this->connection)->hasTable('se2026_usaha_perusahaan')) {
            Schema::connection($this->connection)->rename('usaha_perusahaan', 'se2026_usaha_perusahaan');
            $db->statement('CREATE VIEW usaha_perusahaan AS SELECT * FROM se2026_usaha_perusahaan');
        }

        // 2. Rename usaha_keluarga -> se2026_usaha_keluarga and create view usaha_keluarga
        if (Schema::connection($this->connection)->hasTable('usaha_keluarga') && !Schema::connection($this->connection)->hasTable('se2026_usaha_keluarga')) {
            Schema::connection($this->connection)->rename('usaha_keluarga', 'se2026_usaha_keluarga');
            $db->statement('CREATE VIEW usaha_keluarga AS SELECT * FROM se2026_usaha_keluarga');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $db = DB::connection($this->connection);

        if (Schema::connection($this->connection)->hasTable('se2026_usaha_perusahaan')) {
            $db->statement('DROP VIEW IF EXISTS usaha_perusahaan');
            Schema::connection($this->connection)->rename('se2026_usaha_perusahaan', 'usaha_perusahaan');
        }

        if (Schema::connection($this->connection)->hasTable('se2026_usaha_keluarga')) {
            $db->statement('DROP VIEW IF EXISTS usaha_keluarga');
            Schema::connection($this->connection)->rename('se2026_usaha_keluarga', 'usaha_keluarga');
        }
    }
};
