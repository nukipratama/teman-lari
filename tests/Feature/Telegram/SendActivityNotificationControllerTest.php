<?php

declare(strict_types=1);

use App\Jobs\Telegram\SendTelegramNotificationJob;
use App\Models\Activity;
use App\Models\User;
use App\Services\AI\AnalysisType;
use App\Support\Cooldown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('requires authentication', function (): void {
    $activity = Activity::factory()->create();

    $this->post(route('aktivitas.telegram', $activity))->assertRedirect(route('login'));
});

it('force-dispatches the push when the post-run speech is done', function (): void {
    Bus::fake();
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();
    $analysis = doneAnalysisFor(Activity::class, $activity->id, AnalysisType::PostRunSpeech, content: 'Mantap!');

    $this->actingAs($user)
        ->post(route('aktivitas.telegram', $activity))
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
    $activity = Activity::factory()->for($user)->create();
    $analysis = doneAnalysisFor(Activity::class, $activity->id, AnalysisType::PostRunSpeech, content: 'Mantap!');
    RateLimiter::hit(Cooldown::telegramKey($analysis->id), Cooldown::WINDOW_SECONDS);

    $this->actingAs($user)
        ->post(route('aktivitas.telegram', $activity))
        ->assertRedirect()
        ->assertSessionHas('info');

    Bus::assertNotDispatched(SendTelegramNotificationJob::class);
});

it('does not dispatch and flashes info when the narration is not ready', function (): void {
    Bus::fake();
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();
    doneAnalysisFor(Activity::class, $activity->id, AnalysisType::PostRunSpeech, done: false);

    $this->actingAs($user)
        ->post(route('aktivitas.telegram', $activity))
        ->assertRedirect()
        ->assertSessionHas('info');

    Bus::assertNotDispatched(SendTelegramNotificationJob::class);
});

it('404s when the activity belongs to another user', function (): void {
    Bus::fake();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $activity = Activity::factory()->for($owner)->create();

    $this->actingAs($other)
        ->post(route('aktivitas.telegram', $activity))
        ->assertNotFound();

    Bus::assertNotDispatched(SendTelegramNotificationJob::class);
});
