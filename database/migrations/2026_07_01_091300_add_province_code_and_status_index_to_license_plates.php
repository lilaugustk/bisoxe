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
        Schema::table('license_plates', function (Blueprint $table) {
            $table->index(['province_code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_plates', function (Blueprint $table) {
            $table->dropIndex(['license_plates_province_code_status_index']);
        });
    }
};
