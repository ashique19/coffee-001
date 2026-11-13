<?php

namespace App\View\Components\Cafe;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LocaleSwitch extends Component
{
    public function __construct(
        public string $current,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.cafe.locale-switch');
    }
}
