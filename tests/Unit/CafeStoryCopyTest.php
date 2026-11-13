<?php

use App\Support\Storefront\CafeStoryCopy;

it('returns default paragraphs when stored story is blank', function () {
    $resolved = CafeStoryCopy::resolve([], 'en');

    expect($resolved['mode'])->toBe('default')
        ->and($resolved['pull_quote'])->toBe(__('cafe.default_story.pull_quote', [], 'en'))
        ->and($resolved['paragraphs'])->not->toBeEmpty();
});

it('returns custom body when stored story is present', function () {
    $resolved = CafeStoryCopy::resolve(['about_en' => 'Custom neighbourhood tale.'], 'en');

    expect($resolved)->toBe([
        'mode' => 'custom',
        'body' => 'Custom neighbourhood tale.',
    ]);
});
