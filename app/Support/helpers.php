<?php

use App\Support\Money;

if (! function_exists('money')) {
    /** Format integer piastres for display: 6150000 -> "61,500 ج.م" */
    function money(?int $cents, bool $withCurrency = true): string
    {
        return Money::format($cents ?? 0, $withCurrency);
    }
}

if (! function_exists('arabic_count')) {
    /**
     * Arabic marks quantity in more forms than English does, and the dual is
     * the one that gives a translated interface away: "2 متاجر" is wrong where
     * "متجرين" is what a person would say. Counts of one and two carry the
     * number inside the word, three to ten take the plural, and eleven upward
     * returns to the singular in the accusative.
     *
     * Returns the whole phrase, because where the numeral goes changes with
     * the form — and half the point is that 1 and 2 do not show one at all.
     */
    function arabic_count(int $n, string $one, string $two, string $few, ?string $many = null): string
    {
        return match (true) {
            $n === 1 => $one,
            $n === 2 => $two,
            $n <= 10 => $n.' '.$few,
            default => $n.' '.($many ?? $few),
        };
    }
}

if (! function_exists('asset_v')) {
    /**
     * Cache-busted asset URL. The stylesheet is hand-written and served
     * directly — no bundler in the request path, no build step to forget.
     */
    function asset_v(string $path): string
    {
        static $versions = [];

        if (! isset($versions[$path])) {
            $file = public_path($path);
            $versions[$path] = is_file($file) ? (string) filemtime($file) : '1';
        }

        return asset($path).'?v='.$versions[$path];
    }
}
