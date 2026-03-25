<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;

class ShowAnalytics extends ShowResources
{
    public function render(): View
    {
        return view('livewire.show-analytics');
    }
}
