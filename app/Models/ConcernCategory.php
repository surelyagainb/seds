<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property string $key
 *  @property string $name
 *  @property array|null $keywords_en
 *  @property array|null $keywords_fil
 */
class ConcernCategory extends Model
{
    protected $fillable = ['key', 'name', 'color', 'keywords_en', 'keywords_fil', 'is_active'];

    protected function casts(): array
    {
        return [
            'keywords_en' => 'array',
            'keywords_fil' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
