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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500);
            $table->string('slug', 255)->unique();
            $table->string('category', 100); // e.g. phong-thuy, huong-dan, tin-tuc
            $table->text('summary')->nullable();
            $table->string('meta_title', 500)->nullable();
            $table->text('meta_description')->nullable();
            $table->longText('content')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('view_count')->default(0);
            $table->string('generation_model', 100)->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('category');
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
