<?php

it('defines ported lang keys in en, ar, and bn', function () {
    $keys = [
        'dashboard.signing_queue_link',
        'loading.issue',
        'loading.issueAndPrint',
        'catalog.cancel',
        'customers.guest',
        'invoices.zatca_precheck.buyer_postal',
        'invoices.zatca_precheck.buyer_address_country_sa',
    ];

    foreach (['en', 'ar', 'bn'] as $locale) {
        foreach ($keys as $key) {
            $value = __($key, [], $locale);
            expect($value)->not->toBe($key, "Missing {$key} in {$locale}")
                ->and(trim((string) $value))->not->toBe('');
        }
    }
});
