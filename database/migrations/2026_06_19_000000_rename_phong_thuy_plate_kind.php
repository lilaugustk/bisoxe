<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('plate_kinds')
            ->where('id', 10)
            ->update(['name' => 'Biển thường']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plate_kinds')
            ->where('id', 10)
            ->update(['name' => 'Phong thuỷ']);
    }
};
