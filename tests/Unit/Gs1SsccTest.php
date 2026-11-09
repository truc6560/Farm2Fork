<?php

namespace Tests\Unit;

use App\Rules\Gs1Sscc;
use App\Services\Gs1Service;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class Gs1SsccTest extends TestCase
{
    protected Gs1Service $gs1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gs1 = new Gs1Service();
    }

    /**
     * Test GS1 Modulo 10 Check Digit calculation against official GS1 test vectors.
     */
    public function test_gs1_modulo_10_calculation_with_official_vectors(): void
    {
        // Vector 1: Standard GS1 Specification example
        // Extension 0, Prefix 0614141, Serial 123456789 -> Expected check: 0
        $this->assertSame(0, $this->gs1->calculateCheckDigit('00614141123456789'));

        // Vector 2: From database row 1
        $this->assertSame(1, $this->gs1->calculateCheckDigit('09990001000000001'));

        // Vector 3: From database row 2
        $this->assertSame(8, $this->gs1->calculateCheckDigit('09990001000000002'));

        // Vector 4: Prefix 8938500
        $this->assertSame(0, $this->gs1->calculateCheckDigit('08938500000000001'));
    }

    /**
     * Test that 18-digit SSCC codes with leading zero are preserved and validated.
     */
    public function test_leading_zero_is_preserved_and_validated(): void
    {
        $sscc = '006141411234567890';
        $this->assertSame(18, strlen($sscc));
        $this->assertTrue(str_starts_with($sscc, '00'));

        $res = $this->gs1->validateSscc($sscc);
        $this->assertTrue($res['valid']);
        $this->assertNull($res['error']);
        $this->assertSame('0', $res['parsed']['extension_digit']);
        $this->assertSame(0, $res['parsed']['check_digit']);
    }

    /**
     * Test rejection of invalid formats, lengths, and non-digit characters.
     */
    public function test_rejection_of_invalid_formats(): void
    {
        // Wrong length
        $short = $this->gs1->validateSscc('00614141123456789');
        $this->assertFalse($short['valid']);

        $long = $this->gs1->validateSscc('0061414112345678901');
        $this->assertFalse($long['valid']);

        // Non-digit characters
        $alpha = $this->gs1->validateSscc('00614141123456789A');
        $this->assertFalse($alpha['valid']);

        // Internal pallet code
        $internal = $this->gs1->validateSscc('PALLET001');
        $this->assertFalse($internal['valid']);

        // Incorrect check digit
        $badCheck = $this->gs1->validateSscc('006141411234567899');
        $this->assertFalse($badCheck['valid']);
        $this->assertStringContainsString('Check digit', $badCheck['error']);
    }

    /**
     * Test custom Laravel Validator rule Gs1Sscc.
     */
    public function test_laravel_validation_rule_accepts_valid_and_rejects_invalid(): void
    {
        // Valid
        $val1 = Validator::make(['sscc' => '006141411234567890'], ['sscc' => [new Gs1Sscc()]]);
        $this->assertTrue($val1->passes());

        // Invalid checksum
        $val2 = Validator::make(['sscc' => '006141411234567895'], ['sscc' => [new Gs1Sscc()]]);
        $this->assertTrue($val2->fails());

        // Non-digits
        $val3 = Validator::make(['sscc' => '00614141123456789X'], ['sscc' => [new Gs1Sscc()]]);
        $this->assertTrue($val3->fails());
    }

    /**
     * Test safe generator generates exactly 18 digits with valid checksum.
     */
    public function test_generator_produces_valid_unique_sscc(): void
    {
        $code1 = $this->gs1->generateNextSscc();
        $code2 = $this->gs1->generateNextSscc();

        $this->assertSame(18, strlen($code1));
        $this->assertSame(18, strlen($code2));
        $this->assertTrue(ctype_digit($code1));
        $this->assertTrue(ctype_digit($code2));

        $this->assertTrue($this->gs1->validateSscc($code1)['valid']);
        $this->assertTrue($this->gs1->validateSscc($code2)['valid']);
        $this->assertNotSame($code1, $code2);
    }

    /**
     * Test GS1-128 barcode SVG generation.
     */
    public function test_barcode_svg_generation(): void
    {
        $svg = $this->gs1->generateBarcodeSvg('006141411234567890');
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<rect', $svg);
        $this->assertStringContainsString('GS1-128', $svg);
    }
}

