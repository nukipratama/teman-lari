<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\ActivityDetail;
use App\Models\User;
use App\Services\Run\Metrics\TrainingLoad;
use App\Services\Run\Story\Briefing;
use App\Services\Run\Story\Temari;
use App\Services\Run\Story\Vibe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-05-11 12:00:00'));
afterEach(fn () => Carbon::setTestNow());

it('produces a hibernating briefing with mata-ngantuk for a user with no runs', function (): void {
    $user = User::factory()->create();

    $briefing = app(Briefing::class)->generate($user);

    expect($briefing->vibeState)->toBe(Vibe::HIBERNATING)
        ->and($briefing->vibeLabel)->toBe('Hibernasi')
        ->and($briefing->mood)->toBe(Temari::MOOD_DIM)
        ->and($briefing->accessory)->toBe('mata-ngantuk')
        ->and($briefing->sigilPattern)->toBe('dddd')
        ->and($briefing->recoveryLabel)->toBe('Form belum kebaca')
        ->and($briefing->recoveryTone)->toBe('neutral')
        ->and($briefing->streakLabel)->toBeNull()
        ->and($briefing->suggestionLine)->toBe('Rencana: saatnya keluar pintu lagi. Easy 3K aja.');
});

it('reports "Lari hari ini" when there was a run today', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today(),
        'trimp_edwards' => 60.0,
    ]);

    expect(app(Briefing::class)->generate($user)->streakLabel)->toBe('Lari hari ini');
});

it('reports "Kemarin lari" at 1 day', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today()->subDay(),
        'trimp_edwards' => 60.0,
    ]);

    expect(app(Briefing::class)->generate($user)->streakLabel)->toBe('Kemarin lari');
});

it('escalates the streak label past 4 days', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today()->subDays(6),
        'trimp_edwards' => 60.0,
    ]);

    expect(app(Briefing::class)->generate($user)->streakLabel)->toBe('Sudah 6 hari nih');
});

it('overrides the suggestion when the user has been away 5+ days', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today()->subDays(7),
        'trimp_edwards' => 60.0,
    ]);

    expect(app(Briefing::class)->generate($user)->suggestionLine)
        ->toBe('Sudah lama nggak lari — keluar dulu yuk, easy 3K aja.');
});

it('maps each vibe state to the right mood + accessory + sigil', function (string $vibe, string $expectedMood, ?string $expectedAccessory, string $expectedSigil): void {
    $user = User::factory()->create();

    $this->mock(Vibe::class, function (MockInterface $m) use ($vibe): void {
        $m->shouldReceive('current')->andReturn($vibe);
    });
    $this->mock(TrainingLoad::class, function (MockInterface $m): void {
        $m->shouldReceive('summary')->andReturn(['form' => 5.0, 'form_status' => 'optimal']);
    });

    $briefing = app(Briefing::class)->generate($user);

    expect($briefing->mood)->toBe($expectedMood)
        ->and($briefing->accessory)->toBe($expectedAccessory)
        ->and($briefing->sigilPattern)->toBe($expectedSigil)
        ->and($briefing->vibeLabel)->toBe(Vibe::label($vibe))
        ->and($briefing->headlineLine)->toContain(Vibe::label($vibe));
})->with([
    [Vibe::PUMPED, Temari::MOOD_GLOW, 'headband', 'ssss'],
    [Vibe::FRESH, Temari::MOOD_GLOW, 'headband', 'ssss'],
    [Vibe::BOUNCY, Temari::MOOD_BOUNCY, 'pita', 'orct'],
    [Vibe::WORN_DOWN, Temari::MOOD_WOBBLE, null, 'wvwv'],
    [Vibe::COOKED, Temari::MOOD_SQUISHED, null, 'fhfh'],
    [Vibe::STRETCHED_THIN, Temari::MOOD_SPINNING, null, 'splr'],
    [Vibe::HIBERNATING, Temari::MOOD_DIM, 'mata-ngantuk', 'dddd'],
    [Vibe::STEADY, Temari::MOOD_DIM, 'mata-ngantuk', 'dddd'],
]);

it('reflects form_status in the recovery tone + label', function (string $formStatus, string $expectedTone, string $expectedLabel): void {
    $user = User::factory()->create();

    $this->mock(Vibe::class, function (MockInterface $m): void {
        $m->shouldReceive('current')->andReturn(Vibe::STEADY);
    });
    $this->mock(TrainingLoad::class, function (MockInterface $m) use ($formStatus): void {
        $m->shouldReceive('summary')->andReturn(['form' => 0.0, 'form_status' => $formStatus]);
    });

    $briefing = app(Briefing::class)->generate($user);

    expect($briefing->recoveryTone)->toBe($expectedTone)
        ->and($briefing->recoveryLabel)->toBe($expectedLabel);
})->with([
    ['fresh', 'positive', 'Form Fresh'],
    ['optimal', 'neutral', 'Form Optimal'],
    ['fatigued', 'warning', 'Lelah'],
    ['overreaching', 'alert', 'Overreaching'],
]);
