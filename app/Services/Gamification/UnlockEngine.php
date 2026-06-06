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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Recomputes eligible unlocks for a user and persists new ones. Idempotent:
 * existing unlock_key rows are left alone, so calling this on every event is
 * safe.
 *
 * Each criterion reads from materialized data (badges, PR rows, weekly
 * snapshots, activity_details) rather than re-computing thresholds, so the
 * engine stays cheap even as the catalog grows.
 *
 * @return list<string> unlock keys newly granted in this call
 */
class UnlockEngine
{
    /** @var list<string> */
    private const array ALL_KEYS = [
        'accessory.medal_pertama',
        'accessory.medal_emas',
        'accessory.medal_perak',
        'accessory.medal_platina',
        'accessory.ikat_kepala_berkesan',
        'accessory.ikat_kepala_langka',
        'accessory.ikat_kepala_epik',
        'accessory.ikat_kepala_legendaris',
        'accessory.pita_konsisten',
        'accessory.pita_jarak',
        'accessory.pita_malam',
        'accessory.pita_maraton',
        'accessory.kaus_pemula',
        'accessory.kaus_pagi',
        'accessory.kaus_hujan',
        'accessory.kaus_legendaris',
        'accessory.celana_ringan',
        'accessory.celana_jarak',
        'accessory.celana_split',
        'accessory.celana_maraton',
        'accessory.sepatu_basic',
        'accessory.sepatu_cepat',
        'accessory.sepatu_tahan',
        'accessory.sepatu_legendaris',
        'accessory.aura_pemanasan',
        'accessory.aura_gerah',
        'accessory.aura_tenang',
        'accessory.aura_jagoan',
    ];

    /** Keys that trigger the full-screen unlock takeover instead of the toast. */
    private const array MAJOR_KEYS = [
        'accessory.ikat_kepala_legendaris',
        'accessory.kaus_legendaris',
        'accessory.sepatu_legendaris',
        'accessory.aura_jagoan',
    ];

