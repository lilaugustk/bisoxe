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
        if (!Schema::hasColumn('license_plates', 'min_kind_priority')) {
            Schema::table('license_plates', function (Blueprint $table) {
                $table->integer('min_kind_priority')->default(9999)->after('winning_price');
                $table->index('min_kind_priority');
                $table->index(['vehicle_type', 'status', 'min_kind_priority']);
            });

            // Cập nhật dữ liệu cũ dựa trên bảng liên kết kinds hiện tại
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('license_plates', 'min_kind_priority')) {
            Schema::table('license_plates', function (Blueprint $table) {
                try {
                    $table->dropIndex(['vehicle_type', 'status', 'min_kind_priority']);
                } catch (\Exception $e) {
                }
                try {
                    $table->dropIndex(['min_kind_priority']);
                } catch (\Exception $e) {
                }
                $table->dropColumn('min_kind_priority');
            });
        }
    }
};
