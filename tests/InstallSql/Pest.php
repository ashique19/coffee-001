<?php

use App\Business\BusinessContext;
use Tests\Support\FakesZHeartSigning;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(FakesZHeartSigning::class)
    ->beforeEach(function () {
        $this->fakeZHeartSigning();
    })
    ->afterEach(function () {
        BusinessContext::clear();
    })
    ->in('InstallSql');
