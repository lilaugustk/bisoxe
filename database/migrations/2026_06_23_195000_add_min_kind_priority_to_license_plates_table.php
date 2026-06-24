<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Check and add column
        if (!Schema::hasColumn('license_plates', 'min_kind_priority')) {
            Schema::table('license_plates', function (Blueprint $table) {
                $table->integer('min_kind_priority')->default(9999)->after('winning_price');
            });
        }

        // 2. Check and add single index on min_kind_priority
        $hasSingleIndex = collect(DB::select("SHOW INDEXES FROM license_plates WHERE Key_name = 'license_plates_min_kind_priority_index'"))->isNotEmpty();
        if (!$hasSingleIndex) {
            Schema::table('license_plates', function (Blueprint $table) {
                $table->index('min_kind_priority');
            });
        }

        // 3. Check and add composite index 1
        $hasCompositeIndex1 = collect(DB::select("SHOW INDEXES FROM license_plates WHERE Key_name = 'license_plates_vehicle_type_status_min_kind_priority_index'"))->isNotEmpty();
        if (!$hasCompositeIndex1) {
            Schema::table('license_plates', function (Blueprint $table) {
                $table->index(['vehicle_type', 'status', 'min_kind_priority']);
            });
        }

        // 4. Check and add composite index 2 (optimizes waiting_auction query sorting)
        $hasCompositeIndex2 = collect(DB::select("SHOW INDEXES FROM license_plates WHERE Key_name = 'lp_vh_st_ast_mkp'"))->isNotEmpty();
        if (!$hasCompositeIndex2) {
            Schema::table('license_plates', function (Blueprint $table) {
                $table->index(['vehicle_type', 'status', 'auction_start_time', 'min_kind_priority'], 'lp_vh_st_ast_mkp');
            });
        }

        // 5. Update old data
        DB::statement("
            UPDATE license_plates
            SET min_kind_priority = COALESCE(
                (
                    SELECT MIN(pk.priority)
                    FROM license_plate_kinds lpk
                    JOIN plate_kinds pk ON pk.id = lpk.kind_id
                    WHERE lpk.plate_id = license_plates.id
                ),
                9999
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_plates', function (Blueprint $table) {
            try {
                $table->dropIndex('lp_vh_st_ast_mkp');
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['vehicle_type', 'status', 'min_kind_priority']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['min_kind_priority']);
            } catch (\Exception $e) {
            }
            if (Schema::hasColumn('license_plates', 'min_kind_priority')) {
                $table->dropColumn('min_kind_priority');
            }
        });
    }
};
