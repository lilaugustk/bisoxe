<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoArticle extends Model
{
    use HasFactory;

    protected $table = 'seo_articles';

    protected $fillable = [
        'plate_id',
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'content',
        'video_script',
        'generation_model',
        'generated_at',
        'google_indexed_at',
        'image_path',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'google_indexed_at' => 'datetime',
    ];

    /**
     * Lấy thông tin biển số xe tương ứng với bài viết này.
     */
    public function licensePlate(): BelongsTo
    {
        return $this->belongsTo(LicensePlate::class, 'plate_id', 'id');
    }
}
