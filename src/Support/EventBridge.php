<?php

namespace Platform\Hatch\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Brücke zum optionalen Modul events (Veranstaltungen mit VA-Nummer).
 *
 * Strikt gekapselt und per class_exists gegatet: ohne installiertes Events-Modul
 * liefert available() false und alles andere leere Ergebnisse – Hatch arbeitet
 * dann nur mit der frei eingetragenen Veranstaltungsnummer (event_reference).
 */
class EventBridge
{
    private const EVENT_CLASS = '\\Platform\\Events\\Models\\Event';

    private ?bool $available = null;
    private ?bool $crm = null;

    public function available(): bool
    {
        return $this->available ??= class_exists(self::EVENT_CLASS) && Schema::hasTable('events_events');
    }

    /** Kunde steht meist als CRM-Firma am Event (crm_company_id), das Textfeld customer ist oft leer. */
    private function crmAvailable(): bool
    {
        return $this->crm ??= Schema::hasTable('crm_companies');
    }

    /** @return array<int, string> crm_company_id => Name */
    private function companyNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === [] || !$this->crmAvailable()) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('crm_companies')->whereIn('id', $ids)->get(['id', 'name', 'legal_name'])
            ->mapWithKeys(fn ($c) => [(int) $c->id => (string) ($c->name ?: $c->legal_name)])
            ->filter()
            ->all();
    }

    /**
     * Auswahl für die Suche – nie die ganze Liste:
     *   - ohne Suchbegriff: laufende und anstehende Veranstaltungen (14 Tage zurück bis 45 voraus)
     *   - mit Suchbegriff: jedes Wort muss in VA-Nummer (mit/ohne „#“), Name, Kunde (Text oder CRM-Firma) oder Ort vorkommen
     * Sortiert: Nummern-Treffer zuerst, dann Nähe zu heute; Stornos ans Ende.
     *
     * @return array{items: array<int, array>, total: int, suggested: bool}
     */
    public function lookup(int $teamId, string $term, int $limit = 6): array
    {
        $empty = ['items' => [], 'total' => 0, 'suggested' => false];
        if (!$this->available()) {
            return $empty;
        }

        $class = self::EVENT_CLASS;
        $term = trim($term);
        $today = now()->startOfDay();
        $query = $class::query()->where('team_id', $teamId);

        if ($term === '') {
            $query->where(fn ($q) => $q
                ->whereBetween('start_date', [$today->copy()->subDays(14), $today->copy()->addDays(45)])
                ->orWhere(fn ($q) => $q->where('start_date', '<', $today)->where('end_date', '>=', $today)))
                ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'Storno'));
        } else {
            foreach (preg_split('/\s+/u', $term) as $word) {
                $plain = str_replace('#', '', $word);
                $query->where(fn ($q) => $q
                    ->where('event_number', 'like', '%' . $word . '%')
                    ->orWhereRaw("REPLACE(event_number, '#', '') like ?", ['%' . $plain . '%'])
                    ->orWhere('name', 'like', '%' . $word . '%')
                    ->orWhere('customer', 'like', '%' . $word . '%')
                    ->orWhere('location', 'like', '%' . $word . '%')
                    ->when($this->crmAvailable(), fn ($q) => $q->orWhereIn('crm_company_id', fn ($sub) => $sub
                        ->select('id')->from('crm_companies')
                        ->where(fn ($c) => $c->where('name', 'like', '%' . $word . '%')->orWhere('legal_name', 'like', '%' . $word . '%')))));
            }
        }

        $total = (clone $query)->count();
        $needle = mb_strtolower(str_replace('#', '', $term));
        $words = $term === '' ? [] : preg_split('/\s+/u', mb_strtolower($term));

        // Kandidaten um heute herum: die nächsten anstehenden + die jüngsten vergangenen
        // (+ exakte Nummern-Treffer, egal wann), dann in PHP fein sortieren
        $candidates = (clone $query)->where('start_date', '>=', $today)->orderBy('start_date')->limit(30)->get()
            ->concat((clone $query)->where(fn ($q) => $q->where('start_date', '<', $today)->orWhereNull('start_date'))->orderByDesc('start_date')->limit(30)->get());
        if ($needle !== '') {
            $candidates = $candidates->concat((clone $query)->whereRaw("LOWER(REPLACE(event_number, '#', '')) like ?", [$needle . '%'])->limit(10)->get());
        }

        $candidates = $candidates->unique('id');
        $companies = $this->companyNames($candidates->pluck('crm_company_id')->all());

        $items = $candidates
            ->sortBy(function ($event) use ($needle, $words, $today, $companies) {
                $number = mb_strtolower(str_replace('#', '', (string) $event->event_number));
                $numberRank = $needle === '' ? 3 : ($number === $needle ? 0 : (str_starts_with($number, $needle) ? 1 : (str_contains($number, $needle) ? 2 : 3)));
                // Treffer in Name/Kunde/Nummer vor Treffern, die nur über den Ort passen
                $primary = mb_strtolower($number . ' ' . $event->name . ' ' . $event->customer . ' ' . ($companies[(int) $event->crm_company_id] ?? ''));
                $fieldRank = collect($words)->every(fn ($w) => str_contains($primary, str_replace('#', '', $w))) ? 0 : 1;
                $days = $event->start_date ? abs($today->diffInDays($event->start_date, false)) : 99999;

                return sprintf('%d-%d-%d-%06d', $numberRank, $fieldRank, $event->status === 'Storno' ? 1 : 0, min($days, 999999));
            })
            ->take($limit)
            ->map(fn ($event) => $this->summary($event, $companies))
            ->values()
            ->all();

        return ['items' => $items, 'total' => $total, 'suggested' => $term === ''];
    }

    /** Event, dessen VA-Nummer exakt (mit/ohne „#“) zur frei eingetragenen Nummer passt. */
    public function matchNumber(int $teamId, ?string $number): ?array
    {
        $number = trim((string) $number);
        if (!$this->available() || $number === '') {
            return null;
        }

        $class = self::EVENT_CLASS;
        $event = $class::query()->where('team_id', $teamId)
            ->where(fn ($q) => $q->where('event_number', $number)
                ->orWhereRaw("REPLACE(event_number, '#', '') = ?", [str_replace('#', '', $number)]))
            ->first();

        return $event ? $this->summary($event, $this->companyNames([$event->crm_company_id])) : null;
    }

    /** Ein Event des Teams oder null (nicht installiert, gelöscht, fremdes Team). */
    public function find(int $teamId, ?int $eventId): ?array
    {
        if (!$eventId || !$this->available()) {
            return null;
        }

        $class = self::EVENT_CLASS;
        $event = $class::query()->where('team_id', $teamId)->find($eventId);

        return $event ? $this->summary($event, $this->companyNames([$event->crm_company_id])) : null;
    }

    /**
     * Für MCP-Tools: event_id prüfen und in Attribute übersetzen.
     * Leer/0/null löst die Verknüpfung (VA-Nummer bleibt). Liefert [attribute, fehler|null].
     */
    public function attributesFor(int $teamId, $eventId): array
    {
        if ($eventId === null || $eventId === '' || (int) $eventId === 0) {
            return [['event_id' => null], null];
        }
        if (!$this->available()) {
            return [[], 'Das Events-Modul ist hier nicht installiert – statt event_id nur event_reference (Text) setzen.'];
        }
        $event = $this->find($teamId, (int) $eventId);
        if (!$event) {
            return [[], 'Veranstaltung nicht gefunden (oder gehört zu einem anderen Team).'];
        }

        return [['event_id' => $event['id'], 'event_reference' => $event['number']], null];
    }

    /** @return array{id:int, number:string, name:string, customer:?string, dates:?string, status:?string, url:?string} */
    private function summary($event, array $companies = []): array
    {
        $start = $event->start_date;
        $end = $event->end_date;
        $dates = $start
            ? ($end && !$end->isSameDay($start) ? $start->format('d.m.') . '–' . $end->format('d.m.Y') : $start->format('d.m.Y'))
            : null;

        return [
            'id' => (int) $event->id,
            'number' => (string) $event->event_number,
            'name' => (string) $event->name,
            'customer' => $event->customer ?: ($companies[(int) $event->crm_company_id] ?? null),
            'dates' => $dates,
            'status' => $event->status ?: null,
            'url' => Route::has('events.show') ? route('events.show', ['slug' => $event->slug]) : null,
        ];
    }
}
