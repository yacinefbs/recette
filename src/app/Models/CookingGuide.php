<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CookingGuide extends Model
{
    protected $fillable = [
        'category_id',
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'excerpt',
        'quick_answer',
        'what_you_need',
        'content_sections',
        'common_mistakes',
        'pro_tips',
        'faqs',
        'sources',
        'problem_keywords',
        'reading_time_minutes',
        'image_path',
        'author_name',
        'featured',
        'related_tool_key',
        'related_tool_headline',
        'related_tool_text',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'what_you_need' => 'array',
            'content_sections' => 'array',
            'common_mistakes' => 'array',
            'pro_tips' => 'array',
            'faqs' => 'array',
            'sources' => 'array',
            'problem_keywords' => 'array',
            'reading_time_minutes' => 'integer',
            'featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CookingGuideCategory::class, 'category_id');
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'cooking_guide_recipes');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    public function getImageAttribute(): string
    {
        if ($this->image_path) {
            return asset($this->image_path);
        }

        return asset('assets/images/courses/courses-01.jpg');
    }

    public function getUrlAttribute(): string
    {
        $catSlug = $this->category ? $this->category->slug : 'general';

        return route('cooking-tips.show', ['category' => $catSlug, 'guide' => $this->slug]);
    }
}
