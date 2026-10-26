{{-- Seitenleiste im nx-Stil, fest auf Deutsch (unabhängig von APP_LOCALE).
     Nutzung: {{ $paginator->links('hatch::livewire.partials.pagination') }} --}}
@php
    $pageName = $paginator->getPageName();
    $btn = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm tabular-nums transition';
    $idle = $btn . ' text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]';
    $off = $btn . ' cursor-default text-[color:var(--nx-faint)] opacity-50';
@endphp
<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Seiten" class="flex items-center justify-between gap-3">
            <p class="m-0 text-xs tabular-nums text-[color:var(--nx-faint)]">
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} von {{ $paginator->total() }}
            </p>

            <div class="flex items-center gap-0.5">
                @if ($paginator->onFirstPage())
                    <span class="{{ $off }}" aria-hidden="true">@svg('heroicon-o-chevron-left', 'w-4 h-4')</span>
                @else
                    <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $idle }}" aria-label="Vorherige Seite">@svg('heroicon-o-chevron-left', 'w-4 h-4')</button>
                @endif

                {{-- Seitenzahlen nur ab sm, mobil reichen Pfeile + „x–y von z“ --}}
                <div class="hidden items-center gap-0.5 sm:flex">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="{{ $off }}">…</span>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <span wire:key="paginator-{{ $pageName }}-page{{ $page }}">
                                    @if ($page == $paginator->currentPage())
                                        <span aria-current="page" class="{{ $btn }} bg-[color:var(--nx-active)] font-medium text-[color:var(--nx-text)]">{{ $page }}</span>
                                    @else
                                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" class="{{ $idle }}" aria-label="Seite {{ $page }}">{{ $page }}</button>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    @endforeach
                </div>

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $idle }}" aria-label="Nächste Seite">@svg('heroicon-o-chevron-right', 'w-4 h-4')</button>
                @else
                    <span class="{{ $off }}" aria-hidden="true">@svg('heroicon-o-chevron-right', 'w-4 h-4')</span>
                @endif
            </div>
        </nav>
    @endif
</div>
