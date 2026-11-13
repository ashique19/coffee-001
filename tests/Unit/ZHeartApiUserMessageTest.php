<?php

use App\Support\Invoicing\ZHeartApiUserMessage;

it('maps 401 with api message', function () {
    $msg = ZHeartApiUserMessage::fromResponse(401, [
        'message' => 'Invalid API key.',
        'data' => null,
    ], 'req-401');

    expect($msg)->toContain('Invalid API key.')
        ->and($msg)->toContain('req-401');
});

it('maps 403 client_suspended', function () {
    $msg = ZHeartApiUserMessage::fromResponse(403, [
        'message' => 'Account suspended.',
        'data' => ['error' => 'client_suspended'],
    ], 'req-403');

    expect($msg)->toContain('suspended')
        ->and($msg)->toContain('Account suspended.');
});

it('maps 403 ip_not_allowed', function () {
    $msg = ZHeartApiUserMessage::fromResponse(403, [
        'message' => 'IP blocked.',
        'data' => ['error' => 'ip_not_allowed'],
    ], 'req-ip');

    expect($msg)->toContain('allowlist')
        ->and($msg)->toContain('IP blocked.');
});

it('maps 422 production csid', function () {
    $msg = ZHeartApiUserMessage::fromResponse(422, [
        'message' => 'Production CSID required.',
        'data' => ['error' => 'production_csid_required'],
    ], 'req-csid');

    expect($msg)->toContain('production CSID')
        ->and($msg)->toContain('Production CSID required.');
});

it('maps 422 validation errors', function () {
    $msg = ZHeartApiUserMessage::fromResponse(422, [
        'message' => 'buyer.vat_number is invalid',
        'data' => ['error' => 'validation'],
    ], 'req-val');

    expect($msg)->toContain('buyer.vat_number');
});
