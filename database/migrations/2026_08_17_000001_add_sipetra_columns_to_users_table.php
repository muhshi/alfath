<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sipetra_id')->nullable()->unique()->after('id');
            $table->text('sipetra_token')->nullable()->after('remember_token');
            $table->text('sipetra_refresh_token')->nullable()->after('sipetra_token');
            $table->string('nip')->nullable()->after('email');
            $table->string('jabatan')->nullable()->after('nip');
            $table->string('avatar')->nullable()->after('jabatan');
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sipetra_id',
                'sipetra_token',
                'sipetra_refresh_token',
                'nip',
                'jabatan',
                'avatar',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
