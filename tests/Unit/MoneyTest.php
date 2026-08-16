<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_amounts_convert_to_exact_integer_minor_units(): void
    {
        $this->assertSame(6150000, Money::toCents('61500'));
        $this->assertSame(6150050, Money::toCents('61500.50'));
        $this->assertSame(6150000, Money::toCents('61,500'));
        $this->assertSame(99999, Money::toCents(999.99));
    }

    /** Float arithmetic on money is exactly the bug this class exists to avoid. */
    public function test_a_classic_float_rounding_case_stays_exact(): void
    {
        $this->assertSame(1010, Money::toCents(10.10));
        $this->assertSame(7030, Money::toCents('70.30'));
    }

    public function test_whole_pounds_display_without_decimals(): void
    {
        $this->assertSame('61,500 ج.م', Money::format(6150000));
        $this->assertSame('980 ج.م', Money::format(98000));
    }

    public function test_partial_pounds_keep_their_piastres(): void
    {
        $this->assertSame('999.99 ج.م', Money::format(99999));
    }

    public function test_schema_output_is_always_two_decimals(): void
    {
        $this->assertSame('61500.00', Money::decimal(6150000));
        $this->assertSame('999.99', Money::decimal(99999));
    }
}
