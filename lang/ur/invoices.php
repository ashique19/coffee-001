<?php

return [
    'title' => 'انوائس',
    'subtitle' => 'جاری دستاویزات، signing service کی حیثیت، رسید کے ذریعے پرنٹ (کلیئرنس سرٹیفکیٹ نہیں).',
    'nav' => 'انوائس',
    'search' => 'تلاش',
    'search_hint' => 'دستاویز نمبر یا ہیش',
    'type_filter' => 'دستاویز کی قسم',
    'signing_filter' => 'signing service حیثیت',
    'filter_all' => 'تمام',
    'empty' => 'کوئی انوائس نہیں ملی.',
    'ref' => 'حوالہ',
    'order' => 'آرڈر',
    'view_order' => 'آرڈر ہسٹری',
    'print' => 'پرنٹ / PDF',
    'credit_badge' => 'کریڈٹ نوٹ',
    'type' => [
        'simplified' => 'سادہ',
        'standard' => 'معیاری',
        'credit_note' => 'کریڈٹ نوٹ',
        'debit_note' => 'ڈیبٹ نوٹ',
    ],
    'signing' => [
        'none' => 'قطار میں نہیں',
        'pending' => 'زیر التوا',
        'submitted' => 'بھیجا گیا',
        'accepted' => 'قبول',
        'rejected' => 'مسترد',
        'failed' => 'ناکام',
        'reported' => 'رپورٹ شدہ',
        'reported_with_warnings' => 'رپورٹ (تنبیہات)',
    ],
    'queue_link' => 'signing service رپورٹنگ',
    'errors' => [
        'signing_failed' => 'اس انوائس پر cryptographic دستخط نہیں ہو سکا۔ ایڈمن میں کیز اور signing service CSID چیک کریں اور دوبارہ کوشش کریں.',
        'signing_credentials_invalid' => 'اس پر compliance CSID دستخط کے لیے لوڈ نہیں ہو سکا۔ signing service ایڈمن میں سرٹیفکیٹ چیک کریں یا سپورٹ کو نیچے والی تکنیکی تفصیل بھیجیں۔',
    ],
];
