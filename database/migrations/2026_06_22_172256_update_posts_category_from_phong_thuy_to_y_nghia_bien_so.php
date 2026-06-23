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
        DB::table('posts')
            ->where('category', 'phong-thuy')
            ->update(['category' => 'y-nghia-bien-so']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void    
    {
        DB::table('posts')
            ->where('category', 'y-nghia-bien-so')
            ->update(['category' => 'phong-thuy']);
    }
};
    