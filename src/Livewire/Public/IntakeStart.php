<?php

namespace Platform\Hatch\Livewire\Public;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Support\IntakeStringRenderer;

class IntakeStart extends Component
{
    public string $state = 'loading';
    public ?string $intakeName = null;
    public ?string $intakeDescription = null;
    public ?string $publicToken = null;
    public string $resumeToken = '';
    public ?string $resumeError = null;
    public ?string $startError = null;

    /** Neue Teilnahmen je Umfrage und IP pro Minute. Großzügig: Gäste einer Veranstaltung teilen oft ein WLAN. */
    public const STARTS_PER_MINUTE = 30;

    public function mount(string $publicToken)
    {
        $this->publicToken = $publicToken;

        $intake = HatchProjectIntake::where('public_token', $publicToken)->first();

        if (!$intake) {
            $this->state = 'notFound';
            return;
        }

        // Automatisches Schließdatum erreicht? Dann sofort als geschlossen behandeln
        $intake->closeIfDue();

        $renderer = app(IntakeStringRenderer::class);

        if ($intake->status === 'draft') {
            $this->state = 'notStarted';
            $this->intakeName = $renderer->render($intake->name, $intake);
            return;
        }

        if ($intake->status === 'closed') {
            $this->state = 'notActive';
            $this->intakeName = $renderer->render($intake->name, $intake);
            return;
        }

        $this->intakeName = $renderer->render($intake->name, $intake);
        $this->intakeDescription = $renderer->render($intake->description, $intake);
        $this->state = 'ready';
    }

    public function startNew()
    {
        $intake = HatchProjectIntake::where('public_token', $this->publicToken)->first();
        $intake?->closeIfDue();

        if (!$intake || $intake->status !== 'published') {
            if (!$intake) {
                $this->state = 'notActive';
            } elseif ($intake->status === 'draft') {
                $this->state = 'notStarted';
            } else {
                $this->state = 'notActive';
            }
            $this->intakeName = $intake ? app(IntakeStringRenderer::class)->render($intake->name, $intake) : $this->intakeName;
            return;
        }

        $this->startError = null;

        // Schon in diesem Browser begonnen und noch nichts beantwortet (Zurück-Taste, Neuladen,
        // doppelt getippt)? Dann dort weiter statt einer weiteren leeren Teilnahme.
        // Nur leere – an einem geteilten Tablet bekommt niemand die halbe Umfrage des Vorgängers.
        $startedKey = 'hatch.started.' . $intake->id;
        if ($token = session($startedKey)) {
            $existing = HatchIntakeSession::where('session_token', $token)
                ->where('project_intake_id', $intake->id)
                ->where('status', '!=', 'completed')
                ->where('answered_count', 0)
                ->first();
            if ($existing) {
                return redirect()->route('hatch.public.intake-session', ['sessionToken' => $existing->session_token]);
            }
        }

        $limiterKey = 'hatch-start:' . $intake->id . ':' . request()->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, self::STARTS_PER_MINUTE)) {
            $this->startError = 'Gerade kommen sehr viele Anfragen aus Ihrem Netzwerk. Bitte versuchen Sie es in einer Minute noch einmal.';
            return;
        }
        RateLimiter::hit($limiterKey, 60);

        $session = $intake->sessions()->create([
            'metadata' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referrer' => request()->header('referer'),
            ],
        ]);

        session([$startedKey => $session->session_token]);

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
            $this->resumeError = 'Bitte geben Sie einen gültigen Code im Format XXXX-XXXX ein.';
            return;
        }

        $session = HatchIntakeSession::where('session_token', $token)->first();

        if (!$session) {
            $this->resumeError = 'Zu diesem Code wurde nichts gefunden.';
            return;
        }

        $intake = HatchProjectIntake::where('public_token', $this->publicToken)->first();
        $intake?->closeIfDue();
        if (!$intake || $session->project_intake_id !== $intake->id) {
            $this->resumeError = 'Dieser Code gehört nicht zu dieser Umfrage.';
            return;
        }

        if ($intake->status !== 'published') {
            if ($intake->status === 'draft') {
                $this->state = 'notStarted';
            } else {
                $this->state = 'notActive';
            }
            $this->intakeName = app(IntakeStringRenderer::class)->render($intake->name, $intake) ?? $this->intakeName;
            return;
        }

        return redirect()->route('hatch.public.intake-session', ['sessionToken' => $session->session_token]);
    }

    public function render()
    {
        return view('hatch::livewire.public.intake-start')
            ->layout('platform::layouts.guest')
            ->title($this->intakeName ?: config('app.name'));
    }
}
