<?php

namespace Platform\Hatch\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Hatch\Models\HatchComplexityLevel;

class HatchComplexityLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'simple',
                'display_name' => 'Einfach',
                'description' => 'Einfache Projekte mit klaren Anforderungen',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'medium',
                'display_name' => 'Mittel',
                'description' => 'Mittlere Projekte mit moderater Komplexität',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'complex',
                'display_name' => 'Komplex',
                'description' => 'Komplexe Projekte mit vielen Abhängigkeiten',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($levels as $level) {
            HatchComplexityLevel::updateOrCreate(
                ['name' => $level['name']],
                $level
            );
        }
    }
}
