<?php

namespace Platform\Hatch\Livewire;

use Livewire\Component;

class Sidebar extends Component
{
    public function render()
    {
        return view('hatch::livewire.sidebar')
            ->layout('platform::layouts.app');
    }
}