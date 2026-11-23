<?php

namespace App\Livewire\Admin;

use App\Business\BusinessContext;
use App\Livewire\Concerns\DispatchesToasts;
use App\Livewire\Concerns\ResolvesBusinessContext;
use App\Models\BusinessSetting;
use App\Rules\SaudiVatNumber;
use App\Services\Business\BusinessSettingsService;
use App\Services\Invoicing\ZHeart\ZHeartClient;
use App\Support\Geo\GeoMapsUrl;
use App\Support\Invoicing\ZHeartCredentials;
use App\Support\TenantBusinessHours;
use App\Support\TenantBusinessProfile;
use App\Support\Upload\LogoUploadConstraints;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.pos')]
class BusinessSettings extends Component
{
    use DispatchesToasts;
    use ResolvesBusinessContext;
    use WithFileUploads;

    public int $tenantId;

    public string $section = 'brand';

    public string $displayNameEn = '';

    public string $displayNameAr = '';

    public string $displayNameBn = '';

    public string $taglineEn = '';

    public string $taglineAr = '';

    public string $storyEn = '';

    public string $storyAr = '';

    public string $brandColorKey = '';

    public $logoUpload = null;

    public bool $logoAwaitingSave = false;

    public ?string $currentLogo = null;

    public string $legalNameEn = '';

    public string $legalNameAr = '';

    public string $vatNumber = '';

    public string $crNumber = '';

    public string $addressEn = '';

    public string $addressAr = '';

    public string $naBuilding = '';

    public string $naStreet = '';

    public string $naDistrict = '';

    public string $naCity = '';

    public string $naPostal = '';

    public string $naAdditional = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $email = '';

    public string $website = '';

    public string $instagram = '';

    public string $socialX = '';

    public string $tiktok = '';

    public string $snapchat = '';

    public string $mapsUrl = '';

    public string $lat = '';

    public string $lng = '';

    /** @var list<array{key: string, closed: bool, open: string, close: string}> */
    public array $weekdays = [];

    public string $hoursNote = '';

    public bool $cafeWifi = true;

    public string $cafeSeatingEn = '';

    public string $cafeSeatingAr = '';

    public string $deliveryAppsRaw = '';

    public bool $invoicingIntegrationEnabled = false;

    public bool $zheartKeyConfigured = false;

    public string $zheartBaseUrl = '';

    public string $zheartApiKeyInput = '';

    public bool $zheartReplaceKeyOpen = false;

    public bool $zheartHasStoredKey = false;

    public ?string $zheartKeyLast4 = null;

    public bool $zheartUsingEnvFallback = false;

    public bool $zheartBaseUrlFromEnv = false;

    public ?string $zheartTestStatus = null;

    public string $zheartTestMessage = '';

    public ?int $zheartTestHttpStatus = null;

    public ?string $zheartTestReason = null;

    /** @var array<string, mixed>|null */
    public ?array $zheartTestCard = null;

    /** @var list<string> */
    public array $zheartTestWarnings = [];

    public ?string $zheartTestRequestId = null;

    /** @var list<string> */
    public array $tabsWithErrors = [];

    public function mount(BusinessSettingsService $service): void
    {
        $this->resolveBusiness();
        $this->authorize('business.manage');
        $this->tenantId = $this->tenant->id;
        BusinessContext::set($this->tenant);
        $tenant = $service->ensureHydrated($this->tenant);
        $this->hydrateFromTenant($tenant);
    }

    public function setSection(string $section): void
    {
        $allowed = ['brand', 'legal', 'contact', 'hours', 'branches', 'extras', 'invoicing'];
        if ($section === 'zheart') {
            $section = 'invoicing';
        }
        if (in_array($section, $allowed, true)) {
            $this->section = $section;
            $this->dispatch('business-tab-shown', section: $section);
        }
    }

    public function updatedLat(mixed $value): void
    {
        $this->lat = $this->normalizeCoordinate($value);
    }

    public function updatedLng(mixed $value): void
    {
        $this->lng = $this->normalizeCoordinate($value);
    }

