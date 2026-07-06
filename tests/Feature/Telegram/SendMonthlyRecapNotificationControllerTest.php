<?php

declare(strict_types=1);

use App\Jobs\Telegram\SendTelegramNotificationJob;
use App\Models\User;
use App\Services\AI\AnalysisType;
use App\Support\Cooldown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('requires authentication', function (): void {
    $this->post(route('rekap.bulanan.telegram', ['month' => '2026-06']))->assertRedirect(route('login'));
});

it('force-dispatches the push when the monthly recap is done', function (): void {
    Bus::fake();
    $user = User::factory()->create();
    $analysis = doneAnalysisFor(AnalysisType::MONTHLY_RECAP_SUBJECT_TYPE, $user->id, AnalysisType::MonthlyRecap, '2026-06', content: 'Bulan ini 120 km.');

    $this->actingAs($user)
        ->post(route('rekap.bulanan.telegram', ['month' => '2026-06']))
        ->assertRedirect()
        ->assertSessionHas('success');

    Bus::assertDispatched(
        SendTelegramNotificationJob::class,
        fn (SendTelegramNotificationJob $job): bool => $job->analysisId === $analysis->id && $job->force === true,
    );
});

it('does not re-dispatch and flashes info while the send cooldown is active', function (): void {
    Bus::fake();
    $user = User::factory()->create();
    $analysis = doneAnalysisFor(AnalysisType::MONTHLY_RECAP_SUBJECT_TYPE, $user->id, AnalysisType::MonthlyRecap, '2026-06', content: 'Bulan ini 120 km.');
    RateLimiter::hit(Cooldown::telegramKey($analysis->id), Cooldown::WINDOW_SECONDS);

    $this->actingAs($user)
        ->post(route('rekap.bulanan.telegram', ['month' => '2026-06']))
        ->assertRedirect()
        ->assertSessionHas('info');

    Bus::assertNotDispatched(SendTelegramNotificationJob::class);
});

it('does not dispatch and flashes info when the recap is not ready', function (): void {
    Bus::fake();
    $user = User::factory()->create();
    doneAnalysisFor(AnalysisType::MONTHLY_RECAP_SUBJECT_TYPE, $user->id, AnalysisType::MonthlyRecap, '2026-06', done: false, content: 'Bulan ini 120 km.');

    $this->actingAs($user)
        ->post(route('rekap.bulanan.telegram', ['month' => '2026-06']))
        ->assertRedirect()
        ->assertSessionHas('info');

    Bus::assertNotDispatched(SendTelegramNotificationJob::class);
});

it('does not dispatch for a month the user has no recap for', function (): void {
    Bus::fake();
    $user = User::factory()->create();
    doneAnalysisFor(AnalysisType::MONTHLY_RECAP_SUBJECT_TYPE, $user->id, AnalysisType::MonthlyRecap, '2026-06', content: 'Bulan ini 120 km.');

    $this->actingAs($user)
        ->post(route('rekap.bulanan.telegram', ['month' => '2026-05']))
        ->assertRedirect()
        ->assertSessionHas('info');

    Bus::assertNotDispatched(SendTelegramNotificationJob::class);
});

it('404s on a malformed month', function (): void {
    Bus::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('rekap.bulanan.telegram', ['month' => 'juni-2026']))
        ->assertNotFound();

    Bus::assertNotDispatched(SendTelegramNotificationJob::class);
});
