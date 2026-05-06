<?php

use Illuminate\Support\Facades\Route;
use Platform\Hatch\Livewire\Public\IntakeStart;
use Platform\Hatch\Livewire\Public\IntakeSession;
use Platform\Hatch\Livewire\Public\IntakeSessionOverview;

Route::get('/p/{publicToken}', IntakeStart::class)->name('hatch.public.intake-start');

// Intake-Session-Routes:
// Default-Route bedient den klassischen Block-für-Block-Flow. Sessions, deren
// Template flow_mode = 'overview' hat, werden in IntakeSession::mount() auf die
// Overview-Route umgeleitet (und umgekehrt). Damit bleibt der bestehende
// Block-Flow unverändert und der Overview-Modus läuft additiv parallel.
Route::get('/s/{sessionToken}', IntakeSession::class)->name('hatch.public.intake-session');
Route::get('/s/{sessionToken}/overview', IntakeSessionOverview::class)->name('hatch.public.intake-session.overview');
