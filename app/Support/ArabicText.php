<?php

namespace App\Support;

/**
 * Arabic search only works if both sides of the comparison are folded the same
 * way. "آيفون ١٧ برو" and "ايفون 17 برو" must produce the same key, otherwise
 * the catalog fragments exactly the way the product brief warns about.
 */
final class ArabicText
{
    private const TASHKEEL = '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u';

    private const REPLACEMENTS = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ة' => 'ه',
        'ى' => 'ي', 'ئ' => 'ي',
        'ؤ' => 'و',
        'ـ' => '',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    public static function normalize(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace(self::TASHKEEL, '', $value) ?? $value;
        $value = strtr($value, self::REPLACEMENTS);

        // Letters, digits and separators only. Matching on \p{L} rather than
        // \p{Arabic} matters: Arabic punctuation (، ؛ ؟) lives inside the
        // Arabic block and would otherwise survive into the search tokens.
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /** Tokens long enough to be useful in a FULLTEXT boolean query. */
    public static function tokens(?string $value, int $minLength = 2): array
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(
            explode(' ', $normalized),
            fn (string $token) => mb_strlen($token) >= $minLength
        ));
    }
}
