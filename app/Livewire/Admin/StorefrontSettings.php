<?php

namespace App\Livewire\Admin;

use App\Business\BusinessContext;
use App\Livewire\Concerns\DispatchesToasts;
use App\Livewire\Concerns\ResolvesBusinessContext;
use App\Livewire\Concerns\ValidatesFieldsLive;
use App\Models\BusinessSetting;
use App\Support\Storefront\StorefrontConfig;
use App\Support\Validation\FieldValidationRules;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.pos')]
class StorefrontSettings extends Component
{
    use ResolvesBusinessContext;
    use DispatchesToasts;
    use ValidatesFieldsLive;

    public int $tenantId;

    public string $gaMeasurementId = '';

    public string $googleSiteVerification = '';

    public string $bingSiteVerification = '';

    public function mount(): void
    {
        $this->resolveBusiness();
        abort_unless($this->tenant->isCafeTheme(), 404);
        $this->authorize('reports.view');

        $this->tenantId = $this->tenant->id;
        BusinessContext::set($this->tenant);

        $config = StorefrontConfig::forBusinessSetting($this->tenant);
        $this->gaMeasurementId = $config['ga_measurement_id'];
        $this->googleSiteVerification = $config['google_site_verification'];
        $this->bingSiteVerification = $config['bing_site_verification'];
    }

    public function save(): void
    {
        $this->authorize('reports.view');

        $this->validate($this->liveValidationRules());

        $ga = StorefrontConfig::normalizeGaInput($this->gaMeasurementId);
        $google = trim($this->googleSiteVerification);
        $bing = trim($this->bingSiteVerification);

        $business = BusinessSetting::query()->findOrFail($this->tenantId);
        StorefrontConfig::mergeIntoBusinessSetting($business, [
            'ga_measurement_id' => $ga,
            'google_site_verification' => $google,
            'bing_site_verification' => $bing,
        ]);

        $this->toastSuccess(__('storefront.messages.saved'));
    }

    public function render(): View
    {
        $tenant = BusinessSetting::query()->findOrFail($this->tenantId);

        return view('livewire.admin.storefront-settings', [
            'tenant' => $tenant,
            'publicHomeUrl' => route('cafe.home'),
            'sitemapUrl' => route('storefront.sitemap'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function liveValidationRules(): array
    {
        return [
            'gaMeasurementId' => FieldValidationRules::ga4MeasurementId(),
            'googleSiteVerification' => FieldValidationRules::googleSiteVerification(),
            'bingSiteVerification' => FieldValidationRules::bingSiteVerification(),
        ];
    }
}
