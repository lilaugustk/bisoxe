<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'province_code',
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
     * Lấy thông tin tỉnh thành liên kết với bài viết này.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Province, $this>
     */
    public function province(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    /**
     * Scope lọc bài viết đã xuất bản.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope lọc bài viết theo chuyên mục.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}
