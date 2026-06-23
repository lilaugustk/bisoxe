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
            $table->index('serial_number');
            $table->index(['status', 'winning_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_plates', function (Blueprint $table) {
            $table->dropIndex(['serial_number']);
            $table->dropIndex(['status', 'winning_price']);
        });
    }
};
