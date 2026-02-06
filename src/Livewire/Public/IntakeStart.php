<?php

namespace Platform\Hatch\Livewire\Public;

use Livewire\Component;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchIntakeSession;

class IntakeStart extends Component
{
    public string $state = 'loading';
    public ?string $intakeName = null;

    public function mount(string $publicToken)
    {
        $intake = HatchProjectIntake::where('public_token', $publicToken)->first();

        if (!$intake) {
            $this->state = 'notFound';
            return;
        }

        if (!$intake->is_active) {
            $this->state = 'notActive';
            $this->intakeName = $intake->name;
            return;
        }

        $session = $intake->sessions()->create([
            'metadata' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referrer' => request()->header('referer'),
            ],
        ]);

        return redirect()->route('hatch.public.intake-session', ['sessionToken' => $session->session_token]);
    }

    public function render()
    {
        return view('hatch::livewire.public.intake-start')
            ->layout('platform::layouts.guest');
    }
}
