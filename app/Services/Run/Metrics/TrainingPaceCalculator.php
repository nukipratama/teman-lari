<?php

declare(strict_types=1);

namespace App\Services\Run\Metrics;

/**
 * Daniels-style training paces derived from a VDOT value.
 *
 * Reuses the VO2 <-> velocity relationship from VdotEstimator:
 *   VO2 = -4.60 + 0.182258·v + 0.000104·v²   (v in m/min)
 * Each training zone targets a fraction of VDOT as its VO2 (%VO2max intensity);
 * solving the quadratic for v gives that zone's velocity, converted to sec/km.
 * Fractions are calibrated against Daniels' published training-pace tables.
 */
class TrainingPaceCalculator
{
    private const float EASY_LOW_FRACTION = 0.72;

    private const float EASY_HIGH_FRACTION = 0.80;

    private const float MARATHON_FRACTION = 0.86;

    private const float THRESHOLD_FRACTION = 0.95;

    private const float INTERVAL_FRACTION = 1.03;

    /**
     * @return array{easy: int, marathon: int, threshold: int, interval: int} seconds per kilometre
     */
    public function fromVdot(float $vdot): array
    {
        $easyLowPace = $this->paceFromVo2Fraction($vdot, self::EASY_LOW_FRACTION);
        $easyHighPace = $this->paceFromVo2Fraction($vdot, self::EASY_HIGH_FRACTION);

        return [
            'easy' => (int) round(($easyLowPace + $easyHighPace) / 2),
            'marathon' => (int) round($this->paceFromVo2Fraction($vdot, self::MARATHON_FRACTION)),
            'threshold' => (int) round($this->paceFromVo2Fraction($vdot, self::THRESHOLD_FRACTION)),
            'interval' => (int) round($this->paceFromVo2Fraction($vdot, self::INTERVAL_FRACTION)),
        ];
    }

    /**
     * Seconds per kilometre for the velocity that produces VO2 = $fraction * $vdot.
     */
    private function paceFromVo2Fraction(float $vdot, float $fraction): float
    {
        $vo2 = $fraction * $vdot;

        $a = 0.000104;
        $b = 0.182258;
        $c = -(4.60 + $vo2);

        $velocity = (-$b + sqrt($b ** 2 - 4 * $a * $c)) / (2 * $a); // m/min

        return 60_000 / $velocity;
    }
}
