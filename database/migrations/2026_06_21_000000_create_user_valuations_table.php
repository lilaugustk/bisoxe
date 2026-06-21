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
        Schema::create('user_valuations', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_type'); // 'car' or 'motorcycle'
            $table->string('local_symbol');
            $table->string('serial_letter');
            $table->string('serial_number');
            $table->string('full_number')->index();
            $table->string('display_number');
            $table->string('province_code')->nullable();
            $table->integer('color')->default(0);
            $table->bigInteger('asking_price');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_valuations');
    }
};
