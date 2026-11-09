<?php

namespace App\Rules;

use App\Services\Gs1Service;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Gs1Sscc implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('Mã định danh SSCC phải là dạng chuỗi ký tự.');
            return;
        }

        $sscc = trim($value);

        if (strlen($sscc) !== 18) {
            $fail("Mã SSCC chuẩn GS1 phải có đúng 18 chữ số (hiện tại: " . strlen($sscc) . " ký tự).");
            return;
        }

        if (!ctype_digit($sscc)) {
            $fail('Mã SSCC chuẩn GS1 chỉ được phép chứa các chữ số từ 0 đến 9.');
            return;
        }

        $gs1Service = app(Gs1Service::class);
        $result = $gs1Service->validateSscc($sscc);

        if (!$result['valid']) {
            $fail($result['error'] ?? 'Mã SSCC không thỏa mãn quy chuẩn GS1.');
        }
    }
}

