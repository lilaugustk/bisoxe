<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LicensePlate extends Model
{
    use HasFactory;

    protected $table = 'license_plates';

    protected $fillable = [
        'vehicle_type',
        'local_symbol',
        'serial_letter',
        'serial_number',
        'full_number',
        'display_number',
        'province_code',
        'color',
        'status',
        'starting_price',
        'winning_price',
        'register_start_time',
        'register_end_time',
        'auction_start_time',
        'auction_end_time',
        'crawled_at',
    ];

    protected $casts = [
        'color' => 'integer',
        'starting_price' => 'integer',
        'winning_price' => 'integer',
        'register_start_time' => 'datetime',
        'register_end_time' => 'datetime',
        'auction_start_time' => 'datetime',
        'auction_end_time' => 'datetime',
        'crawled_at' => 'datetime',
    ];

    /**
     * Lấy tỉnh thành của biển số này.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    /**
     * Lấy các phân loại kiểu dáng biển số (ngũ quý, tứ quý...).
     */
    public function kinds(): BelongsToMany
    {
        return $this->belongsToMany(
            PlateKind::class,
            'license_plate_kinds',
            'plate_id',
            'kind_id'
        )->withPivot('created_at');
    }

    /**
     * Lấy bài viết SEO do AI sinh ra cho biển số này.
     */
    public function seoArticle(): HasOne
    {
        return $this->hasOne(SeoArticle::class, 'plate_id', 'id');
    }
}
