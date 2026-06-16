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
        Schema::create('license_plates', function (Blueprint $table) {
            $table->comment('Thông tin biển số đấu giá');
            $table->id();
            
            $table->string('vehicle_type', 20)->comment('car, motorcycle');
            $table->string('local_symbol', 10)->nullable()->comment('Mã địa phương, ví dụ 15');
            $table->string('serial_letter', 10)->nullable()->comment('Ký tự seri, ví dụ K');
            $table->string('serial_number', 20)->nullable()->comment('Phần số, ví dụ 77777');
            $table->string('full_number', 50)->unique()->comment('15K77777');
            $table->string('display_number', 50)->nullable()->comment('15K-777.77');
            
            $table->string('province_code', 10);
            $table->integer('color')->default(0)->comment('0=trắng, 1=vàng (nếu có)');
            $table->string('status', 50)->nullable()->comment('waiting_auction, announced, completed');
            
            $table->bigInteger('starting_price')->default(0);
            $table->bigInteger('winning_price')->default(0);
            
            $table->dateTime('register_start_time')->nullable();
            $table->dateTime('register_end_time')->nullable();
            $table->dateTime('auction_start_time')->nullable();
            $table->dateTime('auction_end_time')->nullable();
            $table->dateTime('crawled_at')->nullable()->comment('Thời điểm crawler lấy dữ liệu');
            
            $table->timestamps();

            // Foreign Key
            $table->foreign('province_code')->references('code')->on('provinces')->onDelete('cascade');

            // Indexes
            $table->index('province_code');
            $table->index('vehicle_type');
            $table->index('status');
            $table->index('auction_start_time');
            $table->index('auction_end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_plates');
    }
};
