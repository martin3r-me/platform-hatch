<?php

return [
    'name' => 'Hatch',
    'description' => 'Hatch Module',
    'version' => '1.0.0',
    
    'routing' => [
        'prefix' => 'hatch',
        'middleware' => ['web', 'auth'],
    ],
    
    'guard' => 'web',
    
    'navigation' => [
        'main' => [
            'hatch' => [
                'title' => 'Hatch',
                'icon' => 'heroicon-o-rocket-launch',
                'route' => 'hatch.dashboard',
            ],
        ],
    ],
    
    'sidebar' => [
        'hatch' => [
            'title' => 'Hatch',
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
];