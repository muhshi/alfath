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
        Schema::connection($this->connection)->create('se2026_anomali_catatan', function (Blueprint $table) {
            $table->id();
            $table->string('region_code', 16)->unique();
            $table->string('email_petugas')->nullable();
            $table->string('nama_petugas')->nullable();
            $table->text('catatan');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('catatan_admin')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('se2026_anomali_catatan');
    }
};
