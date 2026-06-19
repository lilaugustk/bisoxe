<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $table = 'provinces';

    // Khóa chính là chuỗi 'code'
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * Lấy danh sách biển số xe thuộc tỉnh thành này.
     *
     * @return HasMany<LicensePlate, $this>
     */
    public function licensePlates(): HasMany
    {
        return $this->hasMany(LicensePlate::class, 'province_code', 'code');
    }
}
