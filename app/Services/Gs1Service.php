<?php

namespace App\Services;

use App\Models\DonViDongGoiSscc;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Gs1Service
{
    /**
     * Official Code 128 pattern table (107 symbols, indices 0..106).
     * Each pattern is 6 elements representing the alternating widths of bars and spaces.
     * The Stop character (index 106) has 7 elements.
     */
    protected const CODE128_PATTERNS = [
        0   => [2, 1, 2, 2, 2, 2], // '00'
        1   => [2, 2, 2, 1, 2, 2], // '01'
        2   => [2, 2, 2, 2, 2, 1], // '02'
        3   => [1, 2, 1, 2, 2, 3], // '03'
        4   => [1, 2, 1, 3, 2, 2], // '04'
        5   => [1, 3, 1, 2, 2, 2], // '05'
        6   => [1, 2, 2, 2, 1, 3], // '06'
        7   => [1, 2, 2, 3, 1, 2], // '07'
        8   => [1, 3, 2, 2, 1, 2], // '08'
        9   => [2, 2, 1, 2, 1, 3], // '09'
        10  => [2, 2, 1, 3, 1, 2], // '10'
        11  => [2, 3, 1, 2, 1, 2], // '11'
        12  => [1, 1, 2, 2, 3, 2], // '12'
        13  => [1, 2, 2, 1, 3, 2], // '13'
        14  => [1, 2, 2, 2, 3, 1], // '14'
        15  => [1, 1, 3, 2, 2, 2], // '15'
        16  => [1, 2, 3, 1, 2, 2], // '16'
        17  => [1, 2, 3, 2, 2, 1], // '17'
        18  => [2, 2, 3, 2, 1, 1], // '18'
        19  => [2, 2, 1, 1, 3, 2], // '19'
        20  => [2, 2, 1, 2, 3, 1], // '20'
        21  => [2, 1, 3, 2, 1, 2], // '21'
        22  => [2, 2, 3, 1, 1, 2], // '22'
        23  => [3, 1, 2, 1, 3, 1], // '23'
        24  => [3, 1, 1, 2, 2, 2], // '24'
        25  => [3, 2, 1, 1, 2, 2], // '25'
        26  => [3, 2, 1, 2, 2, 1], // '26'
        27  => [3, 1, 2, 2, 1, 2], // '27'
        28  => [3, 2, 2, 1, 1, 2], // '28'
        29  => [3, 2, 2, 2, 1, 1], // '29'
        30  => [2, 1, 2, 1, 2, 3], // '30'
        31  => [2, 1, 2, 3, 2, 1], // '31'
        32  => [2, 3, 2, 1, 2, 1], // '32'
        33  => [1, 1, 1, 3, 2, 3], // '33'
        34  => [1, 3, 1, 1, 2, 3], // '34'
        35  => [1, 3, 1, 3, 2, 1], // '35'
        36  => [1, 1, 2, 3, 1, 3], // '36'
        37  => [1, 3, 2, 1, 1, 3], // '37'
        38  => [1, 3, 2, 3, 1, 1], // '38'
        39  => [2, 1, 1, 3, 1, 3], // '39'
        40  => [2, 3, 1, 1, 1, 3], // '40'
        41  => [2, 3, 1, 3, 1, 1], // '41'
        42  => [1, 1, 2, 1, 3, 3], // '42'
        43  => [1, 1, 2, 3, 3, 1], // '43'
        44  => [1, 3, 2, 1, 3, 1], // '44'
        45  => [1, 1, 3, 1, 2, 3], // '45'
        46  => [1, 1, 3, 3, 2, 1], // '46'
        47  => [1, 3, 3, 1, 2, 1], // '47'
        48  => [3, 1, 3, 1, 2, 1], // '48'
        49  => [2, 1, 1, 3, 3, 1], // '49'
        50  => [2, 3, 1, 1, 3, 1], // '50'
        51  => [2, 1, 3, 1, 1, 3], // '51'
        52  => [2, 1, 3, 3, 1, 1], // '52'
        53  => [2, 1, 3, 1, 3, 1], // '53'
        54  => [3, 1, 1, 1, 2, 3], // '54'
        55  => [3, 1, 1, 3, 2, 1], // '55'
        56  => [3, 3, 1, 1, 2, 1], // '56'
        57  => [3, 1, 2, 1, 1, 3], // '57'
        58  => [3, 1, 2, 3, 1, 1], // '58'
        59  => [3, 3, 2, 1, 1, 1], // '59'
        60  => [3, 1, 4, 1, 1, 1], // '60'
        61  => [2, 2, 1, 4, 1, 1], // '61'
        62  => [4, 3, 1, 1, 1, 1], // '62'
        63  => [1, 1, 1, 2, 2, 4], // '63'
        64  => [1, 1, 1, 4, 2, 2], // '64'
        65  => [1, 2, 1, 1, 2, 4], // '65'
        66  => [1, 2, 1, 4, 2, 1], // '66'
        67  => [1, 4, 1, 1, 2, 2], // '67'
        68  => [1, 4, 1, 2, 2, 1], // '68'
        69  => [1, 1, 2, 2, 1, 4], // '69'
        70  => [1, 1, 2, 4, 1, 2], // '70'
        71  => [1, 2, 2, 1, 1, 4], // '71'
        72  => [1, 2, 2, 4, 1, 1], // '72'
        73  => [1, 4, 2, 1, 1, 2], // '73'
        74  => [1, 4, 2, 2, 1, 1], // '74'
        75  => [2, 4, 1, 2, 1, 1], // '75'
        76  => [2, 2, 1, 1, 1, 4], // '76'
        77  => [4, 1, 3, 1, 1, 1], // '77'
        78  => [2, 4, 1, 1, 1, 2], // '78'
        79  => [1, 3, 4, 1, 1, 1], // '79'
        80  => [1, 1, 1, 2, 4, 2], // '80'
        81  => [1, 2, 1, 1, 4, 2], // '81'
        82  => [1, 2, 1, 2, 4, 1], // '82'
        83  => [1, 1, 4, 2, 1, 2], // '83'
        84  => [1, 2, 4, 1, 1, 2], // '84'
        85  => [1, 2, 4, 2, 1, 1], // '85'
        86  => [4, 1, 1, 2, 1, 2], // '86'
        87  => [4, 2, 1, 1, 1, 2], // '87'
        88  => [4, 2, 1, 2, 1, 1], // '88'
        89  => [2, 1, 2, 1, 4, 1], // '89'
        90  => [2, 1, 4, 1, 2, 1], // '90'
        91  => [4, 1, 2, 1, 2, 1], // '91'
        92  => [1, 1, 1, 1, 4, 3], // '92'
        93  => [1, 1, 1, 3, 4, 1], // '93'
        94  => [1, 3, 1, 1, 4, 1], // '94'
        95  => [1, 1, 4, 1, 1, 3], // '95'
        96  => [1, 1, 4, 3, 1, 1], // '96'
        97  => [4, 1, 1, 1, 1, 3], // '97'
        98  => [4, 1, 1, 3, 1, 1], // '98'
        99  => [1, 1, 3, 1, 4, 1], // '99'
        100 => [1, 1, 4, 1, 3, 1], // Code A
        101 => [3, 1, 1, 1, 4, 1], // Code B
        102 => [4, 1, 1, 1, 3, 1], // FNC1
        103 => [2, 1, 1, 4, 1, 2], // Start A
        104 => [2, 1, 1, 2, 1, 4], // Start B
        105 => [2, 1, 1, 2, 3, 2], // Start C
        106 => [2, 3, 3, 1, 1, 1, 2], // Stop (7 elements)
    ];

    /**
     * Calculate GS1 Modulo 10 Check Digit for a 17-digit numeric string.
     *
     * In GS1 specifications:
     * Starting from the rightmost position (position 1 from right):
     * - Odd positions (1, 3, 5, ..., 17 from right): multiplier 3
     * - Even positions (2, 4, 6, ..., 16 from right): multiplier 1
     *
     * Check Digit = (10 - (Sum % 10)) % 10
     */
    public function calculateCheckDigit(string $base17): int
    {
        if (strlen($base17) !== 17 || !ctype_digit($base17)) {
            throw new InvalidArgumentException("Chuỗi tính check digit SSCC phải gồm đúng 17 chữ số (nhận được: '{$base17}').");
        }

        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $digit = (int)$base17[$i];
            // Position 17 from left is index 16 (pos 1 from right: odd -> factor 3)
            // Index 0 is pos 17 from right: odd -> factor 3
            // Thus, even index (0, 2, 4, ..., 16) has factor 3; odd index has factor 1.
            $multiplier = (($i % 2) === 0) ? 3 : 1;
            $sum += $digit * $multiplier;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Validate an 18-digit SSCC string against GS1 rules and Modulo 10 checksum.
     */
    public function validateSscc(?string $sscc): array
    {
        if (empty($sscc)) {
            return [
                'valid' => false,
                'error' => 'Mã SSCC không được để trống.',
                'parsed' => null,
            ];
        }

        $sscc = trim($sscc);

        if (strlen($sscc) !== 18) {
            return [
                'valid' => false,
                'error' => "Mã SSCC phải có đúng 18 chữ số (hiện tại: " . strlen($sscc) . " ký tự).",
                'parsed' => null,
            ];
        }

        if (!ctype_digit($sscc)) {
            return [
                'valid' => false,
                'error' => "Mã SSCC chỉ được chứa các ký tự số (0-9).",
                'parsed' => null,
            ];
        }

        $base17 = substr($sscc, 0, 17);
        $expectedCheck = $this->calculateCheckDigit($base17);
        $actualCheck = (int)substr($sscc, 17, 1);

        if ($expectedCheck !== $actualCheck) {
            return [
                'valid' => false,
                'error' => "Số kiểm tra (Check digit) không đúng theo thuật toán GS1 Modulo 10. Kỳ vọng: {$expectedCheck}, thực tế: {$actualCheck}.",
                'parsed' => null,
            ];
        }

        $companyPrefix = $this->getCompanyPrefix();
        $prefixLen = strlen($companyPrefix);

        return [
            'valid' => true,
            'error' => null,
            'parsed' => [
                'full' => $sscc,
                'extension_digit' => substr($sscc, 0, 1),
                'company_prefix' => substr($sscc, 1, $prefixLen),
                'serial_reference' => substr($sscc, 1 + $prefixLen, 16 - $prefixLen),
                'check_digit' => $actualCheck,
                'hri' => $this->formatHri($sscc),
            ],
        ];
    }

    /**
     * Get configured GS1 Company Prefix.
     */
    public function getCompanyPrefix(): string
    {
        $prefix = (string)config('gs1.company_prefix', '8938500');
        $prefix = preg_replace('/\D/', '', $prefix);

        if (strlen($prefix) < 4 || strlen($prefix) > 12) {
            // Fallback safe default GS1 Vietnam testing prefix
            return '8938500';
        }

        return $prefix;
    }

    /**
     * Check if the system is currently using a Demo / Testing GS1 prefix.
     */
    public function isDemoMode(): bool
    {
        return (bool)config('gs1.is_demo', true);
    }

    /**
     * Format SSCC into Human Readable Interpretation (HRI).
     * Example: (00) 0 8938500 00000001 1
     */
    public function formatHri(string $sscc, bool $withAi = true): string
    {
        if (strlen($sscc) !== 18 || !ctype_digit($sscc)) {
            return ($withAi ? '(00) ' : '') . $sscc;
        }

        $ext = substr($sscc, 0, 1);
        $prefixLength = strlen($this->getCompanyPrefix());
        $prefix = substr($sscc, 1, $prefixLength);
        $serial = substr($sscc, 1 + $prefixLength, 16 - $prefixLength);
        $check = substr($sscc, 17, 1);

        $formatted = "{$ext} {$prefix} {$serial} {$check}";
        return $withAi ? "(00) {$formatted}" : $formatted;
    }

    /**
     * Generate next valid, unique 18-digit SSCC adhering to GS1 standard.
     *
     * Handles concurrency and collision safely by verifying database uniqueness
     * and retrying with atomic sequence or secure pseudo-random serials.
     */
    public function generateNextSscc(?int $extensionDigit = null, ?string $companyPrefix = null): string
    {
        $ext = ($extensionDigit !== null && $extensionDigit >= 0 && $extensionDigit <= 9)
            ? (string)$extensionDigit
            : (string)config('gs1.default_extension_digit', 0);

        $prefix = $companyPrefix !== null ? preg_replace('/\D/', '', $companyPrefix) : $this->getCompanyPrefix();
        if (strlen($prefix) < 4 || strlen($prefix) > 12) {
            $prefix = '8938500';
        }

        // Extension (1) + Prefix (N) + Serial (16 - N) = 17 digits
        $serialLength = 16 - strlen($prefix);
        $strategy = config('gs1.serial_strategy', 'sequential');

        $maxAttempts = 15;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            if ($strategy === 'sequential') {
                $serial = $this->resolveNextSequentialSerial($ext, $prefix, $serialLength, $attempt);
            } else {
                $serial = $this->generateRandomSerial($serialLength);
            }

            $base17 = $ext . $prefix . $serial;
            $checkDigit = $this->calculateCheckDigit($base17);
            $candidateSscc = $base17 . $checkDigit;

            // Ensure uniqueness in database
            $exists = DB::table('don_vi_dong_goi_sscc')
                ->where('ma_sscc', $candidateSscc)
                ->exists();

            if (!$exists) {
                return $candidateSscc;
            }
        }

        // Fallback: timestamp-based unique seed
        $timeSeed = substr((string)(time() + mt_rand(100, 999)), -$serialLength);
        $serial = str_pad($timeSeed, $serialLength, '0', STR_PAD_LEFT);
        $base17 = $ext . $prefix . $serial;
        $checkDigit = $this->calculateCheckDigit($base17);
        return $base17 . $checkDigit;
    }

    /**
     * Resolve the next sequential serial reference based on database state and atomic cache counter.
     */
    protected function resolveNextSequentialSerial(string $ext, string $prefix, int $serialLength, int $attempt): string
    {
        $searchPrefix = $ext . $prefix;
        $prefixLen = strlen($searchPrefix);
        $cacheKey = "gs1_sscc_seq_{$searchPrefix}";

        if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            $maxRecord = DB::table('don_vi_dong_goi_sscc')
                ->where('ma_sscc', 'like', "{$searchPrefix}%")
                ->orderBy('ma_sscc', 'desc')
                ->value('ma_sscc');

            $initial = 0;
            if (!empty($maxRecord) && strlen($maxRecord) === 18) {
                $existingSerial = substr($maxRecord, $prefixLen, $serialLength);
                if (ctype_digit($existingSerial)) {
                    $initial = (int)$existingSerial;
                }
            }
            \Illuminate\Support\Facades\Cache::forever($cacheKey, $initial);
        }

        $nextNumber = (int)\Illuminate\Support\Facades\Cache::increment($cacheKey);

        $maxAllowed = (int)pow(10, $serialLength) - 1;
        if ($nextNumber > $maxAllowed) {
            // Serial overflow: generate safe pseudo-random
            return $this->generateRandomSerial($serialLength);
        }

        return str_pad((string)$nextNumber, $serialLength, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a zero-padded random serial reference of exact length.
     */
    protected function generateRandomSerial(int $length): string
    {
        $serial = '';
        for ($i = 0; $i < $length; $i++) {
            $serial .= (string)random_int(0, 9);
        }
        return $serial;
    }

    /**
     * Generate official GS1-128 SVG Barcode for an 18-digit SSCC.
     * Encodes AI (00) + 18 SSCC digits using Code 128 Subset C (FNC1 + 10 pairs).
     */
    public function generateBarcodeSvg(string $sscc, int $height = 65, int $scale = 2): string
    {
        if (strlen($sscc) !== 18 || !ctype_digit($sscc)) {
            return '';
        }

        // Full data payload with Application Identifier (00): 20 digits total
        $data = '00' . $sscc;

        // Split into 10 two-digit pairs for Code 128 Subset C
        $pairs = str_split($data, 2);

        // Code 128 Structure:
        // Start C (105) + FNC1 (102) + 10 pairs + Checksum + Stop (106)
        $symbols = [105, 102];
        foreach ($pairs as $p) {
            $symbols[] = (int)$p;
        }

        // Calculate Code 128 Checksum
        // Checksum = (StartCode + Sum(symbol * position)) % 103
        $sum = $symbols[0]; // Start C weight = 1
        for ($pos = 1; $pos < count($symbols); $pos++) {
            $sum += $symbols[$pos] * $pos;
        }
        $checksumSymbol = $sum % 103;
        $symbols[] = $checksumSymbol;
        $symbols[] = 106; // Stop character

        // Convert symbol values to bar/space sequence
        $modules = [];
        foreach ($symbols as $sym) {
            $pattern = self::CODE128_PATTERNS[$sym] ?? self::CODE128_PATTERNS[0];
            $isBar = true;
            foreach ($pattern as $width) {
                for ($w = 0; $w < $width; $w++) {
                    $modules[] = $isBar ? 1 : 0;
                }
                $isBar = !$isBar;
            }
        }

        // Add quiet zones (10 modules on each side)
        $quietZone = 10;
        $totalModules = count($modules) + ($quietZone * 2);
        $totalWidth = $totalModules * $scale;

        // Build SVG paths for black bars
        $rects = [];
        $x = $quietZone * $scale;
        $inBar = false;
        $barStart = 0;

        foreach ($modules as $mod) {
            if ($mod === 1 && !$inBar) {
                $inBar = true;
                $barStart = $x;
            } elseif ($mod === 0 && $inBar) {
                $inBar = false;
                $barWidth = $x - $barStart;
                $rects[] = "<rect x=\"{$barStart}\" y=\"0\" width=\"{$barWidth}\" height=\"{$height}\" fill=\"#1c1917\" />";
            }
            $x += $scale;
        }

        if ($inBar) {
            $barWidth = $x - $barStart;
            $rects[] = "<rect x=\"{$barStart}\" y=\"0\" width=\"{$barWidth}\" height=\"{$height}\" fill=\"#1c1917\" />";
        }

        $rectsSvg = implode("\n        ", $rects);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$totalWidth} {$height}" class="w-full h-auto max-h-[75px]" preserveAspectRatio="none" role="img" aria-label="GS1-128 Barcode (00){$sscc}">
    <rect width="100%" height="100%" fill="#ffffff" />
    <g>
        {$rectsSvg}
    </g>
</svg>
SVG;
    }
}
