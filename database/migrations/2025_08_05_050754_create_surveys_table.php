<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_periode');
            $table->date('end_periode');
            $table->string('img_survey')->nullable(); // path ke upload
            $table->unsignedBigInteger('metabase_dashboard_id');
            $table->json('metabase_params')->nullable(); // ['tahun'=>2024,'wil'=>32]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
