@php
    $steps = [
        1 => __('zatca_admin.wizard.business'),
        2 => __('zatca_admin.wizard.egs'),
        3 => __('zatca_admin.wizard.environment'),
        4 => __('zatca_admin.wizard.onboarding'),
        5 => __('zatca_admin.wizard.compliance'),
        6 => __('zatca_admin.wizard.done'),
    ];
@endphp
<div class="zatca-wizard">
    <header class="mb-4">
        <h1 class="app-page-title">{{ __('zatca_admin.title') }}</h1>
        <p class="app-page-lead">{{ __('zatca_admin.subtitle') }}</p>
    </header>

    <nav class="zatca-stepper" aria-label="{{ __('zatca_admin.wizard.progress') }}">
        @foreach ($steps as $num => $label)
            <button
                type="button"
                wire:click="goToStep({{ $num }})"
                @class([
                    'zatca-stepper__item',
                    'zatca-stepper__item--active' => $wizardStep === $num,
                    'zatca-stepper__item--done' => $wizardStep > $num,
                ])
            >{{ $num }}. {{ $label }}</button>
        @endforeach
    </nav>

    <div class="zatca-wizard__layout mt-4">
        <div class="min-w-0">
            @if (! empty($preflightIssues) && $wizardStep === 1)
                <section class="app-card mb-3 border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                    <h2 class="font-semibold">{{ __('zatca_admin.preflight_title') }}</h2>
                    <ul class="mt-2 list-disc ps-5">
                        @foreach ($preflightIssues as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($wizardStep === 1)
                <x-form.section :title="__('zatca_admin.wizard.business')" :lead="__('zatca_admin.wizard.business_lead')" icon="shield">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-form.input :label="__('zatca_admin.fields.name_en')" name="legalNameEn" wire:model.blur="legalNameEn" required />
                        <x-form.input :label="__('zatca_admin.fields.name_ar')" name="legalNameAr" wire:model.blur="legalNameAr" dir="rtl" />
                        <x-form.input :label="__('zatca_admin.vat_number')" name="legalVat" wire:model.live.debounce.400ms="legalVat" :hint="__('zatca_admin.hints.vat')" inputmode="numeric" maxlength="15" required wrapperClass="sm:col-span-2" />
                        <x-form.input :label="__('zatca_admin.cr_number')" name="legalCr" wire:model.blur="legalCr" inputmode="numeric" />
                    </div>
                    <h3 class="mt-4 text-xs font-semibold uppercase tracking-wide text-[var(--app-muted)]">{{ __('zatca_admin.fields.national_address') }}</h3>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        <x-form.input :label="__('zatca_admin.fields.building')" name="addrBuilding" wire:model.blur="addrBuilding" required />
                        <x-form.input :label="__('zatca_admin.fields.street')" name="addrStreet" wire:model.blur="addrStreet" required />
                        <x-form.input :label="__('zatca_admin.fields.district')" name="addrDistrict" wire:model.blur="addrDistrict" />
                        <x-form.input :label="__('zatca_admin.fields.city')" name="addrCity" wire:model.blur="addrCity" required />
                        <x-form.input :label="__('zatca_admin.fields.postal')" name="addrPostal" wire:model.blur="addrPostal" maxlength="5" inputmode="numeric" pattern="\d{5}" required />
                        <x-form.input :label="__('zatca_admin.fields.additional')" name="addrAdditional" wire:model.blur="addrAdditional" :hint="__('zatca_admin.hints.additional')" />
                    </div>
                </x-form.section>
            @endif

            @if ($wizardStep === 2)
                @if ($devices->isEmpty())
                    <p class="app-card p-4 text-sm text-amber-900">{{ __('zatca_admin.no_devices') }}</p>
                @else
                    <x-form.section :title="__('zatca_admin.wizard.egs')" :lead="__('zatca_admin.wizard.egs_lead')" icon="clipboard">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($devices as $d)
                                <button type="button" wire:click="selectDevice({{ $d->id }})" @class(['tap-action-muted text-sm', 'ring-2 ring-[var(--cafe-caramel)]' => $selectedDeviceId === $d->id])>
                                    {{ $d->name }}
                                </button>
                            @endforeach
                        </div>
                        @if ($device)
                            <form class="mt-4 grid gap-3 sm:grid-cols-2" wire:submit="saveEgsDetails">
                                <x-form.input :label="__('zatca_admin.fields.egs_name')" name="deviceDisplayName" wire:model.blur="deviceDisplayName" required />
                                <x-form.input :label="__('zatca_admin.fields.industry')" name="businessCategory" wire:model.blur="businessCategory" required />
                                <div class="form-field sm:col-span-2">
                                    <span class="form-field__label">{{ __('zatca_admin.fields.serial') }}</span>
                                    <p class="form-field__control font-mono text-xs opacity-80" readonly>{{ $device->uuid }}</p>
                                </div>
                                @if ($device->branch)
                                    <div class="form-field sm:col-span-2">
                                        <span class="form-field__label">{{ __('zatca_admin.branch') }}</span>
                                        <p class="text-sm font-medium">{{ $device->branch->name }}</p>
                                    </div>
                                @endif
                                <x-ui.livewire-action type="submit" target="saveEgsDetails" loading="saving" class="sm:col-span-2">{{ __('zatca_admin.wizard.save_next') }}</x-ui.livewire-action>
                            </form>
                        @endif
                    </x-form.section>
                @endif
            @endif

            @if ($wizardStep === 3)
                <x-form.section :title="__('zatca_admin.wizard.environment')" :lead="__('zatca_admin.wizard.environment_lead')" icon="chart">
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-950">{{ __('zatca_admin.env_warning') }}</p>
                    <div class="mt-3 grid gap-2">
                        @foreach (['sandbox' => __('zatca_admin.env.sandbox'), 'simulation' => __('zatca_admin.env.simulation'), 'production' => __('zatca_admin.env.production')] as $key => $label)
                            <label @class(['zatca-env-card cursor-pointer', 'zatca-env-card--selected' => $environmentTarget === $key])>
                                <input type="radio" class="sr-only" wire:model.live="environmentTarget" value="{{ $key }}" />
                                <span class="text-sm font-semibold">{{ $label }}</span>
                                <p class="mt-1 text-xs text-[var(--app-muted)]">{{ __('zatca_admin.env.'.$key.'_hint') }}</p>
                            </label>
                        @endforeach
                    </div>
                    <x-form.select :label="__('zatca_admin.default_document_type')" name="defaultDocumentType" wire:model.live="defaultDocumentType" class="mt-4">
                        <option value="simplified">{{ __('zatca_admin.default_document_simplified') }}</option>
                        <option value="standard">{{ __('zatca_admin.default_document_standard') }}</option>
                    </x-form.select>
                    <p class="text-xs text-[var(--app-muted)]">{{ __('zatca_admin.default_document_type_hint') }}</p>
                    <x-form.toggle wire:model.live="tenantIntegrationEnabled" name="tenantIntegrationEnabled" :label="__('zatca_admin.enable_for_tenant')" :hint="__('zatca_admin.tenant_integration_hint')" class="mt-4" />
                    <dl class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
                        <div><dt class="text-[var(--app-muted)]">{{ __('zatca_admin.driver') }}</dt><dd class="font-mono">{{ $config['driver'] }}</dd></div>
                        <div><dt class="text-[var(--app-muted)]">{{ __('zatca_admin.platform_api') }}</dt><dd>{{ $config['platform_api_enabled'] ? __('zatca_admin.yes') : __('zatca_admin.no') }}</dd></div>
                    </dl>
                    @unless ($config['api_active_for_tenant'])
                        <p class="mt-2 text-xs text-amber-900">{{ __('zatca_admin.api_disabled_hint') }}</p>
                    @endunless
                </x-form.section>
            @endif

            @if ($wizardStep === 4 && $device)
                <x-form.section :title="__('zatca_admin.wizard.onboarding')" :lead="__('zatca_admin.otp_save_hint')" icon="shield">
                    <ul class="zatca-substeps">
                        @foreach ($substeps as $sub)
                            <li class="zatca-substep">
                                <span>{{ $sub['label'] }}</span>
                                <span @class(['zatca-pill', 'zatca-pill--ok' => $sub['done'], 'zatca-pill--muted' => ! $sub['done']])>
                                    {{ $sub['done'] ? '✓' : '—' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4">
                        <x-ui.livewire-action wire:click="generateCsr" target="generateCsr" loading="generating_csr" variant="muted" class="w-full sm:w-auto">
                            {{ __('zatca_admin.generate_csr') }}
                        </x-ui.livewire-action>
                    </div>
                    <div class="mt-6 border-t pt-4" style="border-color: var(--app-border)">
                        @if ($complianceCsidReady)
                            <p class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-950" role="status">
                                {{ __('zatca_admin.otp_already_issued') }}
                            </p>
                            <x-ui.livewire-action wire:click="goToStep(5)" target="goToStep" type="button" variant="muted" class="mt-3 w-full sm:w-auto">
                                {{ __('zatca_admin.continue_to_tests') }}
                            </x-ui.livewire-action>
                        @else
                            <x-form.input :label="__('zatca_admin.otp')" name="otp" type="password" wire:model.blur="otp" :hint="__('zatca_admin.hints.otp_manual')" autocomplete="off" />
                            <x-ui.livewire-action wire:click="submitComplianceOtp" target="submitComplianceOtp" loading="submitting_otp" class="mt-2 w-full sm:w-auto" :disabled="! $config['api_active_for_tenant']">
                                {{ __('zatca_admin.submit_otp') }}
                            </x-ui.livewire-action>
                        @endif
                    </div>
                    @if ($manualImportAllowed)
                        <details class="mt-6 rounded-xl border p-3 text-sm" style="border-color: var(--app-border)">
                            <summary class="cursor-pointer font-semibold">{{ __('zatca_admin.manual_import') }}</summary>
                            <p class="mt-1 text-xs text-[var(--app-muted)]">{{ __('zatca_admin.manual_import_hint') }}</p>
                            <div class="mt-3 space-y-2">
                                <x-form.input type="password" wire:model.blur="manualComplianceToken" name="manualComplianceToken" :label="__('zatca_admin.token')" />
                                <x-form.input type="password" wire:model.blur="manualComplianceSecret" name="manualComplianceSecret" :label="__('zatca_admin.secret')" />
                                <x-form.input wire:model.blur="manualComplianceRequestId" name="manualComplianceRequestId" :label="__('zatca_admin.request_id_optional')" />
                                <x-ui.livewire-action wire:click="saveManualCompliance" target="saveManualCompliance" loading="importing_credentials" variant="muted">{{ __('zatca_admin.save_manual_compliance') }}</x-ui.livewire-action>
                            </div>
                        </details>
                    @endif
                </x-form.section>
            @endif

            @if ($wizardStep === 5 && $device)
                <x-form.section :title="__('zatca_admin.wizard.compliance')" icon="clipboard">
                    <p class="text-xs text-[var(--app-muted)]">{{ __('zatca_admin.standard_sample_lead') }}</p>
                    <h3 class="mt-4 text-sm font-semibold">{{ __('zatca_admin.compliance_samples_title') }}</h3>
                    <p class="mt-1 text-xs text-[var(--app-muted)]">{{ __('zatca_admin.compliance_samples_hint') }}</p>
                    <ul class="mt-3 space-y-3">
                        @foreach ($complianceChecklist as $item)
                            <li class="flex flex-col gap-2 rounded-xl border p-3 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--app-border)">
                                <div class="flex min-w-0 items-start gap-2">
                                    <span @class([
                                        'zatca-pill shrink-0',
                                        'zatca-pill--ok' => $item['done'],
                                        'zatca-pill--warn' => ! $item['done'],
                                    ])>{{ $item['done'] ? __('zatca_admin.compliance_sample_status_issued') : __('zatca_admin.compliance_sample_status_pending') }}</span>
                                    <span class="text-sm leading-snug">{{ $item['label'] }}</span>
                                </div>
                                @if (! $item['done'])
                                    <x-ui.livewire-action
                                        wire:click="issueComplianceSample('{{ $item['key'] }}')"
                                        target="issueComplianceSample"
                                        loading="compliance_sample"
                                        variant="muted"
                                        class="w-full shrink-0 sm:w-auto"
                                        :disabled="! $config['api_active_for_tenant']"
                                    >
                                        {{ __('zatca_admin.issue_compliance_sample') }}
                                    </x-ui.livewire-action>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <x-form.select :label="__('zatca_admin.sample_invoice')" name="complianceInvoiceId" wire:model.live="complianceInvoiceId" class="mt-4">
                        <option value="">{{ __('zatca_admin.choose_invoice') }}</option>
                        @foreach ($invoicesForCompliance as $inv)
                            <option value="{{ $inv->id }}">
                                #{{ $inv->document_number }}
                                · {{ __('zatca_admin.document_type.'.$inv->document_type->value) }}
                                · {{ $inv->issued_at?->format('Y-m-d H:i') }}
                            </option>
                        @endforeach
                    </x-form.select>
                    <x-ui.livewire-action wire:click="runComplianceCheck" target="runComplianceCheck" loading="compliance_check" class="mt-3 w-full sm:w-auto" :disabled="! $config['api_active_for_tenant']">
                        {{ __('zatca_admin.run_compliance_check') }}
                    </x-ui.livewire-action>
                    @if ($complianceCheckResponseJson)
                        <div
                            class="mt-4 rounded-xl border p-3"
                            style="border-color: var(--app-border)"
                            x-data="{ copied: false, copy() { const text = this.$refs.zatcaJson?.textContent ?? ''; if (!text) { return; } navigator.clipboard.writeText(text).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold">{{ __('zatca_admin.compliance_response_title') }}</h3>
                                <button type="button" class="tap-action-muted min-h-10 px-3 text-xs font-semibold" @click="copy()" x-text="copied ? @js(__('zatca_admin.compliance_response_copied')) : @js(__('zatca_admin.compliance_response_copy'))"></button>
                            </div>
                            <p class="mt-1 text-xs text-[var(--app-muted)]">{{ __('zatca_admin.compliance_response_hint') }}</p>
                            <pre class="mt-3 max-h-[min(24rem,50vh)] overflow-auto rounded-lg bg-[var(--app-surface-muted,#f4f4f5)] p-3 text-[0.65rem] leading-relaxed text-[var(--app-fg)]" wire:key="compliance-json-{{ md5($complianceCheckResponseJson) }}"><code x-ref="zatcaJson">{{ $complianceCheckResponseJson }}</code></pre>
                        </div>
                    @endif
                    <div class="mt-6 border-t pt-4" style="border-color: var(--app-border)">
                        <h3 class="font-semibold text-sm">{{ __('zatca_admin.step_production') }}</h3>
                        <x-ui.livewire-action wire:click="requestProductionCsid" target="requestProductionCsid" loading="requesting_production" class="mt-2 w-full sm:w-auto" :disabled="! $config['api_active_for_tenant']">
                            {{ __('zatca_admin.request_production_csid') }}
                        </x-ui.livewire-action>
                    </div>
                    @if ($manualImportAllowed)
                        <details class="mt-4 rounded-xl border p-3 text-sm" style="border-color: var(--app-border)">
                            <summary class="cursor-pointer font-semibold">{{ __('zatca_admin.manual_production') }}</summary>
                            <x-form.input type="password" wire:model.blur="manualProductionToken" name="manualProductionToken" :label="__('zatca_admin.token')" class="mt-2" />
                            <x-form.input type="password" wire:model.blur="manualProductionSecret" name="manualProductionSecret" :label="__('zatca_admin.secret')" />
                            <x-form.textarea wire:model.blur="manualProductionCertificate" name="manualProductionCertificate" :label="__('zatca_admin.certificate_optional')" rows="3" />
                            <x-ui.livewire-action wire:click="saveManualProduction" target="saveManualProduction" loading="importing_credentials" variant="muted" class="mt-2">{{ __('zatca_admin.save_manual_production') }}</x-ui.livewire-action>
                        </details>
                    @endif
                </x-form.section>
            @endif

            @if ($wizardStep === 6)
                <x-form.section :title="__('zatca_admin.wizard.done')" :lead="__('zatca_admin.known_gaps')" icon="chart">
                    @if ($device && $credential?->isReadyForSubmission())
                        <p class="text-sm font-medium text-emerald-800">{{ __('zatca_admin.ready_for_submission') }}</p>
                    @else
                        <p class="text-sm text-[var(--app-muted)]">{{ __('zatca_admin.wizard.not_ready') }}</p>
                    @endif
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($readiness as $row)
                            <li class="flex flex-wrap justify-between gap-2 border-b pb-2" style="border-color: var(--app-border)">
                                <span>{{ $row['device_name'] }} · {{ $row['branch'] ?? '—' }}</span>
                                <span class="zatca-pill zatca-pill--{{ $row['ready_for_submission'] ? 'ok' : 'warn' }}">
                                    {{ __('zatca_admin.status.'.$row['onboarding_status']) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-form.section>
            @endif

            <div class="zatca-wizard__sticky lg:static">
                @if ($wizardStep > 1)
                    <button type="button" class="tap-action-muted min-h-11 flex-1" wire:click="previousStep">{{ __('zatca_admin.wizard.back') }}</button>
                @endif
                @if ($wizardStep === 1)
                    <x-ui.livewire-action wire:click="saveBusinessDetails" target="saveBusinessDetails" loading="saving" class="min-h-11 flex-1">{{ __('zatca_admin.wizard.save_next') }}</x-ui.livewire-action>
                @elseif ($wizardStep === 3)
                    <x-ui.livewire-action wire:click="saveEnvironment" target="saveEnvironment" loading="saving" class="min-h-11 flex-1">{{ __('zatca_admin.wizard.save_next') }}</x-ui.livewire-action>
                @elseif ($wizardStep < 6)
                    <button type="button" class="tap-action-primary min-h-11 flex-1" wire:click="nextStep">{{ __('zatca_admin.wizard.next') }}</button>
                @endif
            </div>
        </div>

        <aside class="zatca-status-panel app-card p-4 text-sm">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-[var(--app-muted)]">{{ __('zatca_admin.status_panel') }}</h2>
            @if ($device)
                <p class="mt-2 font-medium">{{ $device->name }}</p>
                <p class="font-mono text-[0.65rem] text-[var(--app-muted)]">{{ $device->uuid }}</p>
            @endif
            <div class="mt-3 flex flex-wrap gap-1">
                <span @class(['zatca-pill', 'zatca-pill--ok' => $businessComplete, 'zatca-pill--warn' => ! $businessComplete])>
                    {{ __('zatca_admin.pills.business') }}
                </span>
                <span @class(['zatca-pill', 'zatca-pill--ok' => $tenantIntegrationEnabled, 'zatca-pill--muted' => ! $tenantIntegrationEnabled])>
                    {{ __('zatca_admin.pills.integration') }}
                </span>
                <span class="zatca-pill zatca-pill--muted">{{ $config['driver'] }}</span>
            </div>
            @if ($credential)
                <dl class="mt-4 space-y-2 text-xs">
                    <div>
                        <dt class="text-[var(--app-muted)]">{{ __('zatca_admin.onboarding_status') }}</dt>
                        <dd class="font-medium">{{ $credential->onboarding_status ? __('zatca_admin.status.'.$credential->onboarding_status->value) : __('zatca_admin.status.none') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[var(--app-muted)]">{{ __('zatca_admin.pills.secrets') }}</dt>
                        <dd class="font-mono text-[0.65rem]">••••••••</dd>
                    </div>
                    @if ($credential->compliance_csid_expires_at)
                        <div>
                            <dt class="text-[var(--app-muted)]">{{ __('zatca_admin.pills.compliance_expiry') }}</dt>
                            <dd>{{ $credential->compliance_csid_expires_at->format('Y-m-d') }}</dd>
                        </div>
                    @endif
                    @if ($credential->production_certificate_expires_at)
                        <div>
                            <dt class="text-[var(--app-muted)]">{{ __('zatca_admin.pills.production_expiry') }}</dt>
                            <dd>{{ $credential->production_certificate_expires_at->format('Y-m-d') }}</dd>
                        </div>
                    @endif
                </dl>
            @endif
            <p class="mt-4 rounded-lg bg-[var(--app-surface)] p-2 text-[0.65rem] text-[var(--app-muted)]">{{ __('zatca_admin.known_gaps') }}</p>
        </aside>
    </div>
</div>
