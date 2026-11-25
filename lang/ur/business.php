<?php

$en = require __DIR__.'/../en/business.php';

return array_replace($en, [
    'legal' => array_merge($en['legal'], [
            'address_en' => 'سٹور فرنٹ پتہ (انگریزی)',
            'address_ar' => 'سٹور فرنٹ پتہ (عربی)',
            'managed_by_zheart' => 'Z-Heart کے ذریعے منظم۔ تبدیلی کے لیے ہم سے رابطہ کریں۔',
            'source_zheart' => 'آپ کی تازہ ترین Z-Heart انوائس کے مطابق۔',
            'source_local' => 'ابھی کوئی Z-Heart انوائس نہیں۔ یہ اس ایپ میں پہلے محفوظ کی گئی تفصیلات ہیں؛ نئی انوائسز Z-Heart استعمال کرتی ہیں۔',
            'storefront_heading' => 'سٹور فرنٹ پتہ',
            'storefront_hint' => 'آپ کی ویب سائٹ پر دکھایا جاتا ہے۔ انوائسز پر فروخت کنندہ کا پتہ Z-Heart سے چھپتا ہے۔',
            'seller_id' => 'فروخت کنندہ آئی ڈی',
            'not_set' => 'سیٹ نہیں',
    ]),
    'nav' => 'کاروبار',
    'title' => 'کاروباری ترتیبات',
    'subtitle' => 'برانڈ، قانونی، رابطہ اور اوقات — سٹورفرنٹ، رسیدیں اور انوائسز کے لیے۔',
]);