    public function updatedLogoUpload(): void
    {
        $this->resetValidation('logoUpload');

        if ($this->logoUpload === null) {
            $this->logoAwaitingSave = false;

            return;
        }

        $ext = strtolower((string) $this->logoUpload->getClientOriginalExtension());
        if ($ext === 'svg') {
            $this->logoAwaitingSave = ! $this->getErrorBag()->has('logoUpload');

            return;
        }

        $path = $this->logoUpload->getRealPath();
        if (! is_string($path) || ! is_readable($path)) {
            $this->rejectLogoUpload(__('business.logo_upload.errors.processing_failed'));

            return;
        }

        $size = @getimagesize($path);
        if ($size === false) {
            $this->rejectLogoUpload(__('business.logo_upload.errors.invalid_type'));

            return;
        }

        [$width, $height] = $size;
        if ($width < LogoUploadConstraints::MIN_WIDTH || $height < LogoUploadConstraints::MIN_HEIGHT) {
            $this->rejectLogoUpload(__('business.logo_upload.errors.too_small', [
                'min' => LogoUploadConstraints::MIN_WIDTH,
            ]));
        }

        $this->logoAwaitingSave = $this->logoUpload !== null && ! $this->getErrorBag()->has('logoUpload');
    }

    public function validateLiveField(string $property): void
    {
        $rules = $this->liveRulesForProperty($property);
        if ($rules === null) {
            return;
        }

        $this->validateOnly($property, [$property => $rules]);
    }

    public function saveBrand(BusinessSettingsService $service): void
    {
        $this->authorize('business.manage');
        $this->section = 'brand';
        $this->resetErrorBag();
        $this->tabsWithErrors = [];
        $tenant = $this->tenant();

        $rules = [
            'displayNameEn' => 'required|string|max:160',
            'displayNameAr' => 'required|string|max:160',
            'displayNameBn' => 'nullable|string|max:160',
            'taglineEn' => 'nullable|string|max:240',
            'taglineAr' => 'nullable|string|max:240',
            'storyEn' => 'nullable|string|max:4000',
            'storyAr' => 'nullable|string|max:4000',
            'brandColorKey' => ['required', Rule::in(array_keys(TenantBusinessProfile::brandColorOptions($tenant)))],
            'logoUpload' => LogoUploadConstraints::componentRules(),
        ];
        try {
            $this->runSectionValidation($rules, 'brand', LogoUploadConstraints::validationMessages());
        } catch (ValidationException) {
            return;
        }

        try {
            $tenant = $service->saveBrand($tenant, [
                'display_name_en' => $this->displayNameEn,
                'display_name_ar' => $this->displayNameAr,
                'display_name_bn' => $this->displayNameBn,
                'tagline_en' => $this->taglineEn,
                'tagline_ar' => $this->taglineAr,
                'story_en' => $this->storyEn,
                'story_ar' => $this->storyAr,
                'brand_color_key' => $this->brandColorKey,
            ], auth('tenant')->user(), $this->logoUpload);
        } catch (\InvalidArgumentException $e) {
            $this->addError('logoUpload', $e->getMessage());

            return;
        }

        $hadNewLogo = $this->logoUpload !== null;
        $this->logoUpload = null;
        $this->logoAwaitingSave = false;
        $this->currentLogo = data_get($tenant->settings, 'theme.logo_url');

        if ($hadNewLogo && filled($this->currentLogo)) {
            $this->dispatch('business-logo-saved', publicUrl: asset($this->currentLogo));
            $this->toastSuccess(__('business.messages.saved_logo'));
        } else {
            $this->toastSuccess(__('business.messages.saved'));
        }
    }

    public function removeLogo(): void
    {
        $this->authorize('business.manage');
        $tenant = $this->tenant();
        $settings = $tenant->settings ?? [];
        data_set($settings, 'theme.logo_url', null);
        data_set($settings, 'business.brand.favicon_path', null);
        $tenant->forceFill(['settings' => $settings])->save();
        $this->currentLogo = null;
        $this->logoUpload = null;
        $this->logoAwaitingSave = false;
        $this->dispatch('business-logo-removed');
        $this->toastSuccess(__('business.messages.logo_removed'));
    }

