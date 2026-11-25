<?php

use App\Services\Invoicing\ZHeart\ZHeartApiException;
use App\Services\Invoicing\ZHeart\ZHeartClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'zheart.base_url' => 'https://z-heart.test',
        'zheart.api_key' => 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789',
        'zheart.retry_times' => 0,
    ]);
});

it('calls sign and maps success artifacts', function () {
    Http::fake([
        'https://z-heart.test/api/v1/invoices/sign' => Http::response([
            'status' => 'ok',
            'message' => 'Invoice signed.',
            'data' => [
                'signed_xml' => base64_encode('<Invoice/>'),
                'invoice_hash' => 'aGFzaA==',
                'qr' => 'cXI=',
                'uuid' => '550e8400-e29b-41d4-a716-446655440099',
                'icv' => 3,
                'pih' => 'cGlo',
            ],
            'request_id' => 'req-sign-1',
        ], 200, ['X-Request-Id' => 'req-sign-1']),
    ]);

    $client = app(ZHeartClient::class);
    $result = $client->sign(minimalZHeartInvoicePayload());

    expect($result['data']['icv'])->toBe(3)
        ->and($result['request_id'])->toBe('req-sign-1');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789')
            && ! str_contains(json_encode($request->data()), 'zh_test1234');
    });
});

it('maps 401 to a safe user-facing exception', function () {
    Http::fake([
        'https://z-heart.test/api/v1/invoices/sign' => Http::response([
            'status' => 'error',
            'message' => 'Invalid API key.',
            'data' => null,
            'request_id' => 'req-401',
        ], 401),
    ]);

    try {
        app(ZHeartClient::class)->sign(minimalZHeartInvoicePayload());
        expect(false)->toBeTrue();
    } catch (ZHeartApiException $e) {
        expect($e->getMessage())->toContain('Invalid API key.');
    }
});

it('maps 403 client_not_active from data.error', function () {
    Http::fake([
        'https://z-heart.test/api/v1/invoices/sign' => Http::response([
            'status' => 'error',
            'message' => 'Client inactive.',
            'data' => ['error' => 'client_not_active'],
        ], 403),
    ]);

    try {
        app(ZHeartClient::class)->sign(minimalZHeartInvoicePayload());
        expect(false)->toBeTrue();
    } catch (ZHeartApiException $e) {
        expect($e->getMessage())->toContain('not active');
    }
});

it('does not retry report on 500', function () {
    Http::fake([
        'https://z-heart.test/api/v1/invoices/report' => Http::response([
            'status' => 'error',
            'message' => 'Server blew up',
        ], 500),
    ]);

    expect(fn () => app(ZHeartClient::class)->report(minimalZHeartInvoicePayload()))
        ->toThrow(ZHeartApiException::class);

    Http::assertSentCount(1);
});

it('retries sign on 500 when configured', function () {
    config(['zheart.retry_times' => 2]);

    $calls = 0;
    Http::fake(function () use (&$calls) {
        $calls++;

        return Http::response(['status' => 'error', 'message' => 'fail'], 500);
    });

    try {
        app(ZHeartClient::class)->sign(minimalZHeartInvoicePayload());
    } catch (ZHeartApiException) {
        // expected
    }

    expect($calls)->toBe(3);
});

it('health checks the remote service', function () {
    Http::fake([
        'https://z-heart.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
    ]);

    expect(app(ZHeartClient::class)->health())->toBeTrue();
});

function minimalZHeartInvoicePayload(string $type = 'simplified'): array
{
    return [
        'uuid' => '550e8400-e29b-41d4-a716-446655440001',
        'issue_date' => '2026-01-15',
        'issue_time' => '12:00:00',
        'currency' => 'SAR',
        'type' => $type,
        'lines' => [['name' => 'Item', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 15]],
        'totals' => ['tax_exclusive' => 100, 'tax' => 15, 'tax_inclusive' => 115],
    ];
}

