<?php

namespace Platform\Hatch\Support;

/**
 * Wertet visibility_rules eines Blocks gegen gespeicherte Antworten aus
 * (answers["block_{id}"]). Genutzt vom Formular und von der Auswertung –
 * dort zählt eine bedingte Frage nur bei denen, die sie auch gesehen haben.
 * Blöcke ohne Regeln sind immer sichtbar.
 */
class BlockVisibility
{
    public static function isVisible(?array $rules, array $answers): bool
    {
        if (!is_array($rules) || empty($rules['rules'])) {
            return true;
        }

        $combinator = strtoupper($rules['combinator'] ?? 'AND');

        $results = [];
        foreach ($rules['rules'] as $rule) {
            $sourceId = (int) ($rule['source_block_id'] ?? 0);
            if ($sourceId <= 0) continue;

            $results[] = self::matches($rule['operator'] ?? 'equals', $rule['value'] ?? '', $answers["block_{$sourceId}"] ?? '');
        }

        if (empty($results)) return true;

        return $combinator === 'OR'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    public static function matches(string $operator, $expected, $raw): bool
    {
        // JSON-encoded Antworten (multi_select, matrix, ...) für selected/contains vernünftig prüfen.
        $decoded = null;
        if (is_string($raw) && $raw !== '' && ($raw[0] === '[' || $raw[0] === '{')) {
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) $decoded = $tmp;
        }

        return match ($operator) {
            'equals' => (string) $raw === (string) $expected,
            'not_equals' => (string) $raw !== (string) $expected,
            'contains' => is_string($raw) ? str_contains($raw, (string) $expected) : false,
            'empty' => $raw === '' || $raw === null || $raw === '[]' || $raw === '{}',
            'not_empty' => !($raw === '' || $raw === null || $raw === '[]' || $raw === '{}'),
            'selected' => is_array($decoded)
                ? in_array((string) $expected, array_map('strval', $decoded), true)
                : (string) $raw === (string) $expected,
            'not_selected' => is_array($decoded)
                ? !in_array((string) $expected, array_map('strval', $decoded), true)
                : (string) $raw !== (string) $expected,
            default => true,
        };
    }
}