    public function saveLegal(BusinessSettingsService $service): void
    {
        $this->authorize('business.manage');
        $this->section = 'legal';
        $this->resetErrorBag();
        $this->tabsWithErrors = [];
        try {
            $this->runSectionValidation([
                'legalNameEn' => 'required|string|max:200',
                'legalNameAr' => 'required|string|max:200',
                'vatNumber' => ['required', 'string', new SaudiVatNumber],
                'crNumber' => 'required|string|max:32',
                'addressEn' => 'required|string|max:500',
                'addressAr' => 'required|string|max:500',
                'naBuilding' => 'required|string|max:20',
                'naStreet' => 'required|string|max:120',
                'naDistrict' => 'required|string|max:120',
                'naCity' => 'required|string|max:80',
                'naPostal' => 'required|digits:5',
                'naAdditional' => 'nullable|string|max:20',
            ], 'legal');
        } catch (ValidationException) {
            return;
        }

        $service->saveLegal($this->tenant(), [
            'name_en' => $this->legalNameEn,
            'name_ar' => $this->legalNameAr,
            'vat_number' => $this->vatNumber,
            'cr_number' => $this->crNumber,
            'address_en' => $this->addressEn,
            'address_ar' => $this->addressAr,
            'national_address' => [
                'building' => $this->naBuilding,
                'street' => $this->naStreet,
                'district' => $this->naDistrict,
                'city' => $this->naCity,
                'postal_code' => $this->naPostal,
                'additional' => $this->naAdditional,
            ],
        ], auth('tenant')->user());

        $this->toastSuccess(__('business.messages.saved'));
    }

    public function saveContact(BusinessSettingsService $service): void
    {
        $this->authorize('business.manage');
        $this->section = 'contact';
        $this->resetErrorBag();
        $this->tabsWithErrors = [];
        $this->lat = $this->normalizeCoordinate($this->lat);
        $this->lng = $this->normalizeCoordinate($this->lng);
        try {
            $this->runSectionValidation([
                'phone' => 'nullable|string|max:40',
                'whatsapp' => 'nullable|string|max:40',
                'email' => ['nullable', 'email:rfc,filter', 'max:120'],
                'website' => 'nullable|url|max:200',
                'instagram' => 'nullable|string|max:80',
                'socialX' => 'nullable|string|max:80',
                'tiktok' => 'nullable|string|max:80',
                'snapchat' => 'nullable|string|max:80',
                'mapsUrl' => 'nullable|url|max:500',
                'lat' => 'nullable|numeric',
                'lng' => 'nullable|numeric',
            ], 'contact');
        } catch (ValidationException) {
            return;
        }

        $lat = $this->lat !== '' && is_numeric($this->lat) ? (string) round((float) $this->lat, 6) : '';
        $lng = $this->lng !== '' && is_numeric($this->lng) ? (string) round((float) $this->lng, 6) : '';
        $mapsUrl = trim($this->mapsUrl);
        if ($mapsUrl === '' && $lat !== '' && $lng !== '') {
            $mapsUrl = GeoMapsUrl::googleQuery($lat, $lng);
        }

        $service->saveContact($this->tenant(), [
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'website' => $this->website,
            'instagram' => $this->instagram,
            'x' => $this->socialX,
            'tiktok' => $this->tiktok,
            'snapchat' => $this->snapchat,
            'maps_url' => $mapsUrl,
            'lat' => $lat,
            'lng' => $lng,
        ], auth('tenant')->user());

        $this->lat = $lat;
        $this->lng = $lng;
        $this->mapsUrl = $mapsUrl;

        $this->toastSuccess(__('business.messages.saved'));
    }

    public function saveHours(BusinessSettingsService $service): void
    {
        $this->authorize('business.manage');
        $this->section = 'hours';
        $this->resetErrorBag();
        $this->tabsWithErrors = [];
        $this->weekdays = TenantBusinessProfile::normalizeWeekdays($this->weekdays);
        try {
            $this->runSectionValidation([
                'hoursNote' => 'nullable|string|max:500',
                'weekdays' => 'required|array',
                'weekdays.*.key' => 'required|string|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
                'weekdays.*.closed' => 'boolean',
                'weekdays.*.open' => 'nullable|date_format:H:i',
                'weekdays.*.close' => 'nullable|date_format:H:i',
            ], 'hours');
        } catch (ValidationException) {
            return;
        }

        $service->saveHours($this->tenant(), [
            'weekdays' => $this->weekdays,
            'note' => $this->hoursNote,
        ], auth('tenant')->user());

        $this->toastSuccess(__('business.messages.saved'));
    }

