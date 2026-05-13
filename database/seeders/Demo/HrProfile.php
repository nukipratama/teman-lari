<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

enum HrProfile: string
{
    case Z2Steady = 'z2_steady';
    case Tempo = 'tempo';
    case Intervals = 'intervals';
    case LsdDrift = 'lsd_drift';
    case NegSplit = 'neg_split';

    public function velocityMultiplier(float $progress, bool $intervalWork): float
    {
        return match ($this) {
            self::NegSplit => $progress < 0.5 ? 0.96 : 1.07,
            self::Intervals => $intervalWork ? 1.30 : 0.70,
            self::LsdDrift => 1.04 - 0.08 * $progress,
            self::Tempo, self::Z2Steady => 1.0,
        };
    }

    public function hrBase(float $progress, bool $intervalWork): float
    {
        return match ($this) {
            self::Z2Steady => 148.0,
            self::Tempo => 164.0,
            self::Intervals => $intervalWork ? 174.0 : 138.0,
            self::LsdDrift => 145.0 + 22.0 * $progress,
            self::NegSplit => $progress < 0.5 ? 150.0 : 162.0 + 12.0 * ($progress - 0.5) * 2,
        };
    }

    public function cadenceDrift(float $progress): float
    {
        return match ($this) {
            self::LsdDrift => -3.0 * $progress,
            self::NegSplit => 4.0 * $progress,
            self::Intervals => 0.0,
            self::Tempo, self::Z2Steady => -1.0 * $progress,
        };
    }
}
