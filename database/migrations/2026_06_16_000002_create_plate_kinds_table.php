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
        Schema::create('plate_kinds', function (Blueprint $table) {
            $table->comment('Danh mục loại biển số từ VPA');
            $table->integer('id')->primary()->comment('ID loại biển từ VPA');
            $table->string('name', 255)->comment('Ngũ quý, Tứ quý, Tam hoa...');
            $table->integer('priority')->nullable()->comment('Độ ưu tiên');
            $table->text('regex')->nullable()->comment('Regex nhận diện từ VPA');
            $table->string('group_name', 255)->nullable()->comment('Nhóm phân loại');
            $table->tinyInteger('is_featured')->default(0);
            $table->tinyInteger('is_omitted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plate_kinds');
    }
};