    public function saveExtras(BusinessSettingsService $service): void
    {
        $this->authorize('business.manage');
        $this->section = 'extras';
        $this->resetErrorBag();
        $this->tabsWithErrors = [];
        $tenant = $this->tenant();
        try {
            $this->runSectionValidation([
                'cafeSeatingEn' => 'nullable|string|max:240',
                'cafeSeatingAr' => 'nullable|string|max:240',
                'deliveryAppsRaw' => 'nullable|string|max:4000',
            ], 'extras');
        } catch (ValidationException) {
            return;
        }
        $lines = preg_split('/\r\n|\r|\n/', trim($this->deliveryAppsRaw)) ?: [];
        $apps = array_values(array_filter(array_map('trim', $lines)));
        $service->saveThemeExtras($tenant, [
            'wifi_available' => $this->cafeWifi,
            'seating_note_en' => $this->cafeSeatingEn,
            'seating_note_ar' => $this->cafeSeatingAr,
            'delivery_apps' => $apps,
        ], auth('tenant')->user());

        $this->toastSuccess(__('business.messages.saved'));
    }

    public function saveInvoicing(BusinessSettingsService $service): void
    {
        $this->authorize('business.manage');
        $this->section = 'invoicing';
        $this->resetErrorBag();
        $this->tabsWithErrors = [];

        $service->saveInvoicing($this->tenant(), [
            'integration_enabled' => $this->invoicingIntegrationEnabled,
        ], auth('tenant')->user());

        $this->toastSuccess(__('business.messages.saved'));
    }

    public function beginZheartReplaceKey(): void
    {
        $this->assertOwner();
        $this->zheartReplaceKeyOpen = true;
        $this->zheartApiKeyInput = '';
    }

    public function updatedZheartBaseUrl(): void
    {
        $stored = trim((string) data_get($this->tenant()->settings, 'zheart.base_url', ''));
        if ($stored !== '') {
            $this->zheartBaseUrlFromEnv = false;

            return;
        }

        $env = rtrim((string) config('zheart.base_url', ''), '/');
        $current = rtrim(trim($this->zheartBaseUrl), '/');
        $this->zheartBaseUrlFromEnv = $env !== '' && $current === $env;
    }

    public function saveZheart(BusinessSettingsService $service): void
    {
        $this->assertOwner();
        $this->section = 'invoicing';
        $this->tabsWithErrors = [];

        $rules = [
            'zheartBaseUrl' => ['nullable', 'string', 'max:255', 'regex:/^https?:\\/\\/.+/i'],
        ];
        if (trim($this->zheartApiKeyInput) !== '') {
            $rules['zheartApiKeyInput'] = ['required', 'string', 'min:16', 'max:512'];
        }

        try {
            $this->runSectionValidation($rules, 'invoicing');
        } catch (ValidationException) {
            return;
        }

        try {
            $tenant = $service->saveZheart($this->tenant(), [
                'base_url' => $this->zheartBaseUrl,
                'api_key' => trim($this->zheartApiKeyInput) !== '' ? trim($this->zheartApiKeyInput) : null,
            ], auth('tenant')->user());
        } catch (Throwable $e) {
            Log::warning('business.settings.zheart.save_failed', [
                'message' => $e->getMessage(),
            ]);
            $this->toastError(__('business.validation.section_failed', [
                'section' => __('business.sections.invoicing'),
            ]));

            return;
        }

        $this->zheartApiKeyInput = '';
        $this->zheartReplaceKeyOpen = false;
        $this->toastSuccess(__('business.messages.saved'));
        $this->hydrateFromTenant($tenant);
    }

    public function removeZheartKey(BusinessSettingsService $service): void
    {
        $this->assertOwner();
        $this->section = 'invoicing';

        try {
            $tenant = $service->saveZheart($this->tenant(), [
                'remove_api_key' => true,
            ], auth('tenant')->user());
        } catch (Throwable $e) {
            Log::warning('business.settings.zheart.remove_failed', [
                'message' => $e->getMessage(),
            ]);
            $this->toastError(__('business.validation.section_failed', [
                'section' => __('business.sections.invoicing'),
            ]));

            return;
        }

        $this->zheartApiKeyInput = '';
        $this->zheartReplaceKeyOpen = false;
        $this->toastSuccess(__('business.zheart.key_removed'));
        $this->hydrateFromTenant($tenant);
    }

