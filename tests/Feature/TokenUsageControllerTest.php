<?php

declare(strict_types=1);

use App\Models\AI\TokenUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function seedUsage(
    string $kind,
    int $prompt,
    int $completion,
    Carbon $when,
    ?int $latencyMs = null,
    bool $truncated = false,
    ?int $userId = null,
): void {
    TokenUsage::query()->create([
        'user_id' => $userId,
        'kind' => $kind,
        'prompt_tokens' => $prompt,
        'completion_tokens' => $completion,
        'total_tokens' => $prompt + $completion,
        'model' => 'gpt-test',
        'latency_ms' => $latencyMs,
        'truncated' => $truncated,
        'created_at' => $when,
    ]);
}

it('is reachable without a Laravel session (edge auth handles access in prod)', function (): void {
    $this->get('/ai-usage')->assertSuccessful();
});

it('renders the AiUsage page with totals + per-kind breakdown filtered by date', function (): void {
    seedUsage('briefing', 100, 50, Carbon::parse('2026-05-10 09:00:00'), latencyMs: 800);
    seedUsage('briefing', 200, 80, Carbon::parse('2026-05-15 11:00:00'), latencyMs: 1200, truncated: true);
    seedUsage('run-insight', 300, 150, Carbon::parse('2026-05-12 13:00:00'), latencyMs: 2400);
    seedUsage('briefing', 999, 999, Carbon::parse('2026-04-30 23:00:00')); // outside range

    $this->get('/ai-usage?from=2026-05-01&to=2026-05-19')
        ->assertSuccessful()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('AiUsage')
                ->where('from', '2026-05-01')
                ->where('to', '2026-05-19')
                ->where('totals', [
                    'prompt' => 600,
                    'completion' => 280,
                    'total' => 880,
                    'calls' => 3,
                    'truncated_calls' => 1,
                    'cost' => 0,
                ])
                ->has('byKind', 2)
                ->where('byKind.0', [
                    'kind' => 'run-insight',
                    'prompt' => 300,
                    'completion' => 150,
                    'total' => 450,
                    'calls' => 1,
                    'truncated_calls' => 0,
                    'avg_latency_ms' => 2400,
                    'max_latency_ms' => 2400,
                    'cost' => 0,
                ])
                ->where('byKind.1', [
                    'kind' => 'briefing',
                    'prompt' => 300,
                    'completion' => 130,
                    'total' => 430,
                    'calls' => 2,
                    'truncated_calls' => 1,
                    'avg_latency_ms' => 1000,
                    'max_latency_ms' => 1200,
                    'cost' => 0,
                ])
                ->has('byDeployment')
                ->has('budget'),
        );
});

it('defaults to the rolling last 7 days when no range is given', function (): void {
    Carbon::setTestNow('2026-05-19 12:00:00'); // 7d window = 2026-05-13 .. now
    seedUsage('inside', 50, 50, Carbon::parse('2026-05-15'));
    seedUsage('outside', 50, 50, Carbon::parse('2026-05-10')); // older than 7 days

    $this->get('/ai-usage')
        ->assertSuccessful()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('range', '7d')
                ->where('from', '2026-05-13')
                ->where('totals.calls', 1),
        );

    Carbon::setTestNow();
});

it('resolves relative range tokens to self-correcting windows', function (string $range, string $expectedFrom): void {
    Carbon::setTestNow('2026-05-19 12:00:00');

    $this->get("/ai-usage?range={$range}")
        ->assertSuccessful()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('range', $range)
                ->where('from', $expectedFrom),
        );

    Carbon::setTestNow();
})->with([
    'today' => ['today', '2026-05-19'],
    '7d' => ['7d', '2026-05-13'],
    '30d' => ['30d', '2026-04-20'],
    'month' => ['month', '2026-05-01'],
    'all' => ['all', '1970-01-01'],
]);

it('maps legacy absolute from+to links (no range) to a custom range', function (): void {
    $this->get('/ai-usage?from=2026-05-01&to=2026-05-19')
        ->assertSuccessful()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('range', 'custom')
                ->where('from', '2026-05-01')
                ->where('to', '2026-05-19'),
        );
});

it('includes previousTotals for a bounded range and null for all-time', function (): void {
    Carbon::setTestNow('2026-05-19 12:00:00');
    seedUsage('briefing', 100, 50, Carbon::parse('2026-05-15')); // current 7d window
    seedUsage('briefing', 40, 20, Carbon::parse('2026-05-10')); // prior window

    $this->get('/ai-usage?range=7d')
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('totals.total', 150)
                ->where('previousTotals.total', 60),
        );

    $this->get('/ai-usage?range=all')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('previousTotals', null));

    Carbon::setTestNow();
});

it('rejects malformed date inputs', function (): void {
    $this->getJson('/ai-usage?from=yesterday')->assertStatus(422);
});

it('returns zeroed totals and empty breakdown when no rows fall within range', function (): void {
    $this->get('/ai-usage?from=2026-05-01&to=2026-05-19')
        ->assertSuccessful()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('totals', [
                    'prompt' => 0,
                    'completion' => 0,
                    'total' => 0,
                    'calls' => 0,
                    'truncated_calls' => 0,
                    'cost' => 0,
                ])
                ->has('byKind', 0)
                ->has('byUser', 0),
        );
});

it('renders a byUser breakdown joined to users.name, skipping system-context rows', function (): void {
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);

    seedUsage('briefing', 100, 50, Carbon::parse('2026-05-10'), userId: $alice->id);
    seedUsage('briefing', 200, 80, Carbon::parse('2026-05-12'), userId: $alice->id);
    seedUsage('run-insight', 50, 25, Carbon::parse('2026-05-11'), userId: $bob->id);
    seedUsage('briefing', 10, 5, Carbon::parse('2026-05-13')); // user_id null — system call, excluded from per-user breakdown

    $this->get('/ai-usage?from=2026-05-01&to=2026-05-19')
        ->assertSuccessful()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('byUser', 2)
                ->where('byUser.0', [
                    'user_id' => $alice->id,
                    'user_name' => 'Alice',
                    'prompt' => 300,
                    'completion' => 130,
                    'total' => 430,
                    'calls' => 2,
                ])
                ->where('byUser.1', [
                    'user_id' => $bob->id,
                    'user_name' => 'Bob',
                    'prompt' => 50,
                    'completion' => 25,
                    'total' => 75,
                    'calls' => 1,
                ]),
        );
});

it('keeps the user_id in the breakdown after the user is deleted (no FK cascade)', function (): void {
    $alice = User::factory()->create(['name' => 'Alice']);
    $aliceId = $alice->id;

    seedUsage('briefing', 100, 50, Carbon::parse('2026-05-10'), userId: $aliceId);
    seedUsage('briefing', 200, 80, Carbon::parse('2026-05-12'), userId: $aliceId);

    $alice->delete();

    $this->get('/ai-usage?from=2026-05-01&to=2026-05-19')
        ->assertSuccessful()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('byUser', 1)
                ->where('byUser.0', [
                    'user_id' => $aliceId,
                    'user_name' => null,
                    'prompt' => 300,
                    'completion' => 130,
                    'total' => 430,
                    'calls' => 2,
                ]),
        );
});
