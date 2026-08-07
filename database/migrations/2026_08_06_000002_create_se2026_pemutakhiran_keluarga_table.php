<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        if (!Schema::connection($this->connection)->hasTable('se2026_pemutakhiran_keluarga')) {
            Schema::connection($this->connection)->create('se2026_pemutakhiran_keluarga', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 16)->unique();
                $table->string('sub_sls')->nullable();
                $table->integer('prelist_awal')->default(0);
                $table->integer('ditemukan')->default(0);
                $table->decimal('persentase_ditemukan', 5, 2)->default(0);
                $table->integer('keluarga_baru')->default(0);
                $table->integer('meninggal')->default(0);
                $table->decimal('persentase_meninggal', 5, 2)->default(0);
                $table->integer('tidak_eligible')->default(0);
                $table->decimal('persentase_tidak_eligible', 5, 2)->default(0);
                $table->integer('tidak_dapat_ditemui')->default(0);
                $table->decimal('persentase_tidak_dapat_ditemui', 5, 2)->default(0);
                $table->integer('tidak_ditemukan')->default(0);
                $table->decimal('persentase_tidak_ditemukan', 5, 2)->default(0);
                $table->integer('total_hasil_pendataan')->default(0);
                $table->decimal('persentase_total_hasil_pendataan', 5, 2)->default(0);
                $table->date('tanggal_data')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('se2026_pemutakhiran_keluarga');
    }
};