    public function testZheartConnection(ZHeartClient $client): void
    {
        $this->assertOwner();
        $this->section = 'invoicing';

        $tenant = $this->tenant();
        $base = trim($this->zheartBaseUrl);
        if ($base === '') {
            $base = ZHeartCredentials::baseUrl($tenant);
        } else {
            $base = rtrim($base, '/');
        }

        $key = trim($this->zheartApiKeyInput);
        if ($key === '') {
            $key = ZHeartCredentials::apiKey($tenant);
        }

        $result = $client->testConnection($base, $key, $tenant);
        $this->zheartTestStatus = match ($result['severity'] ?? 'error') {
            'ok' => 'ok',
            'warn' => 'warn',
            default => 'error',
        };
        $this->zheartTestMessage = $result['message'];
        $this->zheartTestHttpStatus = $result['http_status'] ?? null;
        $this->zheartTestReason = $result['reason'] ?? null;
        $this->zheartTestCard = is_array($result['card'] ?? null) ? $result['card'] : null;
        $this->zheartTestWarnings = is_array($result['warnings'] ?? null) ? $result['warnings'] : [];
        $this->zheartTestRequestId = is_string($result['request_id'] ?? null) && $result['request_id'] !== ''
            ? $result['request_id']
            : null;

        if ($result['ok']) {
            $this->toastSuccess($result['message']);
        } elseif (($result['severity'] ?? 'error') === 'warn') {
            $this->toastWarning($result['message']);
        } else {
            $this->toastError($result['message']);
        }
    }

    public function render(): View
    {
        $tenant = $this->tenant();

        return view('livewire.admin.business-settings', [
            'tenant' => $tenant,
            'colorOptions' => TenantBusinessProfile::brandColorOptions($tenant),
            'teamUrl' => route('admin.team'),
            'isOwner' => $this->isOwner(),
        ]);
    }

    private function tenant(): BusinessSetting
    {
        return BusinessSetting::query()->findOrFail($this->tenantId);
    }

    protected function validationAttributes(): array
    {
        return [
            'logoUpload' => __('business.brand.logo'),
        ];
    }

    private function rejectLogoUpload(string $message): void
    {
        Log::info('business.logo_upload.rejected', ['message' => $message]);
        $this->addError('logoUpload', $message);
        $this->logoUpload = null;
        $this->logoAwaitingSave = false;
    }

