<?php

namespace Platform\Hatch\Support;

/**
 * Gemeinsame Regeln für einzelne Antwort-Werte (answers["block_{id}"]).
 *
 * Mehrfach-Typen liegen als JSON vor; die Übersichts-Ansicht speichert auch
 * unberührte Felder („[]“, „{}“, {"start":"","end":""}) – das zählt als leer.
 */
class AnswerValue
{
    /** Typen ohne eigene Eingabe der Teilnehmenden – zählen nie als „beantwortet“. */
    public const NOT_ANSWERABLE = ['info', 'section', 'calculated', 'hidden'];

    public static function isEmpty(?string $type, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (in_array($type, ['multi_select', 'ranking', 'repeater'], true)) {
            return self::decodeList($value) === [];
        }
        if (in_array($type, ['matrix', 'address', 'date_range'], true)) {
            return array_filter(self::decodeMap($value), fn ($v) => $v !== '' && $v !== null) === [];
        }
        if (is_string($value) && in_array($value, ['[]', '{}'], true)) {
            return true;
        }
        if ($type === 'lookup' && is_string($value) && str_starts_with($value, '[')) {
            return self::decodeList($value) === [];
        }

        return false;
    }

    /**
     * Anzahl tatsächlich beantworteter Felder.
     *
     * @param array<int|string, string> $typesById block_id => block_type (unbekannte Blöcke zählen mit)
     */
    public static function countAnswered(array $answers, array $typesById): int
    {
        $count = 0;
        foreach ($answers as $key => $value) {
            $id = (int) str_replace('block_', '', (string) $key);
            $type = $typesById[$id] ?? null;
            if (in_array($type, self::NOT_ANSWERABLE, true)) {
                continue;
            }
            if (!self::isEmpty($type, $value)) {
                $count++;
            }
        }

        return $count;
    }

    public static function decodeList($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    public static function decodeMap($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
