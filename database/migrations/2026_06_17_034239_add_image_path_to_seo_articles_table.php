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
        Schema::table('seo_articles', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('google_indexed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_articles', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
