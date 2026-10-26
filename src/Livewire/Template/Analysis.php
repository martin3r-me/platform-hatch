<?php

namespace Platform\Hatch\Livewire\Template;

use Livewire\Attributes\Url;
use Livewire\Component;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Support\TemplateAnalytics;

/**
 * Vergleich aller Erhebungen einer Vorlage: Bewertungsfragen je Erhebung
 * (z. B. Standort) nebeneinander und im Verlauf der Kalenderwochen.
 */
class Analysis extends Component
{
    public HatchProjectTemplate $template;

    #[Url(as: 'zeitraum', except: 'all')]
    public string $period = 'all';          // all | 4 | 12 (Kalenderwochen)

    #[Url(as: 'vollstaendig', except: false)]
    public bool $completedOnly = false;

    #[Url(as: 'frage', except: '')]
    public string $trendMetric = '';        // Kennzahl für den Verlauf je Erhebung; leer = erste

    public function mount(HatchProjectTemplate $template)
    {
        abort_unless((int) $template->team_id === (int) auth()->user()->current_team_id, 404);
        $this->template = $template;
    }

    public function render()
    {
        $data = app(TemplateAnalytics::class)->build($this->template, [
            'period' => in_array($this->period, ['4', '12'], true) ? $this->period : 'all',
            'completed_only' => $this->completedOnly,
        ]);

        $trendKey = array_key_exists($this->trendMetric, $data['rows']) ? $this->trendMetric : array_key_first($data['rows']);

        return view('hatch::livewire.template.analysis', [
            'data' => $data,
            'trendKey' => $trendKey,
        ])->layout('platform::layouts.app')->title('Vergleich – ' . $this->template->name);
    }
}
