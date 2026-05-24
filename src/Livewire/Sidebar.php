<?php

namespace Platform\Hatch\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Organization\Services\EntityDimensionBridge;
use Platform\Organization\Models\OrganizationEntity;

class Sidebar extends Component
{
    public bool $showAllIntakes = false;

    public function mount()
    {
        $this->showAllIntakes = false;
    }

    #[On('updateSidebar')]
    public function updateSidebar()
    {
    }

    public function toggleShowAllIntakes()
    {
        $this->showAllIntakes = !$this->showAllIntakes;
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return view('hatch::livewire.sidebar', [
                'entityTypeGroups' => collect(),
                'unlinkedIntakes' => collect(),
                'hasMoreIntakes' => false,
            ])->layout('platform::layouts.app');
        }

        // 1. Erhebungen laden
        $myIntakes = HatchProjectIntake::query()
            ->where('team_id', $teamId)
            ->where(function ($query) use ($user) {
                $query->where('owned_by_user_id', $user->id)
                      ->orWhere('created_by_user_id', $user->id);
            })
            ->orderBy('name')
            ->get();

        $allIntakes = HatchProjectIntake::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get();

        $intakesToShow = $this->showAllIntakes ? $allIntakes : $myIntakes;
        $hasMoreIntakes = $allIntakes->count() > $myIntakes->count();

        // 2. Entity-Verknüpfungen laden via DimensionLink
        $intakeIds = $intakesToShow->pluck('id')->toArray();

        $entityIntakeMap = []; // entity_id => [intake_ids]
        $linkedIntakeIds = [];

        // Morph-Varianten
        $contextMorphTypes = ['hatch_project_intake', HatchProjectIntake::class];

        $entityLinks = EntityDimensionBridge::linksForLinkables($contextMorphTypes, $intakeIds);

        foreach ($entityLinks as $link) {
            $entityId = $link->entity_id;
            $intakeId = $link->linkable_id;
            $entityIntakeMap[$entityId][] = $intakeId;
            $linkedIntakeIds[] = $intakeId;
        }

        // Deduplizieren
        foreach ($entityIntakeMap as $entityId => $ids) {
            $entityIntakeMap[$entityId] = array_unique($ids);
        }
        $linkedIntakeIds = array_unique($linkedIntakeIds);

        // 2c. Aufwärts-Traversierung: Ancestors ins Entity-Set aufnehmen
        $directEntityIds = array_keys($entityIntakeMap);
        if (!empty($directEntityIds)) {
            $directEntities = OrganizationEntity::with(['allParents.type'])
                ->whereIn('id', $directEntityIds)
                ->get()
                ->keyBy('id');

            foreach ($directEntities as $entityId => $entity) {
                $ancestor = $entity->allParents;
                while ($ancestor) {
                    if (!isset($entityIntakeMap[$ancestor->id])) {
                        $entityIntakeMap[$ancestor->id] = [];
                    }
                    $ancestor = $ancestor->allParents;
                }
            }
        }

        // 3. Gruppieren: EntityType → Entity-Baum → Erhebungen
        $entityTypeGroups = collect();

        $entityIds = array_keys($entityIntakeMap);
        if (!empty($entityIds)) {
            $entities = OrganizationEntity::with('type')
                ->whereIn('id', $entityIds)
                ->get()
                ->keyBy('id');

            // Eltern-Kind-Beziehungen
            $entityChildrenMap = [];
            $rootEntityIds = [];

            foreach ($entities as $entity) {
                $parentId = $entity->parent_entity_id;
                if ($parentId && $entities->has($parentId)) {
                    $entityChildrenMap[$parentId][] = $entity->id;
                } else {
                    $rootEntityIds[] = $entity->id;
                }
            }

            // Rekursiver Baum-Builder
            $buildTree = function (int $entityId) use (&$buildTree, $entities, $entityChildrenMap, $entityIntakeMap, $intakesToShow): ?array {
                $entity = $entities->get($entityId);
                if (!$entity) {
                    return null;
                }

                $childIds = $entityChildrenMap[$entityId] ?? [];
                $childNodes = collect($childIds)
                    ->map(fn ($childId) => $buildTree($childId))
                    ->filter();

                // Kinder nach EntityType gruppieren
                $childrenByType = $childNodes
                    ->groupBy(fn ($child) => $child['type_id'])
                    ->map(function ($group) use ($entities) {
                        $firstChild = $group->first();
                        $typeEntity = $entities->get($firstChild['entity_id']);
                        $type = $typeEntity?->type;

                        return [
                            'type_id' => $firstChild['type_id'],
                            'type_name' => $type?->name ?? 'Sonstige',
                            'type_icon' => $type?->icon ?? null,
                            'sort_order' => $type?->sort_order ?? 999,
                            'children' => $group->sortBy('entity_name')->values(),
                        ];
                    })
                    ->sortBy('sort_order')
                    ->values();

                $intakes = collect($entityIntakeMap[$entityId] ?? [])
                    ->map(fn ($iid) => $intakesToShow->firstWhere('id', $iid))
                    ->filter()
                    ->values();

                // Gesamtzahl Erhebungen (eigene + aller Kinder)
                $totalIntakes = $intakes->count();
                foreach ($childNodes as $child) {
                    $totalIntakes += $child['total_intakes'];
                }

                if ($totalIntakes === 0) {
                    return null;
                }

                return [
                    'entity_id' => $entityId,
                    'entity_name' => $entity->name,
                    'type_id' => $entity->type?->id,
                    'intakes' => $intakes,
                    'children_by_type' => $childrenByType,
                    'total_intakes' => $totalIntakes,
                ];
            };

            // Root-Entities nach Typ gruppieren
            $groupedByType = [];
            foreach ($rootEntityIds as $entityId) {
                $entity = $entities->get($entityId);
                if (!$entity || !$entity->type) {
                    continue;
                }

                $tree = $buildTree($entityId);
                if (!$tree) {
                    continue;
                }

                $typeId = $entity->type->id;
                if (!isset($groupedByType[$typeId])) {
                    $groupedByType[$typeId] = [
                        'type_id' => $typeId,
                        'type_name' => $entity->type->name,
                        'type_icon' => $entity->type->icon,
                        'sort_order' => $entity->type->sort_order ?? 999,
                        'entities' => [],
                    ];
                }
                $groupedByType[$typeId]['entities'][] = $tree;
            }

            $entityTypeGroups = collect($groupedByType)
                ->sortBy('sort_order')
                ->map(function ($group) {
                    $group['entities'] = collect($group['entities'])
                        ->sortBy('entity_name')
                        ->values();
                    return $group;
                })
                ->values();
        }

        // 4. Unverknüpfte Erhebungen
        $unlinkedIntakes = $intakesToShow->filter(function ($intake) use ($linkedIntakeIds) {
            return !in_array($intake->id, $linkedIntakeIds);
        })->values();

        return view('hatch::livewire.sidebar', [
            'entityTypeGroups' => $entityTypeGroups,
            'unlinkedIntakes' => $unlinkedIntakes,
            'hasMoreIntakes' => $hasMoreIntakes,
        ])->layout('platform::layouts.app');
    }
}
