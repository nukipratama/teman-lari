<?php

declare(strict_types=1);

namespace App\Services\Run\Metrics;

use App\Models\PersonalRecord;
use App\Models\User;

/**
 * Daniels' VDOT formula (1998 tables):
 *   v     = distance_m / time_min                                          (m/min)
 *   VO2   = -4.60 + 0.182258·v + 0.000104·v²                              (ml/kg/min)
 *   pmax  = 0.80 + 0.1894393·e^(-0.012778·t) + 0.2989558·e^(-0.1932605·t) (fraction of VO2max sustainable for t min)
 *   VDOT  = VO2 / pmax
 * Skipping pmax underestimates marathon VDOT by ~10 points.
 */
class VdotEstimator
{
    /** PR category → distance meters used in the Daniels velocity calc. */
    private const array CATEGORY_TO_METERS = [
        'marathon' => 42_195.0,
        'half_marathon' => 21_097.5,
        '15km' => 15_000.0,
        '10km' => 10_000.0,
        '5km' => 5_000.0,
    ];

    /**
     * @return array{vdot: float, source_category: string}|null
     */
    public function estimate(User $user): ?array
    {
        /** @var array<string, PersonalRecord> $prs */
        $prs = PersonalRecord::query()
            ->where('user_id', $user->id)
            ->whereIn('category', array_keys(self::CATEGORY_TO_METERS))
            ->get()
            ->keyBy('category')
            ->all();

        $best = null;
        $bestVdot = null;

        foreach (self::CATEGORY_TO_METERS as $category => $distance) {
            $pr = $prs[$category] ?? null;
            if ($pr === null) {
                continue;
            }
            $vdot = $this->vdotFromTimeAndDistance($pr->value_sec, $distance);
            if ($vdot === null) {
                continue;
            }
            // Daniels' formula is distance-normalized; max VDOT wins.
            if ($bestVdot === null || $vdot > $bestVdot) {
                $bestVdot = $vdot;
                $best = $category;
            }
        }

        if ($bestVdot === null || $best === null) {
            return null;
        }

        return ['vdot' => round($bestVdot, 1), 'source_category' => $best];
    }

    public function vdotFromTimeAndDistance(float $elapsedSec, float $distanceMeters): ?float
    {
        if ($elapsedSec <= 0 || $distanceMeters <= 0) {
            return null;
        }
        $timeMin = $elapsedSec / 60.0;
        $velocity = $distanceMeters / $timeMin; // m/min

        $vo2 = -4.60 + 0.182258 * $velocity + 0.000104 * $velocity * $velocity;

        // pmax is mathematically always > 0.8 (both exponential terms are positive),
        // so no defensive divide-by-zero check is needed here.
        $pmax = 0.80
            + 0.1894393 * exp(-0.012778 * $timeMin)
            + 0.2989558 * exp(-0.1932605 * $timeMin);

        return $vo2 > 0 ? $vo2 / $pmax : null;
    }
}
