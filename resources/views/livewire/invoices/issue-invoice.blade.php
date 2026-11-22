<div class="pb-24 lg:pb-8">
    <header class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="app-page-title">{{ $pageTitle }}</h1>
            <p class="app-page-lead">{{ __('invoices.issue_page_lead') }}</p>
        </div>
        @if ($isStandard)
            <a class="tap-action-muted min-h-11 text-sm" href="{{ route('admin.invoices.issue', ['mode' => 'simplified']) }}">{{ __('invoices.nav_issue_simplified') }}</a>
        @else
            <a class="tap-action-muted min-h-11 text-sm" href="{{ route('admin.invoices.issue', ['mode' => 'standard']) }}">{{ __('invoices.nav_issue_standard') }}</a>
        @endif
    </header>

    <form id="issue-invoice-form" wire:submit="issue" class="app-card space-y-4 p-4">
        @error('document')
            <p class="form-field__error" role="alert">{{ $message }}</p>
        @enderror

        @if ($isStandard)
            <fieldset class="space-y-3">
                <legend class="app-section-heading">{{ __('invoices.buyer_section') }}</legend>
                <x-form.input :label="__('invoices.buyer_name')" name="buyerName" wire:model.blur="buyerName" required />
                <p class="text-xs text-[var(--app-muted)]">{{ __('invoices.buyer_id_hint') }}</p>
                <x-form.input :label="__('business.fields.vat_number')" name="buyerVat" wire:model.blur="buyerVat" />
                <x-form.input :label="__('business.fields.cr_number')" name="buyerCr" wire:model.blur="buyerCr" />
                <x-form.input :label="__('business.fields.building')" name="addrBuilding" wire:model.blur="addrBuilding" required />
                <x-form.input :label="__('business.fields.street')" name="addrStreet" wire:model.blur="addrStreet" required />
                <x-form.input :label="__('business.fields.district')" name="addrDistrict" wire:model.blur="addrDistrict" />
                <x-form.input :label="__('business.fields.city')" name="addrCity" wire:model.blur="addrCity" required />
                <x-form.input :label="__('business.fields.postal')" name="addrPostal" wire:model.blur="addrPostal" required />
                <x-form.input :label="__('business.fields.additional')" name="addrAdditional" wire:model.blur="addrAdditional" />
            </fieldset>
        @else
            <x-form.input :label="__('invoices.buyer_optional')" name="buyerNameOptional" wire:model.blur="buyerNameOptional" />
        @endif

        <fieldset class="space-y-3">
            <legend class="app-section-heading">{{ __('invoices.lines_section') }}</legend>
            @foreach ($lines as $index => $line)
                <div class="app-inset-panel" wire:key="line-{{ $line['line_id'] ?? $index }}" @if (!empty($line['is_custom'])) data-custom-line="1" @endif>
                    <label class="form-field @error('lines.'.$index.'.name') form-field--invalid @enderror">
                        <span class="form-field__label">{{ __('invoices.custom_line_name') }}</span>
                        <input
                            type="text"
                            class="form-field__control w-full {{ $errors->has('lines.'.$index.'.name') ? 'form-field__control--invalid' : '' }}"
                            wire:model.live="lines.{{ $index }}.name"
                            placeholder="{{ __('invoices.custom_line_name') }}"
                            autocomplete="off"
                            aria-label="{{ __('invoices.custom_line_name') }}"
                            @if ($errors->has('lines.'.$index.'.name')) aria-invalid="true" @endif
                        />
                        <x-invoices.document-line-field-error :name="'lines.'.$index.'.name'" />
                    </label>
                    <input type="hidden" wire:model="lines.{{ $index }}.item_type" />
                    <input type="hidden" wire:model="lines.{{ $index }}.vat_zatca_category" />
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        <x-form.input type="number" step="0.01" :label="__('invoices.line_qty')" name="lines.{{ $index }}.quantity" wire:model.blur="lines.{{ $index }}.quantity" />
                        <x-form.input type="number" step="0.01" :label="__('invoices.line_price_ex_vat')" name="lines.{{ $index }}.unit_price" wire:model.blur="lines.{{ $index }}.unit_price" />
                        <x-form.input type="number" step="0.01" :label="__('invoices.line_vat')" name="lines.{{ $index }}.vat_rate" wire:model.blur="lines.{{ $index }}.vat_rate" />
                    </div>
                    @if (count($lines) > 1)
                        <button type="button" class="tap-action-muted mt-2 min-h-10 text-xs" wire:click="removeLine({{ $index }})">{{ __('invoices.remove_line') }}</button>
                    @endif
                </div>
            @endforeach
            <button type="button" class="tap-action-muted min-h-11 w-full sm:w-auto" wire:click="addCustomLine" data-testid="add-custom-line">{{ __('invoices.add_custom_line') }}</button>
        </fieldset>

        <div class="hidden flex-wrap gap-2 lg:flex">
            <x-ui.livewire-action type="button" target="issueAndPrint" loading="issue" class="tap-action-primary min-h-11" wire:click="issueAndPrint" data-testid="issue-and-print">
                {{ __('invoices.issue_and_print') }}
            </x-ui.livewire-action>
            <x-ui.livewire-action type="submit" target="issue" loading="issue" class="tap-action-primary min-h-11">
                {{ __('invoices.issue_submit') }}
            </x-ui.livewire-action>
        </div>
    </form>

    <x-invoices.document-sticky-action-bar
        :total="$previewTotal"
        :submit-label="__('invoices.issue_submit')"
        wire-target="issue"
        form-id="issue-invoice-form"
        print-target="issueAndPrint"
        :print-label="__('invoices.issue_and_print')"
        :print-short-label="__('invoices.issue_and_print_short')"
    />
</div>
