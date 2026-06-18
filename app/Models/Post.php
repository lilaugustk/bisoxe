<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'summary',
        'meta_title',
        'meta_description',
        'content',
        'image_path',
        'is_published',
        'view_count',
        'generation_model',
        'generated_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'view_count' => 'integer',
        'generated_at' => 'datetime',
    ];

    /**
     * Scope lọc bài viết đã xuất bản.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope lọc bài viết theo chuyên mục.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}
