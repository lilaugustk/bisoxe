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
        Schema::create('license_plate_kinds', function (Blueprint $table) {
            $table->comment('Liên kết nhiều-nhiều giữa biển số và loại biển');
            $table->unsignedBigInteger('plate_id');
            $table->integer('kind_id');
            $table->timestamp('created_at')->nullable();

            // Composite Primary Key
            $table->primary(['plate_id', 'kind_id']);

            // Foreign Keys
            $table->foreign('plate_id')->references('id')->on('license_plates')->onDelete('cascade');
            $table->foreign('kind_id')->references('id')->on('plate_kinds')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_plate_kinds');
    }
};
