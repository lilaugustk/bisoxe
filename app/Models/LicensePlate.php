<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $vehicle_type
 * @property string $local_symbol
 * @property string $serial_letter
 * @property string $serial_number
 * @property string $full_number
 * @property string $display_number
 * @property string|null $province_code
 * @property int $color
 * @property string $status
 * @property int $starting_price
 * @property int $winning_price
 * @property \Illuminate\Support\Carbon|null $register_start_time
 * @property \Illuminate\Support\Carbon|null $register_end_time
 * @property \Illuminate\Support\Carbon|null $auction_start_time
 * @property \Illuminate\Support\Carbon|null $auction_end_time
 * @property \Illuminate\Support\Carbon|null $crawled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property Province|null $province
 * @property \Illuminate\Database\Eloquent\Collection<int, PlateKind> $kinds
 * @property SeoArticle|null $seoArticle
 */
class LicensePlate extends Model
{

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
     *
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    /**
     * Lấy các phân loại kiểu dáng biển số (ngũ quý, tứ quý...).
     *
     * @return BelongsToMany<PlateKind, $this>
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
     * Lấy bài viết SEO được sinh tự động cho biển số này.
     *
     * @return HasOne<SeoArticle, $this>
     */
    public function seoArticle(): HasOne
    {
        return $this->hasOne(SeoArticle::class, 'plate_id', 'id');
    }
}
