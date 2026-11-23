<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property string $name
 *  @property string|float $latitude
 *  @property string|float $longitude
 */
class Barangay extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude', 'is_active'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }
}
