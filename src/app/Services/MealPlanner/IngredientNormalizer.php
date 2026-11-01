<?php

namespace App\Services\MealPlanner;

/**
 * Turns Tasty ingredient components into comparable quantities.
 * Compatible units are converted (mass to g, volume to ml); anything else stays separate.
 */
final class IngredientNormalizer
{
    private const UNITS = [
        'gram' => ['mass', 1], 'kilogram' => ['mass', 1000], 'ounce' => ['mass', 28.3495], 'pound' => ['mass', 453.592],
        'milliliter' => ['volume', 1], 'liter' => ['volume', 1000], 'cup' => ['volume', 236.588],
        'tablespoon' => ['volume', 14.7868], 'teaspoon' => ['volume', 4.92892], 'pint' => ['volume', 473.176],
        'quart' => ['volume', 946.353], 'fluid ounce' => ['volume', 29.5735], 'gallon' => ['volume', 3785.41],
    ];

    private const FRACTIONS = [
        '½' => 0.5, '¼' => 0.25, '¾' => 0.75, '⅓' => 1 / 3, '⅔' => 2 / 3, '⅛' => 0.125,
        '⅜' => 0.375, '⅝' => 0.625, '⅞' => 0.875, '⅕' => 0.2, '⅙' => 1 / 6,
    ];

    private const CATEGORIES = [
        'Pantry' => ['baking', 'broth', 'stock', 'oil', 'sugar', 'flour', 'rice', 'pasta', 'spaghetti', 'penne', 'rotini', 'noodle', 'sauce', 'vinegar', 'cocoa', 'chocolate', 'honey', 'syrup', 'beans', 'chickpea', 'lentil', 'quinoa', 'oats', 'oat', 'peanut butter', 'almond', 'walnut', 'pecan', 'nuts', 'coconut', 'cornstarch', 'cornmeal', 'breadcrumb', 'panko', 'mustard', 'ketchup', 'mayonnaise', 'jam', 'extract', 'yeast', 'couscous', 'bulgur', 'granola', 'tofu', 'canned', 'water', 'wine', 'sprinkles', 'marshmallow', 'tomato paste', 'puree'],
        'Spices & Seasonings' => ['salt', 'black pepper', 'ground pepper', 'white pepper', 'pepper flakes', 'paprika', 'cumin', 'cinnamon', 'oregano', 'thyme', 'rosemary', 'nutmeg', 'turmeric', 'chili powder', 'cayenne', 'powder', 'seasoning', 'spice', 'coriander', 'cardamom', 'clove', 'bay leaf', 'garam masala', 'masala', 'curry', 'sage', 'dill', 'fenugreek', 'vanilla'],
        'Dairy & Eggs' => ['egg', 'milk', 'butter', 'cheese', 'cream', 'yogurt', 'yoghurt', 'parmesan', 'mozzarella', 'cheddar', 'ricotta', 'paneer', 'ghee', 'half-and-half', 'buttermilk', 'burrata', 'feta'],
        'Meat & Fish' => ['chicken', 'beef', 'pork', 'bacon', 'sausage', 'ham', 'turkey', 'lamb', 'steak', 'meatball', 'pepperoni', 'prosciutto', 'shrimp', 'salmon', 'tuna', 'halibut', 'cod', 'fish', 'anchovy', 'anchovies', 'crab', 'scallop', 'veal', 'brisket', 'mince', 'ground meat'],
        'Bakery' => ['bread', 'tortilla', 'bun', 'roll', 'bagel', 'pita', 'baguette', 'naan', 'chapatti', 'brioche', 'wrap'],
        'Fruits' => ['apple', 'banana', 'lemon', 'lime', 'orange', 'berry', 'berries', 'strawberry', 'blueberry', 'cherry', 'avocado', 'grape', 'mango', 'pineapple', 'peach', 'pear', 'pumpkin', 'raisin', 'date', 'fig', 'melon', 'cranberr'],
        'Vegetables' => ['onion', 'garlic', 'tomato', 'potato', 'carrot', 'pepper', 'spinach', 'lettuce', 'broccoli', 'mushroom', 'cabbage', 'celery', 'cauliflower', 'zucchini', 'eggplant', 'corn', 'cucumber', 'squash', 'ginger', 'parsley', 'cilantro', 'basil', 'scallion', 'shallot', 'leek', 'kale', 'pea', 'green bean', 'asparagus', 'beet', 'radish', 'chive', 'jalapeno', 'chili', 'herb', 'mint', 'arugula', 'brussels', 'artichoke', 'olive', 'sweet potato', 'salad'],
    ];

    public const CATEGORY_ORDER = ['Meat & Fish', 'Vegetables', 'Fruits', 'Dairy & Eggs', 'Bakery', 'Pantry', 'Spices & Seasonings', 'Other'];

