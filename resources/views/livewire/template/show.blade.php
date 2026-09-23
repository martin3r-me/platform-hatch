<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Formulare', 'href' => route('hatch.dashboard'), 'icon' => 'rocket-launch'],
            ['label' => 'Vorlagen', 'href' => route('hatch.templates.index')],
            ['label' => $template?->name ?: 'Vorlage'],
        ]">
            <span wire:loading.delay class="text-xs text-[color:var(--nx-faint)]">Speichert…</span>
            <x-nx-button variant="primary" wire:click="createIntakeFromTemplate">
                @svg('heroicon-o-paper-airplane', 'w-4 h-4')
                <span>Als Erhebung starten</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Vorlage" width="w-80" :defaultOpen="true" side="left">
            @php
                $gruppe = 'flex flex-col gap-3 border-t border-[color:var(--nx-line)] p-4';
                $ueberschrift = 'text-xs font-medium tracking-wide text-[color:var(--nx-faint)]';
                $zeile = 'flex items-center justify-between gap-3 text-sm';
                $zeileLabel = 'shrink-0 text-xs text-[color:var(--nx-muted)]';
                $fieldCount = $template->templateBlocks->count();
            @endphp

            {{-- Status --}}
            <div class="flex items-center justify-between gap-2 p-4">
                <span class="{{ $ueberschrift }}">Status</span>
                <button type="button" wire:click="toggleActive" class="inline-flex items-center gap-2 text-xs text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]"
                    title="{{ $template->is_active ? 'Inaktive Vorlagen stehen beim Anlegen einer Erhebung nicht zur Auswahl' : 'Wieder für neue Erhebungen verfügbar machen' }}">
                    <x-nx-badge :variant="$template->is_active ? 'success' : 'neutral'" dot>{{ $template->is_active ? 'Aktiv' : 'Inaktiv' }}</x-nx-badge>
                    <span class="underline decoration-dotted underline-offset-2">{{ $template->is_active ? 'deaktivieren' : 'aktivieren' }}</span>
                </button>
            </div>

            {{-- Grundlagen --}}
            <div class="{{ $gruppe }}">
                <span class="{{ $ueberschrift }}">Grundlagen</span>
                <x-nx-input-text name="template.name" label="Name" required wire:model.live.debounce.500ms="template.name" :errorKey="'template.name'" />
                <x-nx-input-textarea name="template.description" label="Beschreibung" hint="intern" rows="3"
                    wire:model.live.debounce.500ms="template.description" placeholder="Wofür wird diese Vorlage verwendet?" />
            </div>

            {{-- Darstellung --}}
            <div class="{{ $gruppe }}">
                <span class="{{ $ueberschrift }}">Darstellung für Respondenten</span>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(['overview' => ['Alles auf einer Seite', 'heroicon-o-document-text', 'Ideal für Umfragen'], 'block_flow' => ['Schritt für Schritt', 'heroicon-o-queue-list', 'Eine Frage pro Seite']] as $mode => [$label, $icon, $hint])
                        <button type="button" wire:click="$set('template.flow_mode', '{{ $mode }}')"
                            class="flex flex-col items-start gap-1 rounded-[8px] border p-2.5 text-left transition-colors {{ ($template->flow_mode ?? 'block_flow') === $mode ? 'border-[color:var(--nx-accent)] bg-[color:var(--nx-active)]' : 'border-[color:var(--nx-line)] hover:bg-[color:var(--nx-hover)]' }}">
                            @svg($icon, 'w-4 h-4 text-[color:var(--nx-muted)]')
                            <span class="text-xs font-medium text-[color:var(--nx-text)]">{{ $label }}</span>
                            <span class="text-[11px] leading-tight text-[color:var(--nx-faint)]">{{ $hint }}</span>
                        </button>
                    @endforeach
                </div>
            </div>


            {{-- Details --}}
            <div class="{{ $gruppe }}">
                <span class="{{ $ueberschrift }}">Details</span>
                <div class="flex flex-col gap-2">
                    <div class="{{ $zeile }}"><span class="{{ $zeileLabel }}">Fragen</span><span class="tabular-nums">{{ count($groups) }} <span class="text-[color:var(--nx-faint)]">· {{ $fieldCount }} {{ $fieldCount === 1 ? 'Feld' : 'Felder' }}</span></span></div>
                    <div class="{{ $zeile }}">
                        <span class="{{ $zeileLabel }}">Erhebungen</span>
                        <a href="{{ route('hatch.project-intakes.index') }}" wire:navigate class="tabular-nums hover:underline">{{ $intakeCount }}</a>
                    </div>
                    @if($template->createdByUser)
                        <div class="{{ $zeile }}"><span class="{{ $zeileLabel }}">Erstellt von</span><span class="truncate">{{ $template->createdByUser->name }}</span></div>
                    @endif
                    <div class="{{ $zeile }}"><span class="{{ $zeileLabel }}">Erstellt am</span><span class="tabular-nums">{{ $template->created_at?->format('d.m.Y H:i') }}</span></div>
                    <div class="{{ $zeile }}"><span class="{{ $zeileLabel }}">Geändert</span><span class="tabular-nums" title="{{ $template->updated_at?->format('d.m.Y H:i') }}">{{ $template->updated_at?->diffForHumans() }}</span></div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <x-nx-empty icon="heroicon-o-bolt">Keine Aktivitäten vorhanden</x-nx-empty>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
    <div class="space-y-4">

        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-[color:var(--nx-text)]">Fragen <span class="font-normal tabular-nums text-[color:var(--nx-faint)]">{{ count($groups) }}</span></h2>
                <p class="mt-0.5 text-xs text-[color:var(--nx-faint)]">Klick auf eine Frage öffnet sie zum Bearbeiten. Eine Frage kann mehrere Felder haben, die zusammen angezeigt werden.</p>
            </div>
            <x-nx-button variant="primary" wire:click="openTypePicker('new')">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Frage hinzufügen</span>
            </x-nx-button>
        </div>

        @forelse($groups as $gi => $group)
            @php
                $header = $group['header'];
                $isOpen = $editingGroup === $group['key'];
                $hasRules = collect($group['fields'])->contains(fn ($f) => !empty($f->visibility_rules['rules'] ?? []));
                $isRequired = collect($group['fields'])->contains(fn ($f) => $f->is_required);
            @endphp
            <div wire:key="group-{{ $group['key'] }}"
                 class="group/card relative flex gap-3 rounded-[8px] border bg-[color:var(--nx-surface)] p-4 transition-colors {{ $isOpen ? 'border-[color:var(--nx-accent)] ring-1 ring-[color:var(--nx-accent)]' : 'border-[color:var(--nx-line)] hover:bg-[color:var(--nx-hover)]' }}">
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)] text-xs font-semibold tabular-nums text-[color:var(--nx-muted)]">{{ $gi + 1 }}</div>

                <button type="button" wire:click="openEditor('{{ $group['key'] }}')" class="min-w-0 flex-1 text-left">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-[color:var(--nx-text)]">{{ $header->name ?: 'Ohne Titel' }}</span>
                        @if($isRequired)<x-nx-badge variant="warning">Pflicht</x-nx-badge>@endif
                        @if($hasRules)<x-nx-badge variant="info">Bedingt sichtbar</x-nx-badge>@endif
                        @if($header->display_compact && ($template->flow_mode ?? '') === 'overview')<x-nx-badge>Tabellenzeile</x-nx-badge>@endif
                    </div>
                    @if($header->description)
                        <p class="mt-0.5 line-clamp-2 text-xs text-[color:var(--nx-muted)]">{{ $header->description }}</p>
                    @endif
                    <div class="mt-2 flex flex-col gap-1">
                        @foreach($group['fields'] as $fi => $field)
                            @php
                                $def = \Platform\Hatch\Support\BlockTypes::get($field->block_type);
                                $summary = \Platform\Hatch\Support\BlockTypes::summary($def['type'], $field->logic_config);
                            @endphp
                            <div class="flex min-w-0 items-center gap-2 text-xs">
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-[6px] bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[color:var(--nx-muted)]">
                                    @svg($def['icon'], 'w-3.5 h-3.5')
                                    {{ $def['label'] }}
                                </span>
                                @if($fi > 0 && $field->name)
                                    <span class="shrink-0 text-[color:var(--nx-text)]">{{ $field->name }}</span>
                                @endif
                                @if($summary !== '')
                                    <span class="truncate text-[color:var(--nx-faint)]">{{ $summary }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </button>

                <div class="flex shrink-0 items-start gap-0.5 opacity-60 transition-opacity group-hover/card:opacity-100">
                    <x-nx-button icon variant="ghost" wire:click="moveGroup('{{ $group['key'] }}', -1)" title="Nach oben" :disabled="$gi === 0">@svg('heroicon-o-arrow-up', 'w-4 h-4')</x-nx-button>
                    <x-nx-button icon variant="ghost" wire:click="moveGroup('{{ $group['key'] }}', 1)" title="Nach unten" :disabled="$gi === count($groups) - 1">@svg('heroicon-o-arrow-down', 'w-4 h-4')</x-nx-button>
                    <x-nx-button icon variant="ghost" wire:click="duplicateGroup('{{ $group['key'] }}')" title="Duplizieren">@svg('heroicon-o-document-duplicate', 'w-4 h-4')</x-nx-button>
                    <x-nx-button icon variant="ghost" wire:click="deleteGroup('{{ $group['key'] }}')"
                        wire:confirm="Frage „{{ $header->name ?: 'Ohne Titel' }}“ mit allen Feldern löschen? Bereits gegebene Antworten bleiben in den Erhebungen erhalten, werden aber keiner Frage mehr zugeordnet."
                        title="Löschen">@svg('heroicon-o-trash', 'w-4 h-4 text-[color:var(--nx-danger)]')</x-nx-button>
                </div>
            </div>
        @empty
            <x-nx-card>
                <x-nx-empty icon="heroicon-o-puzzle-piece">
                    Noch keine Fragen
                    <x-slot name="action">
                        <x-nx-button variant="primary" wire:click="openTypePicker('new')">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Erste Frage hinzufügen</span>
                        </x-nx-button>
                    </x-slot>
                </x-nx-empty>
            </x-nx-card>
        @endforelse

        @if(count($groups) > 0)
            <button type="button" wire:click="openTypePicker('new')"
                class="flex w-full items-center justify-center gap-2 rounded-[8px] border border-dashed border-[color:var(--nx-line-strong)] p-3 text-sm text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                @svg('heroicon-o-plus', 'w-4 h-4')
                Frage hinzufügen
            </button>
        @endif

    </div>
    </x-ui-page-container>

    {{-- Typ-Auswahl --}}
    @if($typePicker)
        <div class="hatch-modal-overlay" wire:click.self="closeTypePicker" x-data x-on:keydown.escape.window="$wire.closeTypePicker()">
            <div class="hatch-modal">
                <div class="flex items-center justify-between gap-3 border-b border-[color:var(--nx-line)] px-5 py-3">
                    <div>
                        <h3 class="text-sm font-semibold text-[color:var(--nx-text)]">
                            {{ $typePicker === 'new' ? 'Welche Art von Frage?' : ($typePicker === 'add' ? 'Feld hinzufügen' : 'Feldtyp ändern') }}
                        </h3>
                        @if(str_starts_with($typePicker, 'change:'))
                            <p class="text-xs text-[color:var(--nx-faint)]">Einstellungen des bisherigen Typs gehen verloren – Antwortmöglichkeiten bleiben bei Auswahl-Typen erhalten.</p>
                        @endif
                    </div>
                    <x-nx-button icon variant="ghost" wire:click="closeTypePicker" title="Schließen">@svg('heroicon-o-x-mark', 'w-4 h-4')</x-nx-button>
                </div>
                <div class="grid gap-5 p-5 sm:grid-cols-2">
                    @foreach($typeGroups as $tg)
                        <div>
                            <div class="mb-1.5 text-xs font-medium text-[color:var(--nx-faint)]">{{ $tg['label'] }}</div>
                            <div class="flex flex-col">
                                @foreach($tg['types'] as $t)
                                    <button type="button" wire:click="pickType('{{ $t['type'] }}')"
                                        class="flex items-center gap-3 rounded-[6px] px-2 py-1.5 text-left transition-colors hover:bg-[color:var(--nx-hover)]">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[6px] bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">@svg($t['icon'], 'w-4 h-4')</span>
                                        <span class="min-w-0">
                                            <span class="block text-sm text-[color:var(--nx-text)]">{{ $t['label'] }}</span>
                                            <span class="block truncate text-xs text-[color:var(--nx-faint)]">{{ $t['hint'] }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Seitenpanel: Frage bearbeiten --}}
    @if($editingGroup && !empty($draft))
        @include('hatch::livewire.template.partials.question-editor')
    @endif

    {{-- Tragendes Layout von Panel und Dialog als eigenes CSS, damit es nicht vom Tailwind-Build der App abhängt --}}
    <style>
        .hatch-drawer-overlay { position: fixed; inset: 0; z-index: 50; display: flex; justify-content: flex-end; }
        .hatch-drawer-backdrop { position: absolute; inset: 0; background: rgba(15, 15, 25, .25); }
        .hatch-drawer {
            position: relative; display: flex; flex-direction: column; height: 100%; width: 100%; max-width: 640px;
            background: var(--nx-bg, #fafafa); border-left: 1px solid var(--nx-line); box-shadow: -20px 0 50px rgba(15, 15, 25, .18);
        }
        .hatch-drawer-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
        .hatch-modal-overlay {
            position: fixed; inset: 0; z-index: 60; overflow-y: auto; background: rgba(15, 15, 25, .3);
            display: flex; align-items: flex-start; justify-content: center; padding: 8vh 16px 16px;
        }
        .hatch-modal {
            width: 100%; max-width: 48rem; border-radius: 12px; border: 1px solid var(--nx-line);
            background: var(--nx-surface, #fff); box-shadow: 0 25px 60px rgba(15, 15, 25, .25);
        }
        .hatch-preview { background: #fff; }
        .hatch-editor-select {
            height: 2rem; border-radius: 6px; border: 1px solid var(--nx-line-strong);
            background: var(--nx-surface); color: var(--nx-text); padding: 0 .5rem; font-size: .8125rem;
        }
        .hatch-editor-select:focus { outline: none; border-color: var(--nx-accent); box-shadow: 0 0 0 1px var(--nx-accent); }
        @include('hatch::livewire.public.partials.field-styles')
    </style>
</x-ui-page>
