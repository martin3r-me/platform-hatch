<?php

namespace Platform\Hatch\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Models\HatchProjectIntake;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $teamId = $user->current_team_id;

        $totalTemplates = HatchProjectTemplate::where('team_id', $teamId)->count();
        $activeTemplates = HatchProjectTemplate::where('team_id', $teamId)->where('is_active', true)->count();
        $totalBlockDefinitions = HatchBlockDefinition::where('team_id', $teamId)->count();

        $intakesByStatus = HatchProjectIntake::where('team_id', $teamId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalIntakes = array_sum($intakesByStatus);
        $completedIntakes = $intakesByStatus['closed'] ?? 0;
        $completionRate = $totalIntakes > 0 ? round(($completedIntakes / $totalIntakes) * 100, 1) : 0;

        $recentIntakes = HatchProjectIntake::where('team_id', $teamId)
            ->with(['projectTemplate', 'createdByUser'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('hatch::livewire.dashboard', [
            'totalTemplates' => $totalTemplates,
            'activeTemplates' => $activeTemplates,
            'totalBlockDefinitions' => $totalBlockDefinitions,
            'intakesByStatus' => $intakesByStatus,
            'totalIntakes' => $totalIntakes,
            'completedIntakes' => $completedIntakes,
            'completionRate' => $completionRate,
            'recentIntakes' => $recentIntakes,
        ])->layout('platform::layouts.app');
    }
}
