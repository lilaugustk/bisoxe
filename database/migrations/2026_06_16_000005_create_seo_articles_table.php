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
        Schema::create('seo_articles', function (Blueprint $table) {
            $table->comment('Nội dung sinh tự động từ dữ liệu biển số');
            $table->id();

            $table->unsignedBigInteger('plate_id');
            $table->string('slug', 255)->unique();
            $table->string('title', 500)->nullable();
            $table->string('meta_title', 500)->nullable();
            $table->text('meta_description')->nullable();

            $table->longText('content')->nullable()->comment('Bài viết SEO sinh tự động');
            $table->longText('video_script')->nullable()->comment('Kịch bản video sinh tự động');

            $table->string('generation_model', 100)->nullable();
            $table->dateTime('generated_at')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('plate_id')->references('id')->on('license_plates')->onDelete('cascade');

            // Indexes
            $table->index('plate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_articles');
    }
};
