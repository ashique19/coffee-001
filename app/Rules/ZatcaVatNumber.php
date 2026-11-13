<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ZatcaVatNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $vat = preg_replace('/\D/', '', (string) $value) ?? '';

        if (! preg_match('/^3\d{13}3$/', $vat)) {
            $fail(__('zatca_admin.validation.vat_format'));
        }
    }
}
