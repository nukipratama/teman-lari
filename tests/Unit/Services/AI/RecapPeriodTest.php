<?php

declare(strict_types=1);

use App\Services\AI\RecapPeriod;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('returns the previous week ending (Sunday) as the last closed week', function (): void {
    Carbon::setTestNow('2026-06-17'); // Wednesday; current week ends 2026-06-21

    expect(RecapPeriod::lastClosedWeekEnding())->toBe('2026-06-14');
});

it('returns the previous month (Y-m) as the last closed month', function (): void {
    Carbon::setTestNow('2026-06-17');

    expect(RecapPeriod::lastClosedMonth())->toBe('2026-05');
});

it('does not overflow when last month has fewer days', function (): void {
    Carbon::setTestNow('2026-03-31');

    expect(RecapPeriod::lastClosedMonth())->toBe('2026-02');
});
