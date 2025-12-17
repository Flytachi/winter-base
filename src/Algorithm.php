<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

use Random\Randomizer;
use Random\Engine\Secure;

/**
 * Class Algorithm
 *
 * A modernized version of the Algorithm class, optimized for PHP 8.2+ using the \Random\Randomizer extension
 * for maximum performance and security in random data generation.
 *
 * The methods provided by `Algorithm` are functionally identical to the original but leverage
 * modern PHP features for superior performance.
 *
 * @version 3.0
 * @author Flytachi
 * @requires PHP >= 8.2
 */
final class Algorithm
{
    /**
     * A reusable, high-performance, cryptographically secure randomizer instance.
     * Stored statically to avoid re-creation on every method call.
     * @var Randomizer|null
     */
    private static ?Randomizer $randomizer = null;

    /**
     * Generates a cryptographically secure random string of a specified length.
     *
     * @param int $length The desired length of the string.
     * @param string|null $alphabet An optional alphabet to use. Defaults to alphanumeric characters.
     * @return string The generated random string.
     */
    public static function random(int $length, ?string $alphabet = null): string
    {
        self::$randomizer ??= new Randomizer(new Secure());
        $alphabet ??= implode(range('a', 'z')) . implode(range('A', 'Z')) . implode(range(0, 9));
        return self::$randomizer->getBytesFromString($alphabet, $length);
    }

    /**
     * Randomly selects an element based on its weight. (Simple linear scan version)
     * This version is generally fast and reliable for most use cases.
     *
     * @template Item
     * @param array<Item> $values An indexed array of elements.
     * @param array<int|float> $weights An indexed array of corresponding weights.
     * @return Item|null The selected item, or null if input arrays are empty.
     */
    public static function weightedRandomLite(array $values, array $weights)
    {
        if (empty($values)) {
            return null;
        }

        self::$randomizer ??= new Randomizer(new Secure());
        $totalWeight = array_sum($weights);

        $randomValue = self::$randomizer->getFloat(
            0,
            $totalWeight,
            \Random\IntervalBoundary::ClosedOpen
        ); // (0 <= x < totalWeight)

        foreach ($weights as $key => $weight) {
            if ($randomValue < $weight) {
                return $values[$key];
            }
            $randomValue -= $weight;
        }

        return end($values);
    }

    /**
     * Randomly selects an element based on its weight, optimized for a very large number of elements.
     * This version uses a binary search for performance with thousands of items.
     *
     * @template Item
     * @param array<Item> $values An indexed array of elements.
     * @param array<int|float> $weights An indexed array of corresponding weights.
     * @return Item|null The selected item, or null if input arrays are empty.
     */
    public static function weightedRandom(array $values, array $weights)
    {
        if (empty($values)) {
            return null;
        }

        self::$randomizer ??= new Randomizer(new Secure());

        $cum_weights = [];
        $total = 0;
        foreach ($weights as $weight) {
            $total += $weight;
            $cum_weights[] = $total;
        }

        $rand_float = self::$randomizer->getFloat(0, $total, \Random\IntervalBoundary::ClosedOpen);
        $index = self::binarySearch($cum_weights, $rand_float);
        return $values[$index] ?? null;
    }

    /**
     * Calculates the probability of each element in percentage based on its weight.
     * This method does not involve randomness and remains unchanged.
     *
     * @param array $values
     * @param array $weights
     * @param bool $isCombine If true, combines the value and its calculated probability.
     * @return array
     */
    public static function weightedCalculateProbabilities(array $values, array $weights, bool $isCombine = false): array
    {
        $totalWeight = array_sum($weights);
        if ($totalWeight === 0) {
            return [];
        }

        $probabilities = [];
        foreach ($weights as $key => $weight) {
            $calculated = ($weight / $totalWeight) * 100;
            if ($isCombine) {
                $probabilities[$key] = [
                    'value' => $values[$key],
                    'calculate' => $calculated,
                ];
            } else {
                $probabilities[$key] = $calculated;
            }
        }
        return $probabilities;
    }

    /**
     * Performs a binary search on a sorted array to find the index of the first element
     * that is greater than or equal to the given value.
     * This method does not involve randomness and remains unchanged.
     *
     * @param array<int|float> $arr The sorted array to search in.
     * @param int|float $value The value to search for.
     * @return int The found index, or -1 if the value is greater than all elements.
     */
    public static function binarySearch(array $arr, int|float $value): int
    {
        $low = 0;
        $high = count($arr) - 1;
        $index = -1;

        while ($low <= $high) {
            $mid = $low + intdiv($high - $low, 2);
            if ($arr[$mid] >= $value) {
                $index = $mid;
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        return $index;
    }
}
