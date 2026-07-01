c:\Users\Admin\AppData\Local\Packages\MicrosoftWindows.Client.Core_cw5n1h2txyewy\TempState\ScreenClip\{7F00BA8A-B87F-49C7-A60F-80901C6D6615}.png<?php

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
            $table->index(['province_code', 'vehicle_type', 'status', 'min_kind_priority'], 'lp_prov_vh_st_mkp');
            $table->index(['province_code', 'vehicle_type', 'status', 'auction_start_time'], 'lp_prov_vh_st_ast');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_plates', function (Blueprint $table) {
            $table->dropIndex('lp_prov_vh_st_mkp');
            $table->dropIndex('lp_prov_vh_st_ast');
        });
    }
};
