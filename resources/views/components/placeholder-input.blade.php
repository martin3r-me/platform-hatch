{{--
    Textfeld mit Platzhalter-Bausteinen (z. B. „Kalenderwoche“) statt {{iso_week}}-Code.

    <x-hatch::placeholder-input
        name="name" label="Name" :catalog="$placeholderCatalog"
        wire:model.live.debounce.500ms="name" />

      catalog   : IntakePlaceholders::catalog() – [{key, label, description, example}]
      multiline : Zeilenumbrüche erlauben (Beschreibung)
      preview   : Vorschau „So sieht es aus“ unter dem Feld, sobald ein Baustein drin ist

    Gespeichert wird weiter die technische Form {{key}}. Das Feld zeigt sie nur
    als nicht editierbaren Baustein an; getipptes {{key}} wird beim Verlassen
    des Feldes ebenfalls zum Baustein.
--}}
@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'catalog' => [],
    'multiline' => false,
    'required' => false,
    'placeholder' => null,
    'preview' => true,
    'errorKey' => null,
])

@php $errorKey = $errorKey ?: $name; @endphp

<div
    x-data="hatchPlaceholderInput({ catalog: {{ \Illuminate\Support\Js::from(array_values($catalog)) }}, multiline: {{ $multiline ? 'true' : 'false' }} })"
    x-modelable="value"
    {{ $attributes->whereStartsWith('wire:model') }}
    class="relative"
>
    @if ($label)
        <div class="mb-1 flex items-center justify-between gap-2">
            <label @if ($name) for="{{ $name }}" @endif class="text-xs font-medium text-[color:var(--nx-text)]">
                {{ $label }}@if ($required)<span class="text-[color:var(--nx-danger)]"> *</span>@endif
            </label>
            @if ($hint)<span class="text-xs text-[color:var(--nx-muted)]">{{ $hint }}</span>@endif
        </div>
    @endif

    <div class="rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] transition-colors focus-within:border-[color:var(--nx-accent)] focus-within:ring-1 focus-within:ring-[color:var(--nx-accent)] @error($errorKey) !border-[color:var(--nx-danger)] @enderror">
        <div wire:ignore>
            <div
                x-ref="editor"
                @if ($name) id="{{ $name }}" @endif
                contenteditable="true"
                role="textbox"
                @if ($multiline) aria-multiline="true" @endif
                data-placeholder="{{ $placeholder }}"
                x-on:input="onInput()"
                x-on:keydown="onKeydown($event)"
                x-on:paste="onPaste($event)"
                x-on:keyup="saveRange()"
                x-on:mouseup="saveRange()"
                x-on:blur="onBlur()"
                class="hatch-ph-editor block w-full px-3 py-2 text-sm leading-6 text-[color:var(--nx-text)] outline-none whitespace-pre-wrap break-words {{ $multiline ? 'min-h-[4.5rem]' : '' }}"
            ></div>
        </div>

        {{-- Einfügen-Menü --}}
        <div class="flex items-center justify-between gap-2 border-t border-[color:var(--nx-line)] px-1.5 py-1">
            <div class="relative" x-on:click.outside="menuOpen = false">
                <button type="button"
                    x-on:mousedown.prevent
                    x-on:click="menuOpen = !menuOpen"
                    class="inline-flex items-center gap-1 rounded-[6px] px-1.5 py-1 text-xs text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                    <span>Platzhalter</span>
                </button>
                <div x-show="menuOpen" x-cloak x-transition.opacity.duration.100ms
                    class="absolute left-0 z-30 mt-1 w-64 overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] py-1 shadow-lg">
                    <template x-for="p in catalog" :key="p.key">
                        <button type="button"
                            x-on:mousedown.prevent
                            x-on:click="insert(p.key)"
                            class="flex w-full items-start justify-between gap-3 px-3 py-1.5 text-left transition-colors hover:bg-[color:var(--nx-hover)]">
                            <span class="min-w-0">
                                <span class="block text-sm text-[color:var(--nx-text)]" x-text="p.label"></span>
                                <span class="block text-xs text-[color:var(--nx-faint)]" x-text="p.description"></span>
                            </span>
                            <span class="shrink-0 pt-0.5 text-xs tabular-nums text-[color:var(--nx-muted)]" x-text="p.example"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    @if ($preview)
        <div x-show="hasTokens()" x-cloak class="mt-1 text-xs text-[color:var(--nx-muted)]">
            <span class="text-[color:var(--nx-faint)]">So sieht es heute aus:</span>
            <span class="text-[color:var(--nx-text)]" x-text="preview()"></span>
        </div>
    @endif

    @error($errorKey)
        <p class="mt-1 text-xs text-[color:var(--nx-danger)]">{{ $message }}</p>
    @enderror
</div>

@once
    @include('hatch::components.placeholder-input-script')
@endonce
