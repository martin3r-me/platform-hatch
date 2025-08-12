<?php

use Illuminate\Support\Facades\Route;

Route::get('/', Platform\Hatch\Livewire\Dashboard::class)->name('hatch.dashboard');
Route::get('/block-definitions', \Platform\Hatch\Livewire\BlockDefinition\Index::class)->name('hatch.block-definitions.index');
Route::get('/block-definitions/{blockDefinition}', \Platform\Hatch\Livewire\BlockDefinition\Show::class)->name('hatch.block-definitions.show');
Route::get('/templates', Platform\Hatch\Livewire\Template\Index::class)->name('hatch.templates.index');
Route::get('/templates/{template}', Platform\Hatch\Livewire\Template\Show::class)->name('hatch.templates.show');

Route::get('/project-intakes', Platform\Hatch\Livewire\ProjectIntake\Index::class)->name('hatch.project-intakes.index');
Route::get('/project-intakes/{projectIntake}', Platform\Hatch\Livewire\ProjectIntake\Show::class)->name('hatch.project-intakes.show');