<?php

use App\Support\DocumentTitle;

it('joins page and brand with an em dash and drops empty sides', function () {
    expect(DocumentTitle::join('Dashboard', 'Fancy Coffee'))->toBe('Dashboard — Fancy Coffee')
        ->and(DocumentTitle::join('Dashboard', ''))->toBe('Dashboard')
        ->and(DocumentTitle::join('', 'Fancy Coffee'))->toBe('Fancy Coffee')
        ->and(DocumentTitle::join('  ', '  '))->toBe('');
});