it('reports a z-heart 500 as an internal error with the request id, never as an auth failure', function () {
    app()->setLocale('en');
    Http::fake([
        'https://z-heart.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://z-heart.test/api/v1/auth' => Http::response(
            ['status' => 'error', 'message' => 'Unexpected error.', 'data' => null, 'request_id' => 'body-id'],
            500,
            ['X-Request-Id' => '834b9521-ed79-4604-8ad9-5b46b510287a'],
        ),
    ]);

    $result = app(ZHeartClient::class)->testConnection();

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toBe('server_error')
        ->and($result['http_status'])->toBe(500)
        ->and($result['request_id'])->toBe('834b9521-ed79-4604-8ad9-5b46b510287a')
        ->and($result['message'])->toBe('Z-Heart had an internal error. Request ID: 834b9521-ed79-4604-8ad9-5b46b510287a. Please try again or contact support.')
        ->and($result['message'])->not->toContain(__('business.zheart.test_reason_auth_failed'));
});

it('handles a non-json 5xx page from z-heart without a request id', function () {
    app()->setLocale('en');
    Http::fake([
        'https://z-heart.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://z-heart.test/api/v1/auth' => Http::response('<html><body>502 Bad Gateway</body></html>', 502, ['Content-Type' => 'text/html']),
    ]);

    $result = app(ZHeartClient::class)->testConnection();

    expect($result['reason'])->toBe('server_error')
        ->and($result['request_id'])->toBeNull()
        ->and($result['message'])->toBe('Z-Heart had an internal error. Please try again or contact support.');
});

it('reports a 200 that is not the expected json as an unreadable reply', function () {
    app()->setLocale('en');
    Http::fake([
        'https://z-heart.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://z-heart.test/api/v1/auth' => Http::response('<html>login</html>', 200, ['Content-Type' => 'text/html', 'X-Request-Id' => 'rid-1']),
    ]);

    $result = app(ZHeartClient::class)->testConnection();

    expect($result['reason'])->toBe('bad_response')
        ->and($result['message'])->toContain('HTTP 200')
        ->and($result['message'])->toContain('rid-1');
});

it('maps z-heart 401 and 403 messages to specific plain reasons', function (int $status, array $body, string $reason) {
    Http::fake([
        'https://z-heart.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://z-heart.test/api/v1/auth' => Http::response($body, $status),
    ]);

    expect(app(ZHeartClient::class)->testConnection()['reason'])->toBe($reason);
})->with([
    'revoked' => [401, ['status' => 'error', 'message' => 'API key has been revoked.'], 'key_revoked'],
    'expired' => [401, ['status' => 'error', 'message' => 'API key has expired.'], 'key_expired'],
    'invalid' => [401, ['status' => 'error', 'message' => 'Invalid API key.'], 'bad_key'],
    'ip' => [403, ['status' => 'error', 'message' => 'ip_not_allowed', 'data' => ['error' => 'ip_not_allowed']], 'ip_not_allowed'],
    'suspended' => [403, ['status' => 'error', 'message' => 'client_suspended', 'data' => ['error' => 'client_suspended']], 'client_suspended'],
    'not active' => [403, ['status' => 'error', 'message' => 'Client is not active.', 'data' => ['error' => 'client_not_active']], 'client_not_active'],
]);

it('has the new connection-test messages in all coffee languages', function () {
    foreach (['test_reason_server_error', 'test_reason_server_error_no_id', 'test_reason_bad_response', 'test_reason_unexpected_status', 'test_reason_key_revoked', 'test_reason_key_expired', 'test_reason_client_suspended', 'test_reason_client_not_active', 'test_request_id'] as $key) {
        $en = __('business.zheart.'.$key, [], 'en');
        expect($en)->not->toBe('business.zheart.'.$key);
        foreach (['bn', 'ar', 'ur', 'hi'] as $locale) {
            expect(__('business.zheart.'.$key, [], $locale))->not->toBe($en, "{$locale}: {$key} untranslated");
        }
    }
});
