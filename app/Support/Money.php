<?php

namespace App\Support;

/**
 * Every price in Banha.shop is an integer number of piastres. Floats never
 * touch money: 61,500.00 EGP is 6_150_000 and stays exact through totals,
 * shipping and order snapshots.
 */
final class Money
{
    public static function minorUnits(): int
    {
        return (int) config('banha.currency.minor_units', 100);
    }

    /** "1250.50" or 1250.5 -> 125050 */
    public static function toCents(int|float|string $amount): int
    {
        if (is_int($amount)) {
            return $amount * self::minorUnits();
        }

        $normalized = is_string($amount)
            ? str_replace([',', ' ', "\u{00A0}"], '', trim($amount))
            : $amount;

        return (int) round(((float) $normalized) * self::minorUnits());
    }

    public static function toMajor(int $cents): float
    {
        return $cents / self::minorUnits();
    }

    /**
     * Display form. Whole pounds drop the decimals — Egyptian retail prices are
     * quoted as "62,000 ج.م", not "62,000.00 ج.م".
     */
    public static function format(int $cents, bool $withCurrency = true): string
    {
        $minor = self::minorUnits();
        $decimals = $cents % $minor === 0 ? 0 : 2;
        $number = number_format($cents / $minor, $decimals, '.', ',');

        return $withCurrency
            ? $number.' '.config('banha.currency.symbol', 'ج.م')
            : $number;
    }

    /** Plain decimal string for schema.org / form values. */
    public static function decimal(int $cents): string
    {
        return number_format($cents / self::minorUnits(), 2, '.', '');
    }
}
