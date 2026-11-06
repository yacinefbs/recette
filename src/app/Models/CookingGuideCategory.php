<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CookingGuideCategory extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'name_fr',
        'icon',
        'description',
        'seo_title',
        'seo_description',
        'sort_order',
    ];

    public function guides(): HasMany
    {
        return $this->hasMany(CookingGuide::class, 'category_id');
    }

    public function publishedGuides(): HasMany
    {
        return $this->hasMany(CookingGuide::class, 'category_id')->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
