<?php

return [
    'title' => 'انوائس',
    'subtitle' => 'جاری دستاویزات، ZATCA کی حیثیت، رسید کے ذریعے پرنٹ (کلیئرنس سرٹیفکیٹ نہیں).',
    'nav' => 'انوائس',
    'search' => 'تلاش',
    'search_hint' => 'دستاویز نمبر یا ہیش',
    'type_filter' => 'دستاویز کی قسم',
    'zatca_filter' => 'ZATCA حیثیت',
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
    'zatca' => [
        'none' => 'قطار میں نہیں',
        'pending' => 'زیر التوا',
        'submitted' => 'بھیجا گیا',
        'accepted' => 'قبول',
        'rejected' => 'مسترد',
        'failed' => 'ناکام',
        'reported' => 'رپورٹ شدہ',
        'reported_with_warnings' => 'رپورٹ (تنبیہات)',
    ],
    'reconcile_link' => 'ZATCA رپورٹنگ',
    'errors' => [
        'signing_failed' => 'اس انوائس پر cryptographic دستخط نہیں ہو سکا۔ ایڈمن میں EGS کیز اور ZATCA CSID چیک کریں اور دوبارہ کوشش کریں.',
        'signing_credentials_invalid' => 'اس EGS پر compliance CSID دستخط کے لیے لوڈ نہیں ہو سکا۔ ZATCA ایڈمن میں سرٹیفکیٹ چیک کریں یا سپورٹ کو نیچے والی تکنیکی تفصیل بھیجیں۔',
    ],
];
