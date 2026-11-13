<?php

namespace App\Livewire\Storefront;

use App\Livewire\Cafe\HomePage as CafeHomePage;
use App\Livewire\Plumber\HomePage as PlumberHomePage;
use App\Models\BusinessSetting;
use App\Business\BusinessContext;
use Livewire\Component;

class ThemedHome extends Component
{
    public Tenant $tenant;

    public function mount(Tenant $tenant): void
    {
        abort_unless($tenant->hasThemedStorefront(), 404);
        $this->tenant = $tenant;
        BusinessContext::set($tenant);
    }

    public function render()
    {
        if ($this->tenant->isCafeTheme()) {
            $page = new CafeHomePage;
            $page->tenant = $this->tenant;

            return $page->render();
        }

        $page = new PlumberHomePage;
        $page->tenant = $this->tenant;

        return $page->render();
    }
}
