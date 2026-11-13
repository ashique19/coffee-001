<?php

use Tests\Support\InstallSqlSchemaParity;

it('matches install.sql column sets to the dumped migration schema for every table', function () {
    $mismatches = InstallSqlSchemaParity::mismatchDescriptions();
    expect($mismatches)->toBeEmpty('Schema drift: '.implode('; ', $mismatches));
});

it('documents install.sql build script parity for ingredients without legacy name column', function () {
    $cols = InstallSqlSchemaParity::installColumnsByTable()['ingredients'] ?? [];
    expect($cols)->toContain('name_en')
        ->and($cols)->not->toContain('name');
});

it('orders ingredients by name_en only when legacy name column is absent', function () {
    expect(\App\Support\Inventory\IngredientInventorySchema::orderByColumns())->toBe(['name_en']);
});
