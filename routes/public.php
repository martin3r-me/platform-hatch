<?php

use Illuminate\Support\Facades\Route;
use Platform\Hatch\Livewire\Public\IntakeStart;
use Platform\Hatch\Livewire\Public\IntakeSession;

Route::get('/p/{publicToken}', IntakeStart::class)->name('hatch.public.intake-start');
Route::get('/s/{sessionToken}', IntakeSession::class)->name('hatch.public.intake-session');
