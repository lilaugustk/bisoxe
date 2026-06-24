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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('province_code', 10)->nullable()->after('category');
            $table->foreign('province_code')->references('code')->on('provinces')->onDelete('set null');
            $table->index('province_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['province_code']);
            $table->dropColumn('province_code');
        });
    }
};
