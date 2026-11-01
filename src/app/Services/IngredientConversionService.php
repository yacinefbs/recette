<?php

namespace App\Services;

class IngredientConversionService
{
    public const UNITS = [
        'g' => 'g', 'kg' => 'kg', 'ml' => 'ml', 'l' => 'l',
        'cup' => 'cup', 'tbsp' => 'tbsp', 'tsp' => 'tsp', 'item' => 'item(s)',
    ];

    private const ML = ['ml' => 1, 'l' => 1000, 'cup' => 240, 'tbsp' => 15, 'tsp' => 5];

    private const G = ['g' => 1, 'kg' => 1000];

    private const FRACTIONS = [1 => '⅛', 2 => '¼', 3 => '⅜', 4 => '½', 5 => '⅝', 6 => '¾', 7 => '⅞'];

    /** "1 cup butter → ½ to ¾ cup applesauce" */
    public function describeRatio(float $oq, string $ou, string $name, array $components): string
    {
        $left = $ou === 'item' ? $this->number($oq).' '.$name : $this->number($oq).' '.$ou.' '.$name;
        $right = collect($components)->map(function ($c) {
            $q = $this->number($c[1]);
            if (! empty($c[3])) {
                $q .= ' to '.$this->number($c[3]);
            }

            return $q.' '.$c[2].' '.$c[0];
        })->implode(' + ');

        return $left.' → '.$right;
    }

    /**
     * Scales the components of a substitute to the quantity entered by the visitor.
     *
     * @return array{lines: array<int,string>, note: ?string}
     */
    public function scale(array $components, float $origQty, string $origUnit, float $userQty, string $userUnit, ?float $gramsPerCup): array
    {
        $converted = $this->toOriginalUnit($userQty, $userUnit, $origUnit, $gramsPerCup);
        if ($converted === null) {
            return ['lines' => [], 'note' => $this->refusal($userUnit, $origUnit)];
        }

        $factor = $converted / max($origQty, 0.0001);
        $lines = [];
        foreach ($components as $c) {
            $min = $c['qty'] * $factor;
            $max = isset($c['qty_max']) ? $c['qty_max'] * $factor : null;
            $lines[] = $this->formatAmount($min, $max, $c['unit']).' '.$c['name'];
        }

        $approx = $userUnit !== $origUnit && ($origUnit !== 'item')
            ? 'Quantities are rounded to practical kitchen measures.'
            : null;

        return ['lines' => $lines, 'note' => $approx];
    }

    private function toOriginalUnit(float $qty, string $from, string $to, ?float $gpc): ?float
    {
        if ($from === $to) {
            return $qty;
        }
        if ($from === 'item' || $to === 'item') {
            return null;
        }
        if (isset(self::ML[$from], self::ML[$to])) {
            return $qty * self::ML[$from] / self::ML[$to];
        }
        if (isset(self::G[$from], self::G[$to])) {
            return $qty * self::G[$from] / self::G[$to];
        }
        if (! $gpc) {
            return null;
        }
        if (isset(self::ML[$from])) { // volume -> weight
            return ($qty * self::ML[$from] / 240 * $gpc) / self::G[$to];
        }

        return ($qty * self::G[$from] / $gpc * 240) / self::ML[$to]; // weight -> volume
    }

    private function refusal(string $from, string $to): string
    {
        if ($from === 'item' || $to === 'item') {
            return 'This ingredient is counted in pieces, so please enter the quantity in pieces.';
        }

        return 'We cannot convert between weight and volume for this ingredient without a reliable density, so no quantity is calculated. Use the ratio shown instead.';
    }

    private function formatAmount(float $qty, ?float $max, string $unit): string
    {
        if (isset(self::ML[$unit])) {
            $ml = $qty * self::ML[$unit];
            $mlMax = $max !== null ? $max * self::ML[$unit] : null;

            return $this->volume($ml, $mlMax);
        }
        if ($unit === 'g') {
            $r = fn ($v) => $v >= 1000 ? $this->number(round($v / 1000, 2)).' kg' : round($v).' g';

            return $max !== null ? round($qty).' to '.$r($max) : $r($qty);
        }

        return $this->number(round($qty * 2) / 2).($max !== null ? ' to '.$this->number(round($max * 2) / 2) : '').' '.$unit;
    }

    private function volume(float $ml, ?float $mlMax): string
    {
        $ref = $mlMax ?? $ml;
        if ($ref >= 120) {
            $f = fn ($v) => $this->number(round($v / 240 * 8) / 8);
            $unit = 'cup';
        } elseif ($ref >= 15) {
            $f = fn ($v) => $this->number(round($v / 15 * 2) / 2);
            $unit = 'tbsp';
        } else {
            $f = fn ($v) => $this->number(max(round($v / 5 * 8) / 8, 0.125));
            $unit = 'tsp';
        }
        $text = $mlMax !== null ? $f($ml).' to '.$f($mlMax) : $f($ml);
        $mlText = $mlMax !== null ? round($ml).'–'.round($mlMax) : (string) round($ml);

        return $text.' '.$unit.' (≈ '.$mlText.' ml)';
    }

    /** 0.75 => "¾", 1.5 => "1 ½", 3 => "3" */
    public function number(float $v): string
    {
        $eighths = (int) round($v * 8);
        $whole = intdiv($eighths, 8);
        $rest = $eighths % 8;
        if ($rest === 0) {
            return (string) $whole;
        }
        $frac = self::FRACTIONS[$rest];

        return $whole > 0 ? $whole.' '.$frac : $frac;
    }
}
