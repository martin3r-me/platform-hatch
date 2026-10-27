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

    public function available(): bool
    {
        return $this->available ??= class_exists(self::EVENT_CLASS) && Schema::hasTable('events_events');
    }

    /**
     * Events des Teams zur Auswahl, gesucht in VA-Nummer (mit/ohne „#“), Name und Kunde.
     *
     * @return array<int, array> siehe summary()
     */
    public function search(int $teamId, string $term, int $limit = 6): array
    {
        $term = trim($term);
        if (!$this->available() || mb_strlen($term) < 2) {
            return [];
        }

        $class = self::EVENT_CLASS;
        $plain = str_replace('#', '', $term);

        return $class::query()
            ->where('team_id', $teamId)
            ->where(fn ($q) => $q
                ->where('event_number', 'like', '%' . $term . '%')
                ->orWhereRaw("REPLACE(event_number, '#', '') like ?", ['%' . $plain . '%'])
                ->orWhere('name', 'like', '%' . $term . '%')
                ->orWhere('customer', 'like', '%' . $term . '%'))
            ->orderByDesc('start_date')
            ->limit($limit)
            ->get()
            ->map(fn ($event) => $this->summary($event))
            ->all();
    }

    /** Ein Event des Teams oder null (nicht installiert, gelöscht, fremdes Team). */
    public function find(int $teamId, ?int $eventId): ?array
    {
        if (!$eventId || !$this->available()) {
            return null;
        }

        $class = self::EVENT_CLASS;
        $event = $class::query()->where('team_id', $teamId)->find($eventId);

        return $event ? $this->summary($event) : null;
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
    private function summary($event): array
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
            'customer' => $event->customer ?: null,
            'dates' => $dates,
            'status' => $event->status ?: null,
            'url' => Route::has('events.show') ? route('events.show', ['slug' => $event->slug]) : null,
        ];
    }
}