    /** @return list<string> */
    public function grantEligible(User $user): array
    {
        $already = UserUnlock::query()
            ->where('user_id', $user->id)
            ->pluck('unlock_key')
            ->all();

        // Once every defined accessory is unlocked, skip the eligibility
        // queries — they're moot.
        if (count(array_diff(self::ALL_KEYS, $already)) === 0) {
            return [];
        }

        $eligible = $this->computeEligible($user);
        $new = array_values(array_diff($eligible, $already));

        if ($new === []) {
            return [];
        }

        $now = Carbon::now();
        $rows = array_map(fn (string $key): array => [
            'user_id' => $user->id,
            'unlock_key' => $key,
            'unlocked_at' => $now,
            'metadata' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $new);

        UserUnlock::query()->insert($rows);

        // Flash the first new unlock for the toast on the next request. Only
        // do this when a session is active — background jobs / CLI ingests
        // don't have one and would crash here.
        if (Session::isStarted()) {
            $firstKey = $new[0];
            $catalog = config('temari_unlocks', []);
            $def = is_array($catalog) ? ($catalog[$firstKey] ?? null) : null;
            if (is_array($def)) {
                Session::flash('unlock', [
                    'unlock_key' => $firstKey,
                    'name' => $def['name'] ?? $firstKey,
                    'icon' => $def['icon'] ?? 'mdi:medal',
                    'is_major' => \in_array($firstKey, self::MAJOR_KEYS, true),
                ]);
            }
        }

        return $new;
    }

    /**
     * @return list<string>
     */
    private function computeEligible(User $user): array
    {
        $ctx = $this->gatherContext($user);

        return [
            ...$this->eligibleMedal($ctx),
            ...$this->eligibleIkatKepala($ctx),
            ...$this->eligiblePita($ctx),
            ...$this->eligibleKaus($ctx),
            ...$this->eligibleCelana($ctx),
            ...$this->eligibleSepatu($ctx),
            ...$this->eligibleAura($ctx),
        ];
    }

    /**
     * @return array{user: User, prCount: int, activityCount: int, totalDistanceM: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, fiveKPlus: int, halfMarathon: int, fastPace: int, badgeCounts: array<string, int>}
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
            'totalDistanceM' => $totalDistanceM,
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

    /**
     * Batch badge counts for all badges used by the unlock criteria.
     * One grouped query instead of N per-badge queries.
     *
     * @return array<string, int>
     */
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
     * @param  array{prCount: int, activityCount: int, totalDistanceM: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int, user: User}  $ctx
     * @return list<string>
     */
    private function eligibleMedal(array $ctx): array
    {
        $keys = [];
        if ($ctx['prCount'] >= 1) {
            $keys[] = 'accessory.medal_pertama';
        }
        if ($ctx['prCount'] >= 5) {
            $keys[] = 'accessory.medal_emas';
        }
        if ($ctx['prCount'] >= 10) {
            $keys[] = 'accessory.medal_perak';
        }
        if ($ctx['prCount'] >= 20) {
            $keys[] = 'accessory.medal_platina';
        }

        return $keys;
    }

    /**
     * @param  array{user: User, prCount: int, activityCount: int, totalDistanceM: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int}  $ctx
     * @return list<string>
     */
    private function eligibleIkatKepala(array $ctx): array
    {
        $keys = [];
        $rc = $ctx['rarityCounts'];

        if (($rc[Rarity::Uncommon->value] ?? 0) >= 3) {
            $keys[] = 'accessory.ikat_kepala_berkesan';
        }
        if (($rc[Rarity::Rare->value] ?? 0) >= 3) {
            $keys[] = 'accessory.ikat_kepala_langka';
        }
        if (($rc[Rarity::Epic->value] ?? 0) >= 3) {
            $keys[] = 'accessory.ikat_kepala_epik';
        }
        if (($rc[Rarity::Legendary->value] ?? 0) >= 1) {
            $keys[] = 'accessory.ikat_kepala_legendaris';
        }

        return $keys;
    }

    /**
     * @param  array{user: User, prCount: int, activityCount: int, totalDistanceM: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int}  $ctx
     * @return list<string>
     */
    private function eligiblePita(array $ctx): array
    {
        $keys = [];

        if ($ctx['streakWeeks'] >= 4) {
            $keys[] = 'accessory.pita_konsisten';
        }
        if ($ctx['totalDistanceM'] >= 100_000) {
            $keys[] = 'accessory.pita_jarak';
        }
        if ($ctx['totalDistanceM'] >= 500_000) {
            $keys[] = 'accessory.pita_maraton';
        }
        if (($ctx['badgeCounts'][RunCard::BADGE_ANAK_MALAM] ?? 0) >= 5) {
            $keys[] = 'accessory.pita_malam';
        }

        return $keys;
    }

    /**
     * @param  array{user: User, prCount: int, activityCount: int, totalDistanceM: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int}  $ctx
     * @return list<string>
     */
    private function eligibleKaus(array $ctx): array
    {
        $keys = [];

        if ($ctx['activityCount'] >= 1) {
            $keys[] = 'accessory.kaus_pemula';
        }
        if (($ctx['badgeCounts'][RunCard::BADGE_ANAK_PAGI] ?? 0) >= 5) {
            $keys[] = 'accessory.kaus_pagi';
        }
        if (($ctx['badgeCounts'][RunCard::BADGE_PEJUANG_HUJAN] ?? 0) >= 3) {
            $keys[] = 'accessory.kaus_hujan';
        }
        if ($ctx['activityCount'] >= 50) {
            $keys[] = 'accessory.kaus_legendaris';
        }

        return $keys;
    }

    /**
     * @param  array{user: User, prCount: int, activityCount: int, totalDistanceM: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int}  $ctx
     * @return list<string>
     */
    private function eligibleCelana(array $ctx): array
    {
        $keys = [];

        if ($ctx['fiveKPlus'] >= 1) {
            $keys[] = 'accessory.celana_ringan';
        }
        if ($ctx['tenKPlus'] >= 1) {
            $keys[] = 'accessory.celana_jarak';
        }
        if (($ctx['badgeCounts'][RunCard::BADGE_NEGATIVE_SPLIT] ?? 0) >= 3) {
            $keys[] = 'accessory.celana_split';
        }
        if ($ctx['halfMarathon'] >= 1) {
            $keys[] = 'accessory.celana_maraton';
        }

        return $keys;
    }

    /**
     * @param  array{user: User, prCount: int, activityCount: int, totalDistanceM: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int}  $ctx
     * @return list<string>
     */
    private function eligibleSepatu(array $ctx): array
    {
        $keys = [];

        if ($ctx['activityCount'] >= 10) {
            $keys[] = 'accessory.sepatu_basic';
        }

        if ($ctx['fastPace'] >= 1) {
            $keys[] = 'accessory.sepatu_cepat';
        }

        if ($ctx['tenKPlus'] >= 5) {
            $keys[] = 'accessory.sepatu_tahan';
        }
        if ($ctx['totalDistanceM'] >= 1_000_000) {
            $keys[] = 'accessory.sepatu_legendaris';
        }

        return $keys;
    }

    /**
     * @param  array{user: User, prCount: int, activityCount: int, totalDistanceM: float, rarityCounts: array<string, int>, streakWeeks: int, twoWeekStreak: int, tenKPlus: int}  $ctx
     * @return list<string>
     */
    private function eligibleAura(array $ctx): array
    {
        $keys = [];

        if ($ctx['twoWeekStreak'] >= 2) {
            $keys[] = 'accessory.aura_pemanasan';
        }
        if (($ctx['badgeCounts'][RunCard::BADGE_HARI_PANAS] ?? 0) >= 3) {
            $keys[] = 'accessory.aura_gerah';
        }
        if (($ctx['badgeCounts'][RunCard::BADGE_Z2_MASTER] ?? 0) >= 5) {
            $keys[] = 'accessory.aura_tenang';
        }
        if (($ctx['rarityCounts'][Rarity::Legendary->value] ?? 0) >= 3) {
            $keys[] = 'accessory.aura_jagoan';
        }

        return $keys;
    }
}
