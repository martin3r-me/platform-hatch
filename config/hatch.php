<?php

return [
    'name' => 'Formulare',
    'description' => 'Formulare Modul',
    'version' => '1.0.0',
    
    'routing' => [
        'prefix' => 'hatch',
        'middleware' => ['web', 'auth'],
    ],
    
    'guard' => 'web',
    
    'navigation' => [
        'main' => [
            'hatch' => [
                'title' => 'Formulare',
                'icon' => 'heroicon-o-rocket-launch',
                'route' => 'hatch.dashboard',
            ],
        ],
    ],
    
    'sidebar' => [
        'hatch' => [
            'title' => 'Formulare',
            'icon' => 'heroicon-o-rocket-launch',
            'items' => [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'route' => 'hatch.dashboard',
                    'icon' => 'heroicon-o-home',
                ],
                'lookups' => [
                    'title' => 'Lookups',
                    'route' => 'hatch.lookups.index',
                    'icon' => 'heroicon-o-list-bullet',
                ],
            ],
        ],
    ],
    'billables' => [
        [
            'model' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'type' => 'per_item',
            'label' => 'Projekt-Intake',
            'description' => 'Jeder erstellte Projekt-Intake verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                ['cost_per_day' => 0.005, 'start_date' => '2025-01-01', 'end_date' => null]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
        [
            'model' => \Platform\Hatch\Models\HatchIntakeSession::class,
            'type' => 'per_item',
            'label' => 'Intake-Session',
            'description' => 'Jede erstellte Intake-Session verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                ['cost_per_day' => 0.0025, 'start_date' => '2025-01-01', 'end_date' => null]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
    ],
];