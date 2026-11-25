<?php

use App\Livewire\Admin\BusinessSettings;
use App\Models\BusinessSetting;
use App\Models\StaffUser;
use App\Support\Invoicing\ZHeartCredentials;
use Database\Seeders\DemoFancyCoffeeSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    (new DemoFancyCoffeeSeeder)->run();
    $this->business = BusinessSetting::singleton();
    $this->owner = StaffUser::query()->where('email', 'owner@fancy-coffee.test')->firstOrFail();
    config([
        'zheart.base_url' => '',
        'zheart.api_key' => '',
    ]);
});

it('shows the z-heart connection block on the invoicing tab for owners only', function () {
    $html = Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->assertSeeHtml('data-testid="business-zheart-form"')
        ->assertSee(__('business.zheart.base_url'), false)
        ->assertSee(__('business.zheart.api_key'), false)
        ->assertSee(__('business.zheart.test_connection'), false)
        ->assertSee(__('business.zheart.lead'), false)
        ->assertSee(__('business.invoicing.integration_hint'), false)
        ->assertDontSee('DB values override', false)
        ->assertDontSee('cryptographically signed locally', false)
        ->assertDontSee('signed locally', false)
        ->html();

    expect($html)->not->toContain(__('business.sections.zheart'))
        ->and(substr_count($html, 'wire:click="setSection(\'invoicing\')"'))->toBe(1);

    $manager = StaffUser::query()->firstOrCreate(
        ['email' => 'manager-zheart-tab@example.test'],
        [
            'name' => 'Manager',
            'password' => bcrypt('TestPassword!2026'),
            'is_active' => true,
        ],
    );
    $manager->syncRoles(['manager']);

    Livewire::actingAs($manager, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->assertDontSeeHtml('data-testid="business-zheart-form"');
});

it('prefills the base url from env when the database is empty', function () {
    config([
        'zheart.base_url' => 'https://env-zheart.example.test',
        'zheart.api_key' => 'zh_envonly.ABCDEFGH',
    ]);

    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->assertSet('zheartBaseUrl', 'https://env-zheart.example.test')
        ->assertSet('zheartBaseUrlFromEnv', true)
        ->assertSeeHtml('data-testid="business-zheart-base-env-hint"')
        ->assertSee(__('business.zheart.key_status_env', ['last4' => 'EFGH']), false);
});

it('saves an encrypted api key and never echoes the plaintext value', function () {
    $key = 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789';

    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->set('zheartBaseUrl', 'https://z-heart.example.test')
        ->set('zheartApiKeyInput', $key)
        ->call('saveZheart')
        ->assertHasNoErrors()
        ->assertSet('zheartApiKeyInput', '')
        ->assertDontSee($key, false);

    $this->business->refresh();
    $encoded = json_encode($this->business->settings);
    $audit = DB::table('business_settings_audit_logs')->orderByDesc('id')->first();

    expect(ZHeartCredentials::baseUrl($this->business))->toBe('https://z-heart.example.test')
        ->and(ZHeartCredentials::apiKey($this->business))->toBe($key)
        ->and(ZHeartCredentials::hasStoredApiKey($this->business))->toBeTrue()
        ->and(ZHeartCredentials::storedKeyLast4($this->business))->toBe('6789')
        ->and($encoded)->not->toContain($key)
        ->and(data_get($this->business->settings, 'zheart.api_key_encrypted'))->not->toBe($key)
        ->and(Crypt::decryptString((string) data_get($this->business->settings, 'zheart.api_key_encrypted')))->toBe($key)
        ->and($audit)->not->toBeNull()
        ->and((string) $audit->snapshot)->toContain('[redacted]')
        ->and((string) $audit->snapshot)->not->toContain($key);

    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->assertSeeHtml('data-testid="business-zheart-key-status"')
        ->assertSee(__('business.zheart.key_status_saved', ['last4' => '6789']), false)
        ->assertDontSee($key, false)
        ->assertDontSee(data_get($this->business->settings, 'zheart.api_key_encrypted'), false);
});

it('tests a successful connection and shows the request id without leaking the key', function () {
    Http::fake([
        'https://z-heart.example.test/api/v1/health' => Http::response(['status' => 'ok'], 200, [
            'X-Request-Id' => 'req-health-ignored',
        ]),
        'https://z-heart.example.test/api/v1/auth' => Http::response([
            'status' => 'ok',
            'request_id' => 'req-ok-c68571c9',
            'client' => [
                'name' => 'Test Client',
                'vat_number' => '300012345600003',
                'cr_number' => '1010123456',
                'environment' => 'sandbox',
                'active' => true,
                'onboarded' => true,
            ],
            'api_key' => ['label' => 'Sandbox key', 'last4' => '6789'],
            'ip_allowlist_enabled' => true,
        ], 200),
    ]);

    $key = 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789';

    $html = Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->set('zheartBaseUrl', 'https://z-heart.example.test')
        ->set('zheartApiKeyInput', $key)
        ->call('testZheartConnection')
        ->assertSet('zheartTestStatus', 'ok')
        ->assertSet('zheartTestHttpStatus', 200)
        ->assertSet('zheartTestRequestId', 'req-ok-c68571c9')
        ->assertSeeHtml('data-testid="business-zheart-test-card"')
        ->assertSeeHtml('data-testid="business-zheart-test-request-id"')
        ->assertSee('req-ok-c68571c9', false)
        ->assertSee('Test Client', false)
        ->assertDontSee($key, false)
        ->html();

    expect($html)->not->toContain($key);

    Http::assertSent(function ($request) use ($key) {
        return $request->hasHeader('Authorization', 'Bearer '.$key)
            && ! str_contains((string) $request->url(), $key)
            && ! str_contains((string) $request->body(), $key);
    });
});

it('shows a failed connection result with the request id', function () {
    Http::fake([
        'https://z-heart.example.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://z-heart.example.test/api/v1/auth' => Http::response([
            'status' => 'error',
            'request_id' => 'req-fail-401',
        ], 401, ['X-Request-Id' => 'req-fail-401']),
    ]);

    $key = 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789';

    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->set('zheartBaseUrl', 'https://z-heart.example.test')
        ->set('zheartApiKeyInput', $key)
        ->call('testZheartConnection')
        ->assertSet('zheartTestStatus', 'error')
        ->assertSet('zheartTestHttpStatus', 401)
        ->assertSet('zheartTestRequestId', 'req-fail-401')
        ->assertSee(__('business.zheart.test_reason_bad_key'), false)
        ->assertSee('req-fail-401', false)
        ->assertDontSee($key, false);
});

it('shows a vat mismatch warning when the z-heart client vat differs from business settings', function () {
    $settings = $this->business->settings ?? BusinessSetting::defaultSettings();
    data_set($settings, 'legal_entity.vat_number', '300012345600003');
    $this->business->forceFill(['settings' => $settings])->save();

    Http::fake([
        'https://z-heart.example.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://z-heart.example.test/api/v1/auth' => Http::response([
            'status' => 'ok',
            'request_id' => 'req-vat-mismatch',
            'client' => [
                'name' => 'Test Client',
                'vat_number' => '300099999900003',
                'environment' => 'sandbox',
            ],
        ], 200),
    ]);

    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->set('zheartBaseUrl', 'https://z-heart.example.test')
        ->set('zheartApiKeyInput', 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789')
        ->call('testZheartConnection')
        ->assertSeeHtml('data-testid="business-zheart-test-warnings"')
        ->assertSee('300099999900003', false)
        ->assertSet('zheartTestRequestId', 'req-vat-mismatch');
});

it('maps a legacy zheart section to the invoicing tab', function () {
    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->call('setSection', 'zheart')
        ->assertSet('section', 'invoicing');
});

it('forbids managers from testing or saving z-heart credentials', function () {
    $manager = StaffUser::query()->firstOrCreate(
        ['email' => 'manager-zheart-actions@example.test'],
        [
            'name' => 'Manager',
            'password' => bcrypt('TestPassword!2026'),
            'is_active' => true,
        ],
    );
    $manager->syncRoles(['manager']);

    Livewire::actingAs($manager, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->assertDontSeeHtml('data-testid="business-zheart-form"')
        ->call('testZheartConnection')
        ->assertForbidden();
});

it('shows amber not-ready with the plain issue list when z-heart says the client cannot sign yet', function () {
    Http::fake([
        'https://z-heart.example.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://z-heart.example.test/api/v1/auth' => Http::response([
            'status' => 'ok',
            'ready' => false,
            'issues' => [
                ['field' => 'client_profile.legal_name_ar', 'message' => 'The legal name in Arabic is missing.'],
                ['field' => 'client_profile.address_building', 'message' => 'The building number is not valid. It must be exactly 4 digits.'],
                ['field' => 'zatca.onboarding', 'message' => 'This business is not onboarded with ZATCA for the Simulation environment yet.'],
            ],
            'client' => ['name' => 'Fancy Coffee', 'environment' => 'simulation', 'active' => true, 'onboarded' => false],
        ], 200),
    ]);

    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->set('zheartBaseUrl', 'https://z-heart.example.test')
        ->set('zheartApiKeyInput', 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789')
        ->call('testZheartConnection')
        ->assertSet('zheartTestStatus', 'warn')
        ->assertSet('zheartTestReason', 'not_ready')
        ->assertSeeHtml('data-testid="business-zheart-test-issues"')
        ->assertSee(__('business.zheart.test_not_ready'), false)
        ->assertSee(__('business.zheart.test_not_ready_hint'), false)
        ->assertSee(__('business.zheart.issues.client_profile.legal_name_ar'), false)
        ->assertSee(__('business.zheart.issues.client_profile.address_building'), false)
        ->assertSee(__('business.zheart.issues.zatca.onboarding', ['environment' => 'Simulation']), false)
        ->assertDontSee(__('business.zheart.test_connected_heading'), false)
        ->assertSeeHtml('data-testid="business-zheart-test-card"');
});

it('stays green when z-heart reports ready, and when an older z-heart sends no readiness block', function (array $extra) {
    Http::fake([
        'https://z-heart.example.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://z-heart.example.test/api/v1/auth' => Http::response(array_merge([
            'status' => 'ok',
            'client' => ['name' => 'Fancy Coffee', 'environment' => 'simulation', 'active' => true, 'onboarded' => true],
        ], $extra), 200),
    ]);

    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->set('zheartBaseUrl', 'https://z-heart.example.test')
        ->set('zheartApiKeyInput', 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789')
        ->call('testZheartConnection')
        ->assertSet('zheartTestStatus', 'ok')
        ->assertSet('zheartTestIssues', [])
        ->assertSee(__('business.zheart.test_connected_heading'), false)
        ->assertDontSeeHtml('data-testid="business-zheart-test-issues"');
})->with([
    'ready' => [['ready' => true, 'issues' => []]],
    'older z-heart' => [[]],
]);

it('shows a z-heart 500 as an internal error with the request id in arabic', function () {
    app()->setLocale('ar');
    Http::fake([
        'https://z-heart.example.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://z-heart.example.test/api/v1/auth' => Http::response(
            ['status' => 'error', 'message' => 'Unexpected error.', 'data' => null],
            500,
            ['X-Request-Id' => '834b9521-ed79-4604-8ad9-5b46b510287a'],
        ),
    ]);

    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'invoicing')
        ->set('zheartBaseUrl', 'https://z-heart.example.test')
        ->set('zheartApiKeyInput', 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789')
        ->call('testZheartConnection')
        ->assertSet('zheartTestStatus', 'error')
        ->assertSet('zheartTestReason', 'server_error')
        ->assertSet('zheartTestRequestId', '834b9521-ed79-4604-8ad9-5b46b510287a')
        ->assertSee(__('business.zheart.test_reason_server_error', ['id' => '834b9521-ed79-4604-8ad9-5b46b510287a']), false)
        ->assertSeeHtml('data-testid="business-zheart-test-request-id"')
        ->assertDontSee(__('business.zheart.test_reason_auth_failed'), false);
});
