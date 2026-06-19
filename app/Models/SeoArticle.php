<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $plate_id
 * @property string $slug
 * @property string $title
 * @property string $meta_title
 * @property string $meta_description
 * @property string $content
 * @property string|null $video_script
 * @property string|null $generation_model
 * @property Carbon|null $generated_at
 * @property Carbon|null $google_indexed_at
 * @property string|null $image_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property LicensePlate|null $licensePlate
 */
class SeoArticle extends Model
{
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
     *
     * @return BelongsTo<LicensePlate, $this>
     */
    public function licensePlate(): BelongsTo
    {
        return $this->belongsTo(LicensePlate::class, 'plate_id', 'id');
    }
}
