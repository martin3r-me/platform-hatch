{{--
    Compact-Table Segment fuer Overview-Modus.

    Erwartet:
      - $segment        ['kind' => 'compact_table', 'groups' => [...], 'columns' => [...]]
      - $isReadOnly     bool
      - $blockIndexById id => array index, fuer missing-required-Highlighting

    Layout:
      - Eine Tabelle pro Segment.
      - Erste Spalte = Gruppen-Label (Name des ersten Blocks der Gruppe, z. B. „Mo").
      - Folge-Spalten = Felder der Gruppe (kompakt gerendert).
      - Aufeinanderfolgende strukturgleiche Gruppen liefern jeweils eine Zeile.
--}}
@php
    // Sichtbare Gruppen filtern: mindestens ein Feld muss sichtbar sein.
    $visibleGroups = [];
    foreach ($segment['groups'] as $g) {
        foreach ($g['fields'] as $f) {
            if ($this->isBlockVisible($f)) {
                $visibleGroups[] = $g;
                break;
            }
        }
    }
    $hasVisible = count($visibleGroups) > 0;

    // Spalten-Definitionen aus der ersten Gruppe ableiten — Header-Labels
    // koennen vom Template-Block (name) oder, falls leer, von der
    // BlockDefinition kommen.
    $columnHeads = [];
    foreach ($segment['columns'] as $colBlock) {
        $columnHeads[] = $colBlock['name'] ?: '—';
    }

    // Gemeinsame Beschreibung: nimm die description des Header-Blocks der
    // ersten Gruppe, falls vorhanden — typischerweise dort hinterlegt.
    $tableTitle = $segment['groups'][0]['fields'][0]['name'] ?? null;
    $tableDescription = $segment['groups'][0]['fields'][0]['description'] ?? null;
@endphp

@if($hasVisible)
<div class="intake-card overflow-hidden">
    @if($tableDescription)
        <div class="px-6 pt-5 pb-3 border-b border-gray-100">
            @if($tableDescription)
                <p class="text-sm text-gray-500 leading-relaxed">{{ $tableDescription }}</p>
            @endif
        </div>
    @endif
    <div class="overflow-x-auto">
        <table class="intake-compact-table">
            <thead>
                <tr>
                    <th class="row-label-head"></th>
                    @foreach($columnHeads as $idx => $head)
                        @php $colBlock = $segment['columns'][$idx]; @endphp
                        <th>
                            {{ $head }}
                            @if($colBlock['is_required'] && !$isReadOnly)
                                <span class="text-rose-500 ml-0.5">*</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($visibleGroups as $group)
                    @php
                        // Zeilen-Label: Name des ersten Blocks der Gruppe (z. B. „Mo").
                        // Wenn der erste Block in der Spaltenliste denselben Namen liefert,
                        // wird er als Label der Zeile verwendet.
                        $firstField = $group['fields'][0];
                        $rowLabel = $firstField['name'] ?: '—';
                    @endphp
                    <tr>
                        <td class="row-label">{{ $rowLabel }}</td>
                        @foreach($group['fields'] as $colIdx => $cellBlock)
                            @php
                                $blockIdx = $blockIndexById[$cellBlock['id']] ?? null;
                                $isMissing = $blockIdx !== null && in_array($blockIdx, $missingRequiredBlocks ?? [], true);
                            @endphp
                            <td class="{{ $isMissing ? 'bg-rose-50/60' : '' }}">
                                @if(!$this->isBlockVisible($cellBlock))
                                    <span class="text-xs text-gray-300">—</span>
                                @else
                                    @include('hatch::livewire.public.partials.overview-block', [
                                        'block' => $cellBlock,
                                        'isReadOnly' => $isReadOnly,
                                        'compact' => true,
                                    ])
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
