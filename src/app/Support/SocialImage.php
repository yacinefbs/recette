<?php

namespace App\Support;

class SocialImage
{
    private const MAP = [
        'weekly-meal-planner' => 'weekly-meal-planner',
        'tools/cooking-timer' => 'cooking-timer',
        'tools/cooking-converter' => 'cooking-converter',
        'what-can-i-cook' => 'what-can-i-cook',
        'tools/ingredient-substitution' => 'ingredient-substitution',
        'editorial-policy' => 'editorial-policy',
    ];

    /** @return array{url:string,width:int,height:int,type:string}|null */
    public static function forUrl(?string $url): ?array
    {
        $path = trim((string) parse_url((string) $url, PHP_URL_PATH), '/');
        $slug = self::MAP[$path] ?? (str_starts_with($path, 'ingredient-substitutes/') ? 'ingredient-substitution' : null);
        if (! $slug) {
            return null;
        }

        $file = "assets/images/social/{$slug}-og.jpg";
        if (! is_file(public_path($file))) {
            return null;
        }

        return ['url' => asset($file), 'width' => 1200, 'height' => 630, 'type' => 'image/jpeg'];
    }
}