    public static function parseQuantity(mixed $raw): float
    {
        $s = trim((string) $raw);
        if ($s === '') {
            return 0.0;
        }

        $total = 0.0;
        foreach (self::FRACTIONS as $glyph => $value) {
            if (str_contains($s, $glyph)) {
                $s = str_replace($glyph, ' '.$value.' ', $s);
            }
        }

        foreach (preg_split('/\s+/u', trim($s)) as $part) {
            if ($part === '') {
                continue;
            }
            if (str_contains($part, '/')) {
                [$n, $d] = array_pad(explode('/', $part, 2), 2, 1);
                $total += is_numeric($n) && is_numeric($d) && (float) $d != 0.0 ? (float) $n / (float) $d : 0;
            } elseif (is_numeric($part)) {
                $total += (float) $part;
            }
        }

        return $total;
    }

    /**
     * @return array{key:string,name:string,dim:string,qty:float,unit:string}|null
     */
    public function component(array $component): ?array
    {
        $ing = $component['ingredient'] ?? [];
        $name = trim((string) ($ing['display_singular'] ?? $ing['name'] ?? ''));
        if ($name === '') {
            $name = trim(explode(',', (string) preg_replace('/^[\d\s\/.¼½¾⅓⅔⅛]+(?:[a-z]+\.?\s+)?/iu', '', (string) ($component['raw_text'] ?? '')))[0]);
        }
        if ($name === '') {
            return null;
        }

        $key = 'n'.trim((string) preg_replace('/\s+/u', ' ', str_replace('-', ' ', mb_strtolower($name))));
        $measurement = $this->pick($component['measurements'] ?? []);
        $unknown = ['key' => $key, 'name' => $name, 'dim' => 'unknown', 'qty' => 0.0, 'unit' => ''];

        if ($measurement === null) {
            return $unknown;
        }

        $qty = self::parseQuantity($measurement['quantity'] ?? '');
        $unit = mb_strtolower((string) ($measurement['unit']['name'] ?? ''));

        if ($qty <= 0) {
            return $unknown;
        }

        if (isset(self::UNITS[$unit])) {
            [$dim, $factor] = self::UNITS[$unit];

            return ['key' => $key, 'name' => $name, 'dim' => $dim, 'qty' => $qty * $factor, 'unit' => $dim === 'mass' ? 'g' : 'ml'];
        }

        return ['key' => $key, 'name' => $name, 'dim' => 'count', 'qty' => $qty, 'unit' => $unit];
    }

    /** Prefers metric measurements, then convertible ones, then anything else. */
    private function pick(array $measurements): ?array
    {
        foreach ([['gram', 'kilogram', 'milliliter', 'liter'], array_keys(self::UNITS)] as $wanted) {
            foreach ($measurements as $m) {
                if (in_array(mb_strtolower((string) ($m['unit']['name'] ?? '')), $wanted, true) && self::parseQuantity($m['quantity'] ?? '') > 0) {
                    return $m;
                }
            }
        }

        return $measurements[0] ?? null;
    }

    public function category(string $name): string
    {
        $name = mb_strtolower($name);
        foreach (self::CATEGORIES as $category => $words) {
            foreach ($words as $w) {
                if (preg_match('/\b'.preg_quote($w, '/').'(?:e?s)?\b/u', $name)) {
                    return $category;
                }
            }
        }

        return 'Other';
    }

    public function format(string $dim, float $qty, string $unit): string
    {
        if ($dim === 'mass') {
            $g = (int) (ceil($qty / 5) * 5);

            return $g >= 1000 ? rtrim(rtrim(number_format($g / 1000, 2, '.', ''), '0'), '.').' kg' : $g.' g';
        }

        if ($dim === 'volume') {
            $ml = (int) (ceil($qty / 5) * 5);
            $text = $ml >= 1000 ? rtrim(rtrim(number_format($ml / 1000, 2, '.', ''), '0'), '.').' l' : $ml.' ml';
            if ($ml >= 60 && $ml < 4000) {
                $cups = round($ml / 236.588 * 4) / 4;
                if ($cups >= 0.25) {
                    $text .= ' (≈ '.rtrim(rtrim(number_format($cups, 2, '.', ''), '0'), '.').' cup'.($cups > 1 ? 's' : '').')';
                }
            }

            return $text;
        }

        $n = (int) ceil($qty - 0.0001);
        if ($unit === '') {
            return $n.($n > 1 ? ' pcs' : ' pc');
        }
        $plural = $n > 1 ? (preg_match('/(s|x|ch|sh)$/', $unit) ? 'es' : 's') : '';

        return $n.' '.$unit.$plural;
    }
}
