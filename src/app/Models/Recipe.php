<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tasty_id', 'name', 'description', 'thumbnail_url', 'image_path', 'total_time_minutes', 'prep_time_minutes', 'cook_time_minutes', 'num_servings', 'nutrition', 'sections', 'instructions'])]
class Recipe extends Model
{
    public function getImageAttribute(): string
    {
        return $this->image_path ? asset($this->image_path) : asset('assets/images/teachers/t-1.jpg');
    }

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'instructions' => 'array',
            'nutrition' => 'array',
        ];
    }
}
