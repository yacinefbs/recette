<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NutritionSource extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'version',
        'license',
        'url',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
        ];
    }

    public function foods(): HasMany
    {
        return $this->hasMany(Food::class);
    }
}
