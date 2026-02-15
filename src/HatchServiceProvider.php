<?php

namespace Platform\Hatch;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class HatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Falls in Zukunft Artisan Commands o.ä. nötig sind, hier rein
        
        // Keine Services in Hatch vorhanden
    }

    public function boot(): void
    {
        // Schritt 1: Config laden
        $this->mergeConfigFrom(__DIR__.'/../config/hatch.php', 'hatch');
        
        // Schritt 2: Existenzprüfung (config jetzt verfügbar)
        if (
            config()->has('hatch.routing') &&
            config()->has('hatch.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'hatch',
                'title'      => 'Hatch',
                'routing'    => config('hatch.routing'),
                'guard'      => config('hatch.guard'),
                'navigation' => config('hatch.navigation'),
                'sidebar'    => config('hatch.sidebar'),
            ]);
        }

        // Schritt 3: Wenn Modul registriert, Routes laden
        if (PlatformCore::getModule('hatch')) {
            ModuleRouter::group('hatch', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/guest.php');
            }, requireAuth: false);

            ModuleRouter::group('hatch', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Schritt 3b: Public Routes (ohne ModuleRouter, da kein Auth/Team nötig)
        Route::prefix('hatch')->middleware(['web'])->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/public.php');
        });

        // Schritt 4: Migrationen laden
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Schritt 5: Config veröffentlichen
        $this->publishes([
            __DIR__.'/../config/hatch.php' => config_path('hatch.php'),
        ], 'config');

        // Schritt 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'hatch');
        $this->registerLivewireComponents();

        // Schritt 7: Tools registrieren (loose gekoppelt - für AI/Chat)
        $this->registerTools();
    }

    /**
     * Registriert Hatch-Tools für die AI/Chat-Funktionalität.
     */
    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Overview
            $registry->register(new \Platform\Hatch\Tools\HatchOverviewTool());

            // Templates (CRUD)
            $registry->register(new \Platform\Hatch\Tools\ListTemplatesTool());
            $registry->register(new \Platform\Hatch\Tools\GetTemplateTool());
            $registry->register(new \Platform\Hatch\Tools\CreateTemplateTool());
            $registry->register(new \Platform\Hatch\Tools\UpdateTemplateTool());
            $registry->register(new \Platform\Hatch\Tools\DeleteTemplateTool());

            // Template ↔ Block-Definition Verknüpfung
            $registry->register(new \Platform\Hatch\Tools\AddTemplateBlockTool());
            $registry->register(new \Platform\Hatch\Tools\UpdateTemplateBlockTool());
            $registry->register(new \Platform\Hatch\Tools\RemoveTemplateBlockTool());

            // Template ↔ Block-Definition Verknüpfung (Bulk)
            $registry->register(new \Platform\Hatch\Tools\BulkAddTemplateBlocksTool());
            $registry->register(new \Platform\Hatch\Tools\BulkUpdateTemplateBlocksTool());
            $registry->register(new \Platform\Hatch\Tools\BulkRemoveTemplateBlocksTool());

            // Block Definitions (CRUD)
            $registry->register(new \Platform\Hatch\Tools\ListBlockDefinitionsTool());
            $registry->register(new \Platform\Hatch\Tools\GetBlockDefinitionTool());
            $registry->register(new \Platform\Hatch\Tools\CreateBlockDefinitionTool());
            $registry->register(new \Platform\Hatch\Tools\UpdateBlockDefinitionTool());
            $registry->register(new \Platform\Hatch\Tools\DeleteBlockDefinitionTool());

            // Block Definitions (Bulk)
            $registry->register(new \Platform\Hatch\Tools\BulkCreateBlockDefinitionsTool());
            $registry->register(new \Platform\Hatch\Tools\BulkUpdateBlockDefinitionsTool());
            $registry->register(new \Platform\Hatch\Tools\BulkDeleteBlockDefinitionsTool());

            // Intakes (CRUD)
            $registry->register(new \Platform\Hatch\Tools\ListIntakesTool());
            $registry->register(new \Platform\Hatch\Tools\GetIntakeTool());
            $registry->register(new \Platform\Hatch\Tools\CreateIntakeTool());
            $registry->register(new \Platform\Hatch\Tools\UpdateIntakeTool());
            $registry->register(new \Platform\Hatch\Tools\DeleteIntakeTool());

            // Intakes (Bulk)
            $registry->register(new \Platform\Hatch\Tools\BulkCreateIntakesTool());
            $registry->register(new \Platform\Hatch\Tools\BulkUpdateIntakesTool());
            $registry->register(new \Platform\Hatch\Tools\BulkDeleteIntakesTool());

            // Sessions (Read-Only)
            $registry->register(new \Platform\Hatch\Tools\ListIntakeSessionsTool());
            $registry->register(new \Platform\Hatch\Tools\GetIntakeSessionTool());
        } catch (\Throwable $e) {
            \Log::warning('Hatch: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Hatch\\Livewire';
        $prefix = 'hatch';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            // hatch.dashboard aus hatch + dashboard.php
            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}