    private function normalizeCoordinate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }
        if (is_array($value)) {
            return '';
        }
        if (is_numeric($value)) {
            return (string) round((float) $value, 6);
        }

        return TenantBusinessProfile::scalarString($value);
    }

    private function hydrateFromTenant(BusinessSetting $tenant): void
    {
        $settings = $tenant->settings ?? [];
        $brand = (array) data_get($settings, 'business.brand', []);
        $legal = (array) data_get($settings, 'business.legal', data_get($settings, 'legal_entity', []));
        $contact = (array) data_get($settings, 'business.contact', []);
        $hours = (array) data_get($settings, 'business.hours', []);
        $extras = (array) data_get($settings, 'business.theme_extras', []);

        $this->displayNameEn = (string) ($brand['display_name_en'] ?? $legal['name_en'] ?? $tenant->name);
        $this->displayNameAr = (string) ($brand['display_name_ar'] ?? $legal['name_ar'] ?? '');
        $this->displayNameBn = (string) ($brand['display_name_bn'] ?? '');
        $this->taglineEn = (string) ($brand['tagline_en'] ?? '');
        $this->taglineAr = (string) ($brand['tagline_ar'] ?? '');
        $cafeContent = (array) data_get($settings, 'content.cafe', []);
        $this->storyEn = (string) ($cafeContent['about_en'] ?? '');
        $this->storyAr = (string) ($cafeContent['about_ar'] ?? '');
        $this->brandColorKey = TenantBusinessProfile::normalizeBrandColorKey($tenant, $brand['brand_color_key'] ?? null);
        $this->currentLogo = data_get($settings, 'theme.logo_url');

        $this->legalNameEn = (string) ($legal['name_en'] ?? '');
        $this->legalNameAr = (string) ($legal['name_ar'] ?? '');
        $this->vatNumber = (string) ($legal['vat_number'] ?? '');
        $this->crNumber = (string) ($legal['cr_number'] ?? '');
        $this->addressEn = (string) ($legal['address_en'] ?? $legal['address'] ?? '');
        $this->addressAr = (string) ($legal['address_ar'] ?? '');
        $na = (array) ($legal['national_address'] ?? []);
        $this->naBuilding = (string) ($na['building'] ?? '');
        $this->naStreet = (string) ($na['street'] ?? '');
        $this->naDistrict = (string) ($na['district'] ?? '');
        $this->naCity = (string) ($na['city'] ?? '');
        $this->naPostal = (string) ($na['postal_code'] ?? '');
        $this->naAdditional = (string) ($na['additional'] ?? '');

        $this->phone = (string) ($contact['phone'] ?? '');
        $this->whatsapp = (string) ($contact['whatsapp'] ?? '');
        $this->email = (string) ($contact['email'] ?? '');
        $this->website = (string) ($contact['website'] ?? '');
        $this->instagram = (string) ($contact['instagram'] ?? '');
        $this->socialX = (string) ($contact['x'] ?? '');
        $this->tiktok = (string) ($contact['tiktok'] ?? '');
        $this->snapchat = (string) ($contact['snapchat'] ?? '');
        $this->mapsUrl = (string) ($contact['maps_url'] ?? '');
        $this->lat = (string) ($contact['lat'] ?? '');
        $this->lng = (string) ($contact['lng'] ?? '');

        $storedWeekdays = $hours['weekdays'] ?? null;
        $weekdays = TenantBusinessProfile::normalizeWeekdays($storedWeekdays);
        if ($storedWeekdays === null || $storedWeekdays === []) {
            $inferred = TenantBusinessHours::weekdaysFromLegacyRows(
                (array) (TenantBusinessProfile::contentSlice($tenant)['hours'] ?? []),
            );
            if ($inferred !== null) {
                $weekdays = TenantBusinessProfile::normalizeWeekdays($inferred);
            }
        }
        $this->weekdays = $weekdays;
        $this->hoursNote = TenantBusinessProfile::scalarString($hours['note'] ?? '');

        $this->cafeWifi = (bool) ($extras['wifi_available'] ?? true);
        $this->cafeSeatingEn = (string) ($extras['seating_note_en'] ?? '');
        $this->cafeSeatingAr = (string) ($extras['seating_note_ar'] ?? '');
        $apps = (array) ($extras['delivery_apps'] ?? []);
        $this->deliveryAppsRaw = implode("\n", $apps);

        $this->zheartKeyConfigured = filled(config('zheart.api_key')) || ZHeartCredentials::isConfigured($tenant);
        $storedIntegration = data_get($settings, 'invoicing.integration_enabled');
        if ($storedIntegration === null && $this->zheartKeyConfigured) {
            $this->invoicingIntegrationEnabled = true;
        } else {
            $this->invoicingIntegrationEnabled = (bool) $storedIntegration;
        }

        $this->hydrateZheartCredentials($tenant);
    }

    private function hydrateZheartCredentials(BusinessSetting $tenant): void
    {
        $storedBaseUrl = trim((string) data_get($tenant->settings, 'zheart.base_url', ''));
        $envBaseUrl = rtrim((string) config('zheart.base_url', ''), '/');
        if ($storedBaseUrl !== '') {
            $this->zheartBaseUrl = $storedBaseUrl;
            $this->zheartBaseUrlFromEnv = false;
        } elseif ($envBaseUrl !== '') {
            $this->zheartBaseUrl = $envBaseUrl;
            $this->zheartBaseUrlFromEnv = true;
        } else {
            $this->zheartBaseUrl = '';
            $this->zheartBaseUrlFromEnv = false;
        }
        $this->zheartHasStoredKey = ZHeartCredentials::hasStoredApiKey($tenant);
        $this->zheartKeyLast4 = ZHeartCredentials::last4ForDisplay($tenant);
        $this->zheartUsingEnvFallback = ! $this->zheartHasStoredKey
            && is_string(config('zheart.api_key'))
            && config('zheart.api_key') !== '';
        $this->zheartApiKeyInput = '';
        $this->zheartReplaceKeyOpen = false;
    }

    private function isOwner(): bool
    {
        return auth('tenant')->user()?->hasRole('owner') ?? false;
    }

    private function assertOwner(): void
    {
        abort_unless($this->isOwner(), 403);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     */
    private function runSectionValidation(array $rules, string $section, array $messages = []): void
    {
        try {
            Validator::make($this->only(array_keys($rules)), $rules, $messages)->validate();
        } catch (ValidationException $e) {
            $this->section = $section;
            $this->tabsWithErrors = [$section];
            $this->setErrorBag($e->validator->errors());
            $this->toastWarning(__('business.validation.section_failed', [
                'section' => __('business.sections.'.$section),
            ]), dedupe: false);
            throw $e;
        }
    }

    private function liveRulesForProperty(string $property): ?string
    {
        if ($this->section !== 'contact') {
            return null;
        }

        return match ($property) {
            'email' => 'nullable|email:rfc,filter|max:120',
            'website' => 'nullable|url|max:200',
            'mapsUrl' => 'nullable|url|max:500',
            default => null,
        };
    }
}
