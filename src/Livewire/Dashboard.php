<?php

namespace Platform\Hatch\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('hatch::livewire.dashboard')
            ->layout('platform::layouts.app');
    }
}