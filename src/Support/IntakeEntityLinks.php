<?php

namespace Platform\Hatch\Support;

use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Organization\Authz\DimensionLinkAuthorizer;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Services\EntityDimensionBridge;

/**
 * Verknüpfung Erhebung ↔ Organisationseinheit (Dimension „entity“ aus organization).
 *
 * Dieselben Links setzt auch das Kontext-Panel (Reiter „Verknüpfungen“); die
 * Hatch-Seitenleiste gruppiert danach. Hier: beim Anlegen direkt verknüpfen und
 * beim Duplizieren übernehmen. Verknüpft wird nur mit Einheiten, die der User
 * beschreiben darf – wie im Panel.
 */
class IntakeEntityLinks
{
    /** Morph-Varianten, unter denen Links einer Erhebung liegen können. */
    public const LINKABLE_TYPES = ['hatch_project_intake', HatchProjectIntake::class];

    /** @return array<int, int> */
    public function writableEntityIds(User $user): array
    {
        return (new DimensionLinkAuthorizer())->writableEntityIds($user);
    }

    public function canLink(User $user, int $entityId): bool
    {
        return in_array($entityId, $this->writableEntityIds($user), true)
            && OrganizationEntity::whereKey($entityId)->where('is_active', true)->exists();
    }

    /** @return array<int, int> */
    public function entityIdsOf(HatchProjectIntake $intake): array
    {
        return EntityDimensionBridge::linksForLinkables(self::LINKABLE_TYPES, [$intake->id], false)
            ->pluck('entity_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Verknüpft, wenn der User die Einheit beschreiben darf.
     * Scheitert das Verknüpfen, bleibt die Erhebung trotzdem bestehen (nur geloggt) –
     * nachholen lässt es sich im Panel „Verknüpfungen“.
     */
    public function link(HatchProjectIntake $intake, int $entityId, User $user): bool
    {
        try {
            if (!$this->canLink($user, $entityId)) {
                return false;
            }

            return EntityDimensionBridge::createLink($entityId, HatchProjectIntake::class, $intake->id, [
                'team_id' => $user->currentTeam?->id ?? $intake->team_id,
                'created_by_user_id' => $user->id,
            ]) !== null;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Übernimmt die Einheiten des Originals (nur beschreibbare). Liefert die Anzahl. */
    public function copy(HatchProjectIntake $from, HatchProjectIntake $to, User $user): int
    {
        try {
            $writable = array_flip($this->writableEntityIds($user));
            $count = 0;
            foreach ($this->entityIdsOf($from) as $entityId) {
                if (isset($writable[$entityId]) && $this->link($to, $entityId, $user)) {
                    $count++;
                }
            }

            return $count;
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }

    /**
     * Auswahl für den Anlege-Dialog: beschreibbare, aktive Einheiten, optional gesucht.
     *
     * @return array<int, array{id:int, name:string, path:string}>
     */
    public function candidates(User $user, string $search = '', int $limit = 8): array
    {
        $writable = $this->writableEntityIds($user);
        if ($writable === []) {
            return [];
        }

        $search = trim($search);
        $rows = OrganizationEntity::query()
            ->whereIn('id', $writable)
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%')))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'parent_entity_id']);

        $parents = OrganizationEntity::whereIn('id', $rows->pluck('parent_entity_id')->filter()->unique())
            ->pluck('name', 'id');

        return $rows->map(fn ($e) => [
            'id' => (int) $e->id,
            'name' => $e->name,
            'path' => (string) ($parents[$e->parent_entity_id] ?? ''),
        ])->all();
    }

    public function name(int $entityId): ?string
    {
        return OrganizationEntity::whereKey($entityId)->value('name');
    }
}
