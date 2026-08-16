<?php

use App\Support\Money;

if (! function_exists('money')) {
    /** Format integer piastres for display: 6150000 -> "61,500 ج.م" */
    function money(?int $cents, bool $withCurrency = true): string
    {
        return Money::format($cents ?? 0, $withCurrency);
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
