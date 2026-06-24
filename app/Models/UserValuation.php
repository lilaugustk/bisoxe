<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
 * @property int $asking_price
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Province|null $province
 * @property-read \Illuminate\Support\Collection<int, \App\Models\PlateKind> $kinds
 */
class UserValuation extends Model
{
    protected $table = 'user_valuations';

    protected $fillable = [
        'vehicle_type',
        'local_symbol',
        'serial_letter',
        'serial_number',
        'full_number',
        'display_number',
        'province_code',
        'color',
        'asking_price',
        'ip_address',
    ];

    protected $casts = [
        'color' => 'integer',
        'asking_price' => 'integer',
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
     * Nhận diện các phân loại loại biển số động dựa trên regex của plate_kinds.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\PlateKind>
     */
    public function getKindsAttribute()
    {
        $serialNumber = $this->serial_number;
        if (empty($serialNumber)) {
            return collect();
        }

        // Cache lại danh sách plate_kinds dưới dạng array để tránh lỗi deserialize __PHP_Incomplete_Class
        // do cấu hình 'serializable_classes' => false mặc định trong Laravel 11.
        $plateKinds = Cache::remember('user_valuations_plate_kinds_cache', 3600, function () {
            return PlateKind::orderBy('priority')->get()->map(fn ($kind) => [
                'id' => $kind->id,
                'name' => $kind->name,
                'priority' => $kind->priority,
                'regex' => $kind->regex,
            ])->toArray();
        });

        $matchedKinds = collect();
        foreach ($plateKinds as $kind) {
            $regex = $kind['regex'] ?? null;
            if ($regex) {
                try {
                    if (preg_match('#' . str_replace('#', '\#', $regex) . '#', $serialNumber)) {
                        $matchedKinds->push(new PlateKind([
                            'id' => $kind['id'],
                            'name' => $kind['name'],
                            'priority' => $kind['priority'],
                        ]));
                    }
                } catch (\Exception $e) {
                    // Bỏ qua regex lỗi
                }
            }
        }

        return $matchedKinds->sortBy('priority')->values();
    }
}
