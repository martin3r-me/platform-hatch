<?php

namespace Platform\Hatch\Livewire\Public;

use Livewire\Component;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchIntakeSession;

class IntakeStart extends Component
{
    public string $state = 'loading';
    public ?string $intakeName = null;
    public ?string $intakeDescription = null;
    public ?string $publicToken = null;
    public string $resumeToken = '';
    public ?string $resumeError = null;

    public function mount(string $publicToken)
    {
        $this->publicToken = $publicToken;

        $intake = HatchProjectIntake::where('public_token', $publicToken)->first();

        if (!$intake) {
            $this->state = 'notFound';
            return;
        }

        if ($intake->status === 'draft') {
            $this->state = 'notStarted';
            $this->intakeName = $intake->name;
            return;
        }

        if ($intake->status === 'closed') {
            $this->state = 'notActive';
            $this->intakeName = $intake->name;
            return;
        }

        $this->intakeName = $intake->name;
        $this->intakeDescription = $intake->description;
        $this->state = 'ready';
    }

    public function startNew()
    {
        $intake = HatchProjectIntake::where('public_token', $this->publicToken)->first();

        if (!$intake || $intake->status !== 'published') {
            if (!$intake) {
                $this->state = 'notActive';
            } elseif ($intake->status === 'draft') {
                $this->state = 'notStarted';
            } else {
                $this->state = 'notActive';
            }
            $this->intakeName = $intake?->name ?? $this->intakeName;
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

    public function resumeSession()
    {
        $this->resumeError = null;

        $token = strtoupper(trim($this->resumeToken));
        $token = preg_replace('/[^A-Z0-9]/', '', $token);

        if (strlen($token) === 8) {
            $token = substr($token, 0, 4) . '-' . substr($token, 4);
        }

        if (!preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $token)) {
            $this->resumeError = 'Bitte geben Sie einen gueltigen Token im Format XXXX-XXXX ein.';
            return;
        }

        $session = HatchIntakeSession::where('session_token', $token)->first();

        if (!$session) {
            $this->resumeError = 'Keine Session mit diesem Token gefunden.';
            return;
        }

        $intake = HatchProjectIntake::where('public_token', $this->publicToken)->first();
        if (!$intake || $session->project_intake_id !== $intake->id) {
            $this->resumeError = 'Dieser Token gehoert nicht zu dieser Erhebung.';
            return;
        }

        if ($intake->status !== 'published') {
            if ($intake->status === 'draft') {
                $this->state = 'notStarted';
            } else {
                $this->state = 'notActive';
            }
            $this->intakeName = $intake->name ?? $this->intakeName;
            return;
        }

        return redirect()->route('hatch.public.intake-session', ['sessionToken' => $session->session_token]);
    }

    public function render()
    {
        return view('hatch::livewire.public.intake-start')
            ->layout('platform::layouts.guest');
    }
}
