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
            // Composite index hỗ trợ lọc & sắp xếp trang đấu giá tỉnh thành
            $table->index(['province_code', 'vehicle_type', 'color', 'status', 'auction_start_time'], 'lp_prov_vh_col_st_ast');
            
            // Composite index hỗ trợ lọc & sắp xếp trang phân tích tỉnh thành (pSEO)
            $table->index(['province_code', 'status', 'winning_price'], 'lp_prov_st_win');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_plates', function (Blueprint $table) {
            $table->dropIndex('lp_prov_vh_col_st_ast');
            $table->dropIndex('lp_prov_st_win');
        });
    }
};
