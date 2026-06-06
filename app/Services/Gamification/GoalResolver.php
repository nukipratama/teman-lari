<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Enums\Rarity;
use App\Models\Activity;
use App\Models\PersonalRecord;
use App\Models\RunCard;
use App\Models\User;
use App\Models\UserUnlock;
use App\Models\WeeklySnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Computes goal progress for every unlock in the catalog. Each goal carries
 * its current progress, target value, and unit so the UI can render progress
 * bars without a dedicated DB table.
 *
 * @see config/temari_unlocks.php
 */
readonly class GoalResolver
{
    /**
     * @return list<array{id: string, title: string, description: string, slot: string, rarity: string, current: int|float, target: int|float, unit: string, is_completed: bool}>
     */
    public function forUser(User $user): array
    {
        $ctx = $this->gatherContext($user);
        /** @var list<string> $unlockedKeys */
        $unlockedKeys = array_values(UserUnlock::query()
            ->where('user_id', $user->id)
            ->pluck('unlock_key')
            ->all());

        return [
            ...$this->medalGoals($ctx, $unlockedKeys),
            ...$this->ikatKepalaGoals($ctx, $unlockedKeys),
            ...$this->pitaGoals($ctx, $unlockedKeys),
            ...$this->kausGoals($ctx, $unlockedKeys),
            ...$this->celanaGoals($ctx, $unlockedKeys),
            ...$this->sepatuGoals($ctx, $unlockedKeys),
            ...$this->auraGoals($ctx, $unlockedKeys),
        ];
    }

    /**
     * @return list<array{id: string, title: string, description: string, slot: string, rarity: string, current: int|float, target: int|float, unit: string, is_completed: bool}>
     */
    public function closestToCompletion(User $user, int $limit = 3): array
    {
        $goals = $this->forUser($user);

        $scored = array_map(function (array $goal): array {
            $pct = $goal['target'] > 0 ? $goal['current'] / $goal['target'] : 0;
            $capped = min($pct, 1.0);

            return ['goal' => $goal, 'pct' => $capped];
        }, $goals);

        usort($scored, function (array $a, array $b): int {
            if ($a['goal']['is_completed'] && ! $b['goal']['is_completed']) {
                return 1;
            }

            if (! $a['goal']['is_completed'] && $b['goal']['is_completed']) {
                return -1;
            }

            return $b['pct'] <=> $a['pct'];
        });

        return array_map(fn (array $s): array => $s['goal'], array_slice($scored, 0, $limit));
    }

    /**
     * @return array{user: User, prCount: int, activityCount: int, totalDistanceKm: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, fiveKPlus: int, halfMarathon: int, fastPace: int, badgeCounts: array<string, int>}
     */
    private function gatherContext(User $user): array
    {
        $prCount = PersonalRecord::query()->where('user_id', $user->id)->count();
        $activityCount = Activity::query()->where('user_id', $user->id)->count();

        $rarityCounts = RunCard::query()
            ->whereHas('activity', fn ($q) => $q->where('user_id', $user->id))
            ->select('rarity', DB::raw('COUNT(*) as cnt'))
            ->groupBy('rarity')
            ->pluck('cnt', 'rarity')
            ->all();

        $totalDistanceM = (float) Activity::query()
            ->where('user_id', $user->id)
            ->join('activity_details', 'activities.id', '=', 'activity_details.activity_id')
            ->sum('activity_details.distance');

        $streakWeeks = WeeklySnapshot::query()
            ->where('user_id', $user->id)
            ->where('runs', '>', 0)
            ->orderByDesc('week_ending')
            ->limit(4)
            ->count();

        $twoWeekStreak = WeeklySnapshot::query()
            ->where('user_id', $user->id)
            ->where('runs', '>', 0)
            ->orderByDesc('week_ending')
            ->limit(2)
            ->count();

        $tenKPlus = Activity::query()
            ->where('user_id', $user->id)
            ->whereHas('detail', fn ($q) => $q->where('distance', '>=', 10000))
            ->count();

        $fiveKPlus = Activity::query()
            ->where('user_id', $user->id)
            ->whereHas('detail', fn ($q) => $q->where('distance', '>=', 5000))
            ->count();

        $halfMarathon = Activity::query()
            ->where('user_id', $user->id)
            ->whereHas('detail', fn ($q) => $q->where('distance', '>=', 21000))
            ->count();

        $fastPace = Activity::query()
            ->where('user_id', $user->id)
            ->whereHas('detail', fn ($q) => $q->where('average_speed', '>=', 3.0))
            ->count();

        $badgeCounts = $this->gatherBadgeCounts($user);

        return [
            'user' => $user,
            'prCount' => $prCount,
            'activityCount' => $activityCount,
            'totalDistanceKm' => round($totalDistanceM / 1000, 1),
            'rarityCounts' => $rarityCounts,
            'streakWeeks' => $streakWeeks,
            'twoWeekStreak' => $twoWeekStreak,
            'tenKPlus' => $tenKPlus,
            'fiveKPlus' => $fiveKPlus,
            'halfMarathon' => $halfMarathon,
            'fastPace' => $fastPace,
            'badgeCounts' => $badgeCounts,
        ];
    }

    /** @return array<string, int> */
    private function gatherBadgeCounts(User $user): array
    {
        $badges = [
            RunCard::BADGE_ANAK_MALAM,
            RunCard::BADGE_ANAK_PAGI,
            RunCard::BADGE_PEJUANG_HUJAN,
            RunCard::BADGE_NEGATIVE_SPLIT,
            RunCard::BADGE_HARI_PANAS,
            RunCard::BADGE_Z2_MASTER,
        ];

        $counts = [];
        foreach ($badges as $badge) {
            $counts[$badge] = RunCard::query()
                ->whereHas('activity', fn ($q) => $q->where('user_id', $user->id))
                ->whereJsonContains('badges', $badge)
                ->count();
        }

        return $counts;
    }

    /**
     * @param  array{prCount: int, activityCount: int, totalDistanceKm: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, fiveKPlus: int, halfMarathon: int, fastPace: int, badgeCounts: array<string, int>}  $ctx
     * @param  list<string>  $unlockedKeys
     * @return list<array{id: string, title: string, description: string, slot: string, rarity: string, current: int|float, target: int|float, unit: string, is_completed: bool}>
     */
    private function medalGoals(array $ctx, array $unlockedKeys): array
    {
        $goals = [
            ['id' => 'accessory.medal_pertama', 'target' => 1, 'description' => 'Catat 1 PR di kategori apapun.'],
            ['id' => 'accessory.medal_emas', 'target' => 5, 'description' => 'Catat 5 PR total.'],
            ['id' => 'accessory.medal_perak', 'target' => 10, 'description' => 'Catat 10 PR total.'],
            ['id' => 'accessory.medal_platina', 'target' => 20, 'description' => 'Catat 20 PR total.'],
        ];

        return array_map(fn (array $g): array => [
            'id' => $g['id'],
            'title' => 'Catat PR ke-' . $g['target'],
            'description' => $g['description'],
            'slot' => 'medal',
            'rarity' => $this->rarityForKey($g['id']),
            'current' => min($ctx['prCount'], $g['target']),
            'target' => $g['target'],
            'unit' => 'PR',
            'is_completed' => \in_array($g['id'], $unlockedKeys, true),
        ], $goals);
    }

    /**
     * @param  array{prCount: int, activityCount: int, totalDistanceKm: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, fiveKPlus: int, halfMarathon: int, fastPace: int, badgeCounts: array<string, int>}  $ctx
     * @param  list<string>  $unlockedKeys
     * @return list<array{id: string, title: string, description: string, slot: string, rarity: string, current: int|float, target: int|float, unit: string, is_completed: bool}>
     */
    private function ikatKepalaGoals(array $ctx, array $unlockedKeys): array
    {
        $rc = $ctx['rarityCounts'];
        $items = [
            ['id' => 'accessory.ikat_kepala_berkesan', 'rarityValue' => Rarity::Uncommon->value, 'target' => 3, 'label' => 'Berkesan'],
            ['id' => 'accessory.ikat_kepala_langka', 'rarityValue' => Rarity::Rare->value, 'target' => 3, 'label' => 'Langka'],
            ['id' => 'accessory.ikat_kepala_epik', 'rarityValue' => Rarity::Epic->value, 'target' => 3, 'label' => 'Luar Biasa'],
            ['id' => 'accessory.ikat_kepala_legendaris', 'rarityValue' => Rarity::Legendary->value, 'target' => 1, 'label' => 'Legendaris'],
        ];

        return array_map(function (array $g) use ($rc, $unlockedKeys): array {
            $current = $rc[$g['rarityValue']] ?? 0;

            return [
                'id' => $g['id'],
                'title' => "Kumpulkan {$g['target']} kartu {$g['label']}",
                'description' => "Dapatkan {$g['target']} kartu {$g['label']}.",
                'slot' => 'ikat_kepala',
                'rarity' => $this->rarityForKey($g['id']),
                'current' => min($current, $g['target']),
                'target' => $g['target'],
                'unit' => 'kartu',
                'is_completed' => \in_array($g['id'], $unlockedKeys, true),
            ];
        }, $items);
    }

    /**
     * @param  array{prCount: int, activityCount: int, totalDistanceKm: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, fiveKPlus: int, halfMarathon: int, fastPace: int, badgeCounts: array<string, int>}  $ctx
     * @param  list<string>  $unlockedKeys
     * @return list<array{id: string, title: string, description: string, slot: string, rarity: string, current: int|float, target: int|float, unit: string, is_completed: bool}>
     */
    private function pitaGoals(array $ctx, array $unlockedKeys): array
    {
        $bc = $ctx['badgeCounts'];

        return [
            [
                'id' => 'accessory.pita_konsisten',
                'title' => '4 minggu beruntun lari',
                'description' => 'Lari di 4 minggu berturut-turut.',
                'slot' => 'pita',
                'rarity' => $this->rarityForKey('accessory.pita_konsisten'),
                'current' => min($ctx['streakWeeks'], 4),
                'target' => 4,
                'unit' => 'minggu',
                'is_completed' => \in_array('accessory.pita_konsisten', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.pita_jarak',
                'title' => 'Akumulasi jarak 100 km',
                'description' => 'Akumulasi jarak 100 km.',
                'slot' => 'pita',
                'rarity' => $this->rarityForKey('accessory.pita_jarak'),
                'current' => min($ctx['totalDistanceKm'], 100),
                'target' => 100,
                'unit' => 'km',
                'is_completed' => \in_array('accessory.pita_jarak', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.pita_malam',
                'title' => '5 lari malam',
                'description' => 'Selesaikan 5 lari malam (setelah jam 9 malam).',
                'slot' => 'pita',
                'rarity' => $this->rarityForKey('accessory.pita_malam'),
                'current' => min($bc[RunCard::BADGE_ANAK_MALAM] ?? 0, 5),
                'target' => 5,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.pita_malam', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.pita_maraton',
                'title' => 'Akumulasi jarak 500 km',
                'description' => 'Akumulasi jarak 500 km.',
                'slot' => 'pita',
                'rarity' => $this->rarityForKey('accessory.pita_maraton'),
                'current' => min($ctx['totalDistanceKm'], 500),
                'target' => 500,
                'unit' => 'km',
                'is_completed' => \in_array('accessory.pita_maraton', $unlockedKeys, true),
            ],
        ];
    }

    /**
     * @param  array{prCount: int, activityCount: int, totalDistanceKm: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, fiveKPlus: int, halfMarathon: int, fastPace: int, badgeCounts: array<string, int>}  $ctx
     * @param  list<string>  $unlockedKeys
     * @return list<array{id: string, title: string, description: string, slot: string, rarity: string, current: int|float, target: int|float, unit: string, is_completed: bool}>
     */
    private function kausGoals(array $ctx, array $unlockedKeys): array
    {
        $bc = $ctx['badgeCounts'];

        return [
            [
                'id' => 'accessory.kaus_pemula',
                'title' => 'Catat lari pertama',
                'description' => 'Catat 1 aktivitas lari.',
                'slot' => 'kaus',
                'rarity' => $this->rarityForKey('accessory.kaus_pemula'),
                'current' => min($ctx['activityCount'], 1),
                'target' => 1,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.kaus_pemula', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.kaus_pagi',
                'title' => '5 lari pagi',
                'description' => 'Selesaikan 5 lari pagi (sebelum jam 6).',
                'slot' => 'kaus',
                'rarity' => $this->rarityForKey('accessory.kaus_pagi'),
                'current' => min($bc[RunCard::BADGE_ANAK_PAGI] ?? 0, 5),
                'target' => 5,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.kaus_pagi', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.kaus_hujan',
                'title' => '3 lari pas hujan',
                'description' => 'Selesaikan 3 lari pas hujan.',
                'slot' => 'kaus',
                'rarity' => $this->rarityForKey('accessory.kaus_hujan'),
                'current' => min($bc[RunCard::BADGE_PEJUANG_HUJAN] ?? 0, 3),
                'target' => 3,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.kaus_hujan', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.kaus_legendaris',
                'title' => 'Catat 50 lari',
                'description' => 'Catat 50 aktivitas lari.',
                'slot' => 'kaus',
                'rarity' => $this->rarityForKey('accessory.kaus_legendaris'),
                'current' => min($ctx['activityCount'], 50),
                'target' => 50,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.kaus_legendaris', $unlockedKeys, true),
            ],
        ];
    }

    /**
     * @param  array{prCount: int, activityCount: int, totalDistanceKm: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, fiveKPlus: int, halfMarathon: int, fastPace: int, badgeCounts: array<string, int>}  $ctx
     * @param  list<string>  $unlockedKeys
     * @return list<array{id: string, title: string, description: string, slot: string, rarity: string, current: int|float, target: int|float, unit: string, is_completed: bool}>
     */
    private function celanaGoals(array $ctx, array $unlockedKeys): array
    {
        $bc = $ctx['badgeCounts'];

        return [
            [
                'id' => 'accessory.celana_ringan',
                'title' => 'Lari 5 km pertama',
                'description' => 'Catat 1 lari sejauh 5 km atau lebih.',
                'slot' => 'celana',
                'rarity' => $this->rarityForKey('accessory.celana_ringan'),
                'current' => min($ctx['fiveKPlus'], 1),
                'target' => 1,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.celana_ringan', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.celana_jarak',
                'title' => 'Lari 10 km pertama',
                'description' => 'Catat 1 lari sejauh 10 km atau lebih.',
                'slot' => 'celana',
                'rarity' => $this->rarityForKey('accessory.celana_jarak'),
                'current' => min($ctx['tenKPlus'], 1),
                'target' => 1,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.celana_jarak', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.celana_split',
                'title' => '3 negative split',
                'description' => 'Catat 3 lari negative split.',
                'slot' => 'celana',
                'rarity' => $this->rarityForKey('accessory.celana_split'),
                'current' => min($bc[RunCard::BADGE_NEGATIVE_SPLIT] ?? 0, 3),
                'target' => 3,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.celana_split', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.celana_maraton',
                'title' => 'Lari 21 km',
                'description' => 'Catat 1 lari sejauh 21 km atau lebih.',
                'slot' => 'celana',
                'rarity' => $this->rarityForKey('accessory.celana_maraton'),
                'current' => min($ctx['halfMarathon'], 1),
                'target' => 1,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.celana_maraton', $unlockedKeys, true),
            ],
        ];
    }

    /**
     * @param  array{prCount: int, activityCount: int, totalDistanceKm: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, fiveKPlus: int, halfMarathon: int, fastPace: int, badgeCounts: array<string, int>}  $ctx
     * @param  list<string>  $unlockedKeys
     * @return list<array{id: string, title: string, description: string, slot: string, rarity: string, current: int|float, target: int|float, unit: string, is_completed: bool}>
     */
    private function sepatuGoals(array $ctx, array $unlockedKeys): array
    {
        return [
            [
                'id' => 'accessory.sepatu_basic',
                'title' => 'Catat 10 lari',
                'description' => 'Catat 10 aktivitas lari.',
                'slot' => 'sepatu',
                'rarity' => $this->rarityForKey('accessory.sepatu_basic'),
                'current' => min($ctx['activityCount'], 10),
                'target' => 10,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.sepatu_basic', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.sepatu_cepat',
                'title' => 'Pace di bawah 5:30/km',
                'description' => 'Catat 1 lari dengan rata-rata pace di bawah 5:30/km.',
                'slot' => 'sepatu',
                'rarity' => $this->rarityForKey('accessory.sepatu_cepat'),
                'current' => min($ctx['fastPace'], 1),
                'target' => 1,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.sepatu_cepat', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.sepatu_tahan',
                'title' => '5 lari 10 km+',
                'description' => 'Catat 5 lari sejauh 10 km atau lebih.',
                'slot' => 'sepatu',
                'rarity' => $this->rarityForKey('accessory.sepatu_tahan'),
                'current' => min($ctx['tenKPlus'], 5),
                'target' => 5,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.sepatu_tahan', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.sepatu_legendaris',
                'title' => 'Akumulasi jarak 1000 km',
                'description' => 'Akumulasi jarak 1000 km.',
                'slot' => 'sepatu',
                'rarity' => $this->rarityForKey('accessory.sepatu_legendaris'),
                'current' => min($ctx['totalDistanceKm'], 1000),
                'target' => 1000,
                'unit' => 'km',
                'is_completed' => \in_array('accessory.sepatu_legendaris', $unlockedKeys, true),
            ],
        ];
    }

    /**
     * @param  array{prCount: int, activityCount: int, totalDistanceKm: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, fiveKPlus: int, halfMarathon: int, fastPace: int, badgeCounts: array<string, int>}  $ctx
     * @param  list<string>  $unlockedKeys
     * @return list<array{id: string, title: string, description: string, slot: string, rarity: string, current: int|float, target: int|float, unit: string, is_completed: bool}>
     */
    private function auraGoals(array $ctx, array $unlockedKeys): array
    {
        $bc = $ctx['badgeCounts'];
        $rc = $ctx['rarityCounts'];

        return [
            [
                'id' => 'accessory.aura_pemanasan',
                'title' => '2 minggu beruntun lari',
                'description' => 'Lari di 2 minggu berturut-turut.',
                'slot' => 'aura',
                'rarity' => $this->rarityForKey('accessory.aura_pemanasan'),
                'current' => min($ctx['twoWeekStreak'], 2),
                'target' => 2,
                'unit' => 'minggu',
                'is_completed' => \in_array('accessory.aura_pemanasan', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.aura_gerah',
                'title' => '3 lari pas gerah',
                'description' => 'Selesaikan 3 lari saat suhu di atas 31\u{00b0}C.',
                'slot' => 'aura',
                'rarity' => $this->rarityForKey('accessory.aura_gerah'),
                'current' => min($bc[RunCard::BADGE_HARI_PANAS] ?? 0, 3),
                'target' => 3,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.aura_gerah', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.aura_tenang',
                'title' => '5 lari HR Zone 2',
                'description' => 'Catat 5 lari di HR Zone 2 (bawah 70% HRmax).',
                'slot' => 'aura',
                'rarity' => $this->rarityForKey('accessory.aura_tenang'),
                'current' => min($bc[RunCard::BADGE_Z2_MASTER] ?? 0, 5),
                'target' => 5,
                'unit' => 'lari',
                'is_completed' => \in_array('accessory.aura_tenang', $unlockedKeys, true),
            ],
            [
                'id' => 'accessory.aura_jagoan',
                'title' => '3 kartu Legendaris',
                'description' => 'Dapatkan 3 kartu Legendaris.',
                'slot' => 'aura',
                'rarity' => $this->rarityForKey('accessory.aura_jagoan'),
                'current' => min($rc[Rarity::Legendary->value] ?? 0, 3),
                'target' => 3,
                'unit' => 'kartu',
                'is_completed' => \in_array('accessory.aura_jagoan', $unlockedKeys, true),
            ],
        ];
    }

    private function rarityForKey(string $key): string
    {
        $catalog = (array) config('temari_unlocks', []);

        return (string) ($catalog[$key]['rarity'] ?? 'common');
    }
}
