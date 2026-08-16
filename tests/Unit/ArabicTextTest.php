<?php

namespace Tests\Unit;

use App\Support\ArabicText;
use PHPUnit\Framework\TestCase;

class ArabicTextTest extends TestCase
{
    /**
     * All of these are the same product to a customer, so they must fold to
     * the same key. This is what stops the catalog fragmenting.
     */
    public function test_spelling_variants_fold_to_one_key(): void
    {
        $expected = ArabicText::normalize('ايفون 17 برو');

        foreach (['أيفون 17 برو', 'إيفون ١٧ برو', 'آيفون 17 برو', 'ايفون  17   برو', 'ايفـــون 17 برو'] as $variant) {
            $this->assertSame($expected, ArabicText::normalize($variant), "Failed on: {$variant}");
        }
    }

    public function test_ta_marbuta_and_alef_maqsura_are_folded(): void
    {
        $this->assertSame(ArabicText::normalize('ثلاجة'), ArabicText::normalize('ثلاجه'));
        $this->assertSame(ArabicText::normalize('مصطفى'), ArabicText::normalize('مصطفي'));
    }

    /** Arabic punctuation must not survive — it would poison the tokens. */
    public function test_diacritics_and_punctuation_are_stripped(): void
    {
        $this->assertSame('غساله اوتوماتيك', ArabicText::normalize('غَسَّالَة، أوتوماتيك!'));
        $this->assertSame('ثلاجه كريازي', ArabicText::normalize('ثلاجة؟ كريازي؛'));
    }

    public function test_latin_text_is_lowercased_and_kept(): void
    {
        $this->assertSame('samsung galaxy s25', ArabicText::normalize('Samsung Galaxy S25'));
    }

    public function test_arabic_indic_digits_become_western(): void
    {
        $this->assertSame('ايفون 17', ArabicText::normalize('ايفون ١٧'));
    }

    public function test_tokens_drop_noise_that_would_match_everything(): void
    {
        $this->assertSame(['samsung', 'galaxy'], ArabicText::tokens('Samsung a Galaxy'));
        $this->assertSame([], ArabicText::tokens(''));
    }
}
