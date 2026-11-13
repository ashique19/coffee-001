<?php

namespace App\Livewire\Storefront;

use App\Livewire\Cafe\HomePage as CafeHomePage;
use App\Livewire\Plumber\HomePage as PlumberHomePage;
use App\Models\BusinessSetting;
use App\Business\BusinessContext;
use Livewire\Component;
use App\Livewire\Concerns\ResolvesBusinessContext;

class ThemedHome extends Component
{
    use ResolvesBusinessContext;

    public function mount(): void
    {
        $this->resolveBusiness();
        abort_unless($this->tenant->hasThemedStorefront(), 404);
        BusinessContext::set($this->tenant);
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
