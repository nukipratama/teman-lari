<?php

declare(strict_types=1);

use App\Jobs\AI\AnalyzeBriefingFeaturedKartuVoiceJob;
use App\Models\AI\Analysis;
use App\Models\User;
use App\Services\AI\AnalysisService;
use App\Services\AI\AnalysisStatus;
use App\Services\AI\AnalysisType;
use App\Services\AI\Narrators\BriefingFeaturedKartuVoiceNarrator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function mockFeaturedKartuNarrator(string $payload): void
{
    $mock = Mockery::mock(BriefingFeaturedKartuVoiceNarrator::class);
    $mock->shouldReceive('generate')->andReturn($payload);
    app()->instance(BriefingFeaturedKartuVoiceNarrator::class, $mock);
}

function featuredKartuRow(int $userId, ?string $discriminator = '2026-05-18'): Analysis
{
    return Analysis::factory()->queued()->create([
        'subject_type' => AnalysisType::BRIEFING_SUBJECT_TYPE,
        'subject_id' => $userId,
        'analysis_type' => AnalysisType::BriefingFeaturedKartuVoice,
        'discriminator' => $discriminator,
    ]);
}

it('marks the row Done with the kartu voice from the narrator', function (): void {
    $user = User::factory()->create();
    mockFeaturedKartuNarrator('kartu voice line');

    $row = featuredKartuRow($user->id);
    (new AnalyzeBriefingFeaturedKartuVoiceJob($row->id))->handle(app(AnalysisService::class));

    $fresh = $row->fresh();
    expect($fresh->content)->toBe('kartu voice line')
        ->and($fresh->status)->toBe(AnalysisStatus::Done);
});

it('falls back to today when the discriminator is null', function (): void {
    Carbon::setTestNow('2026-05-19 12:00:00');
    $user = User::factory()->create();
    mockFeaturedKartuNarrator('today kartu voice');

    $row = featuredKartuRow($user->id, null);
    (new AnalyzeBriefingFeaturedKartuVoiceJob($row->id))->handle(app(AnalysisService::class));

    expect($row->fresh()->content)->toBe('today kartu voice')
        ->and($row->fresh()->status)->toBe(AnalysisStatus::Done);
    Carbon::setTestNow();
});

it('marks the row Failed and rethrows when the user is missing', function (): void {
    $row = featuredKartuRow(99999);

    expect(fn () => (new AnalyzeBriefingFeaturedKartuVoiceJob($row->id))->handle(app(AnalysisService::class)))
        ->toThrow(ModelNotFoundException::class);

    expect($row->fresh()->status)->toBe(AnalysisStatus::Failed);
});
