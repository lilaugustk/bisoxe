<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlateKind extends Model
{
    protected $table = 'plate_kinds';

    // Khóa chính là id do VPA định nghĩa sẵn
    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'priority',
        'regex',
        'group_name',
        'is_featured',
        'is_omitted',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_omitted' => 'boolean',
    ];

    /**
     * Lấy các biển số xe thuộc loại này.
     *
     * @return BelongsToMany<LicensePlate, $this>
     */
    public function licensePlates(): BelongsToMany
    {
        return $this->belongsToMany(
            LicensePlate::class,
            'license_plate_kinds',
            'kind_id',
            'plate_id'
        )->withPivot('created_at');
    }
}
