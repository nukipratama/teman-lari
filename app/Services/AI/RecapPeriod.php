<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Carbon;

/**
 * Boundaries of the latest fully-closed recap periods. Recaps and their chains
 * never narrate the still-running current week/month, so every "is this period
 * closed?" check caps at these values.
 */
final class RecapPeriod
{
    /**
     * The latest fully-closed week's `week_ending` (Sunday, ISO date string).
     */
    public static function lastClosedWeekEnding(): string
    {
        return Carbon::today()->subWeek()->endOfWeek(Carbon::SUNDAY)->startOfDay()->toDateString();
    }

    /**
     * The latest fully-closed month (Y-m).
     */
    public static function lastClosedMonth(): string
    {
        return Carbon::today()->subMonthNoOverflow()->format('Y-m');
    }
}
