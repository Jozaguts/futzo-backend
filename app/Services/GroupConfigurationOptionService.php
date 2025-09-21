<?php

namespace App\Services;

class GroupConfigurationOptionService
{
    private const MIN_GROUP_SIZE = 3;
    private const MAX_GROUP_SIZE = 6;
    private const MIN_GROUPS = 2;
    private const MAX_TOTAL_TEAMS = 36;

    private const ELIMINATION_STAGES = [
        [
            'teams' => 4,
            'label' => 'Semifinal',
            'phase_name' => 'Semifinales',
        ],
        [
            'teams' => 8,
            'label' => 'Cuartos',
            'phase_name' => 'Cuartos de Final',
        ],
        [
            'teams' => 16,
            'label' => 'Octavos',
            'phase_name' => 'Octavos de Final',
        ],
        [
            'teams' => 32,
            'label' => 'Dieciseisavos',
            'phase_name' => 'Dieciseisavos de Final',
        ],
    ];

    /**
     * Genera todas las opciones de configuración de grupos válidas para un total de equipos.
     */
    public function buildOptions(int $totalTeams): array
    {
        if ($totalTeams < self::MIN_GROUP_SIZE * self::MIN_GROUPS || $totalTeams > self::MAX_TOTAL_TEAMS) {
            return [];
        }

        $combinations = [];
        $this->generateCombinations($totalTeams, self::MAX_GROUP_SIZE, [], $combinations);

        $options = [];
        foreach ($combinations as $groupSizes) {
            $option = $this->buildOptionPayload($groupSizes);
            if ($option !== null) {
                $options[] = $option;
            }
        }

        usort($options, function (array $a, array $b) {
            $countComparison = count($a['group_sizes']) <=> count($b['group_sizes']);
            if ($countComparison !== 0) {
                return $countComparison;
            }

            $sizesA = $a['group_sizes'];
            $sizesB = $b['group_sizes'];
            $length = min(count($sizesA), count($sizesB));
            for ($i = 0; $i < $length; $i++) {
                if ($sizesA[$i] === $sizesB[$i]) {
                    continue;
                }

                // ordenar de mayor a menor dentro del mismo número de grupos
                return $sizesB[$i] <=> $sizesA[$i];
            }

            return count($sizesA) <=> count($sizesB);
        });

        return $options;
    }

    private function generateCombinations(int $remaining, int $maxSize, array $current, array &$results): void
    {
        if ($remaining === 0) {
            if (count($current) >= self::MIN_GROUPS) {
                $results[] = $current;
            }
            return;
        }

        $upper = min($maxSize, self::MAX_GROUP_SIZE, $remaining);
        for ($size = $upper; $size >= self::MIN_GROUP_SIZE; $size--) {
            if ($size > $remaining) {
                continue;
            }

            $nextRemaining = $remaining - $size;
            if ($nextRemaining > 0 && $nextRemaining < self::MIN_GROUP_SIZE) {
                continue;
            }

            $current[] = $size;
            $this->generateCombinations($nextRemaining, $size, $current, $results);
            array_pop($current);
        }
    }

    private function buildOptionPayload(array $groupSizes): ?array
    {
        rsort($groupSizes, SORT_NUMERIC);
        $groups = count($groupSizes);
        $advanceTopN = 2;
        $baseQualifiers = $groups * $advanceTopN;

        $stage = $this->resolveStage($groups, $baseQualifiers);
        if ($stage === null) {
            return null;
        }

        $neededBestThirds = $stage['teams'] - $baseQualifiers;
        if ($neededBestThirds < 0 || $neededBestThirds > $groups) {
            return null;
        }

        $includeBestThirds = $neededBestThirds > 0;

        $groupPhasePayload = [
            'teams_per_group' => max($groupSizes),
            'advance_top_n' => $advanceTopN,
            'include_best_thirds' => $includeBestThirds,
            'best_thirds_count' => $includeBestThirds ? $neededBestThirds : null,
            'group_sizes' => $groupSizes,
        ];

        return [
            'id' => $this->buildOptionId($groupSizes, $stage['teams']),
            'groups' => $groups,
            'group_sizes' => $groupSizes,
            'group_phase' => $groupPhasePayload,
            'elimination' => [
                'teams' => $stage['teams'],
                'label' => $stage['label'],
                'phase_name' => $stage['phase_name'],
            ],
        ];
    }

    private function resolveStage(int $groups, int $baseQualifiers): ?array
    {
        foreach (self::ELIMINATION_STAGES as $stage) {
            if ($baseQualifiers > $stage['teams']) {
                continue;
            }

            $needed = $stage['teams'] - $baseQualifiers;
            if ($needed === 0 || $needed <= $groups) {
                return $stage;
            }
        }

        return null;
    }

    private function buildOptionId(array $groupSizes, int $stageTeams): string
    {
        return implode('-', $groupSizes) . '|' . $stageTeams;
    }
}
