<?php

$en = require __DIR__.'/../en/pos.php';

return array_replace($en, [
    'receipt_preview' => [
        'paid' => 'ادائیگی موصول ہو گئی',
        'title' => 'رسید :number',
        'print' => 'پرنٹ',
        'new_order' => 'نیا آرڈر',
        'close' => 'بند کریں',
    ],
    'cash' => 'نقد',
    'card' => 'کارڈ',
    'mada' => 'مدی',
    'other' => 'دیگر',
    'payment_method' => 'ادائیگی کا طریقہ',
    'current_order' => 'موجودہ آرڈر',
    'pay_and_print' => 'ادائیگی اور پرنٹ',
    'subtotal_ex_vat' => 'ذیلی کل (VAT کے بغیر)',
    'vat' => 'VAT',
    'total_inc_vat' => 'کل (VAT سمیت)',
    'promo_discount_ex_vat' => 'پرومو ڈسکاؤنٹ (VAT کے بغیر)',
    'open_cart' => 'کارٹ دیکھیں',
    'mobile_cart' => [
        'items' => ':count آئٹم|:count آئٹمز',
        'expand' => 'آرڈر دیکھیں',
        'collapse' => 'آرڈر چھپائیں',
        'pay' => 'ادائیگی',
        'add_discount' => 'ڈسکاؤنٹ شامل کریں',
    ],
]);
