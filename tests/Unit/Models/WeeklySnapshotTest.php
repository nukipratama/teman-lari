<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\WeeklySnapshot;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('casts week_ending to a Carbon date and load metrics to floats', function (): void {
    $snap = WeeklySnapshot::factory()->create([
        'week_ending' => '2026-05-10',
        'weekly_trimp' => '459',
        'monotony' => '0.92',
        'strain' => '422.3',
        'runs' => '3',
    ]);

    expect($snap->week_ending)->toBeInstanceOf(Carbon::class)
        ->and($snap->week_ending->toDateString())->toBe('2026-05-10')
        ->and($snap->weekly_trimp)->toBeFloat()
        ->and($snap->monotony)->toBeFloat()->toEqualWithDelta(0.92, 0.001)
        ->and($snap->strain)->toBeFloat()->toEqualWithDelta(422.3, 0.01)
        ->and($snap->runs)->toBe(3);
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $snap = WeeklySnapshot::factory()->for($user)->create();

    expect($snap->user->is($user))->toBeTrue();
});

it('enforces one snapshot per (user_id, week_ending)', function (): void {
    $user = User::factory()->create();
    WeeklySnapshot::factory()->for($user)->create(['week_ending' => '2026-05-10']);

    expect(fn () => WeeklySnapshot::factory()->for($user)->create(['week_ending' => '2026-05-10']))
        ->toThrow(UniqueConstraintViolationException::class);
});
