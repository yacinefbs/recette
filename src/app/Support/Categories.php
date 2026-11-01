<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Site categories. Recipes are matched by keywords in their name, so new topics
 * can be added here (or later moved to the database) without touching the views.
 */
final class Categories
{
    public const ALL = [
        'chicken' => [
            'label' => 'Chicken',
            'icon' => 'fa-drumstick-bite',
            'keywords' => ['chicken'],
            'intro' => 'Chicken is the weeknight hero: affordable, quick to cook and easy to flavour. From creamy bakes to crispy fried chicken, these recipes cover every mood.',
            'tip' => 'Cook chicken to an internal temperature of 165Â°F (74Â°C) and let it rest for 5 minutes so it stays juicy. Leftovers keep 3-4 days in the fridge.',
        ],
        'pasta' => [
            'label' => 'Pasta',
            'icon' => 'fa-utensils',
            'keywords' => ['pasta', 'spaghetti', 'lasagna', 'alfredo', 'penne', 'macaroni', 'noodle', 'rotini', 'ravioli', 'fettuccine'],
            'intro' => 'Pasta dishes are the easiest way to get a satisfying dinner on the table. Browse creamy alfredos, baked pastas and one-pot favourites.',
            'tip' => 'Salt the cooking water generously and save a cup of it: a splash of starchy pasta water helps any sauce cling to the noodles.',
        ],
        'beef' => [
            'label' => 'Beef & Burgers',
            'icon' => 'fa-hamburger',
            'keywords' => ['beef', 'burger', 'steak', 'meatball', 'brisket', 'cheeseburger'],
            'intro' => 'Juicy burgers, tender steaks and hearty beef dinners. Simple techniques that deliver big flavour.',
            'tip' => 'Let beef rest for a few minutes after cooking so the juices redistribute, and avoid pressing burgers while they sizzle.',
        ],
        'salads' => [
            'label' => 'Salads',
            'icon' => 'fa-leaf',
            'keywords' => ['salad', 'slaw'],
            'intro' => 'Fresh, crunchy and colourful: salads that work as a light lunch or as a side that steals the show.',
            'tip' => 'Dress salads just before serving to keep the leaves crisp, and dry washed greens well so the dressing sticks.',
        ],
        'soups' => [
            'label' => 'Soups & Stews',
            'icon' => 'fa-pepper-hot',
            'keywords' => ['soup', 'stew', 'chili', 'broth', 'chowder'],
            'intro' => 'Warming bowls for cold days. Most soups and stews taste even better the next day and freeze well.',
            'tip' => 'Cool soups quickly, then refrigerate for up to 4 days or freeze for up to 3 months. Reheat gently and adjust the seasoning at the end.',
        ],
        'desserts' => [
            'label' => 'Desserts',
            'icon' => 'fa-ice-cream',
            'keywords' => ['cake', 'cookie', 'brownie', 'pie', 'pudding', 'muffin', 'ice cream', 'chocolate', 'cheesecake', 'cupcake', 'fudge', 'candy', 'macaron', 'dessert'],
            'intro' => 'Cookies, brownies, cakes and more: sweet recipes for every skill level, with clear steps for reliable results.',
            'tip' => 'Measure flour by weight when you can and use room-temperature eggs and butter. Most baked treats keep 3-4 days in an airtight container.',
        ],
        'breakfast' => [
            'label' => 'Breakfast & Brunch',
            'icon' => 'fa-bread-slice',
            'keywords' => ['pancake', 'waffle', 'omelet', 'omelette', 'breakfast', 'oatmeal', 'granola', 'french toast', 'eggs', 'bagel', 'brunch'],
            'intro' => 'Start the day right with fluffy pancakes, eggs and easy brunch ideas you can make in minutes.',
            'tip' => 'Do not overmix pancake batter: a few lumps mean tender, fluffy results. Keep finished pancakes warm in a low oven.',
        ],
        'drinks' => [
            'label' => 'Drinks',
            'icon' => 'fa-glass-martini-alt',
            'keywords' => ['smoothie', 'cocktail', 'lemonade', 'drink', 'iced tea', 'coffee', 'milkshake', 'punch'],
            'intro' => 'Smoothies, cocktails and refreshers to match any occasion.',
            'tip' => 'Chill the glasses and use fresh ingredients: they make a bigger difference to drinks than any fancy equipment.',
        ],
    ];

    public static function find(string $slug): ?array
    {
        return isset(self::ALL[$slug]) ? self::ALL[$slug] + ['slug' => $slug] : null;
    }

    public static function all(): array
    {
        return collect(self::ALL)->map(fn ($c, $slug) => $c + ['slug' => $slug])->all();
    }

    public static function constrain(Builder $query, string $slug): Builder
    {
        $words = self::ALL[$slug]['keywords'] ?? [];

        return $query->where(function (Builder $q) use ($words) {
            foreach ($words as $word) {
                $q->orWhere('name', 'like', '%'.$word.'%');
            }
        });
    }

    /** Slug of the first category matching a recipe name (priority = declaration order of dish types). */
    public static function forName(string $name): ?string
    {
        $lower = strtolower($name);
        foreach (['desserts', 'breakfast', 'salads', 'soups', 'drinks', 'pasta', 'chicken', 'beef'] as $slug) {
            foreach (self::ALL[$slug]['keywords'] as $word) {
                if (str_contains($lower, $word)) {
                    return $slug;
                }
            }
        }

        return null;
    }
}
