<?php

namespace App\Livewire\Concerns;

use App\Support\Zatca\ZatcaApiUserMessage;

trait DispatchesToasts
{
    protected function toastSuccess(string $message, ?string $title = null): void
    {
        $this->dispatch('toast', type: 'success', message: $message, title: $title);
    }

    protected function toastInfo(string $message, ?string $title = null): void
    {
        $this->dispatch('toast', type: 'info', message: $message, title: $title);
    }

    protected function toastWarning(string $message, ?string $title = null, bool $dedupe = true): void
    {
        $this->dispatch('toast', type: 'warning', message: $message, title: $title, dedupe: $dedupe);
    }

    protected function toastError(string $message, ?string $title = null, bool $persistent = true): void
    {
        $this->dispatch('toast', type: 'error', message: $message, title: $title, persistent: $persistent);
    }

    protected function toastZatcaError(\Throwable $e): void
    {
        $this->toastError(ZatcaApiUserMessage::forThrowable($e));
    }
}
