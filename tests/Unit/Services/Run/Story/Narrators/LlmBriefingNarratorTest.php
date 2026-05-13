<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\ActivityDetail;
use App\Models\User;
use App\Services\Llm\AzureOpenAiClient;
use App\Services\Llm\LlmNarratorException;
use App\Services\Run\Metrics\TrainingLoad;
use App\Services\Run\Story\Contracts\VerdictNarrator;
use App\Services\Run\Story\Narrators\LlmBriefingNarrator;
use App\Services\Run\Story\Temari;
use App\Services\Run\Story\Vibe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Testing\ClientFake;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('azure_openai.uri', 'https://x.openai.azure.com/openai/deployments/x/chat/completions?api-version=2024-10-21');
    config()->set('azure_openai.api_key', 'fake-key');
    config()->set('azure_openai.deployment', 'x');
    config()->set('azure_openai.timeout', 8);
    config()->set('azure_openai.max_tokens', 400);
});

/** @return array{user: User, narrator: LlmBriefingNarrator, client: ClientFake} */
function bootLlmNarrator(string $jsonContent): array
{
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    // Seed a run so Vibe/TrainingLoad have data to chew on.
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today(),
        'trimp_edwards' => 60.0,
    ]);

    $client = new ClientFake([
        CreateResponse::fake([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => $jsonContent]],
            ],
        ]),
    ]);
    $azure = Mockery::mock(AzureOpenAiClient::class);
    $azure->shouldReceive('client')->andReturn($client);

    $narrator = new LlmBriefingNarrator(
        app(Vibe::class),
        app(TrainingLoad::class),
        app(VerdictNarrator::class),
        $azure,
    );

    return ['user' => $user, 'narrator' => $narrator, 'client' => $client];
}

it('maps a valid LLM structured response onto BriefingResult', function (): void {
    ['user' => $user, 'narrator' => $narrator] = bootLlmNarrator(json_encode([
        'mood' => Temari::MOOD_GLOW,
        'headline' => 'Pagi yang oke buat lari pelan',
        'suggestion' => 'Easy run 30 menit, dengerin badan',
        'vibe_label' => 'Lagi Glow',
        'vibe_emoji' => '✨',
    ], JSON_THROW_ON_ERROR));

    $result = $narrator->generate($user, Carbon::today());

    expect($result->mood)->toBe(Temari::MOOD_GLOW)
        ->and($result->headlineLine)->toBe('Pagi yang oke buat lari pelan')
        ->and($result->suggestionLine)->toBe('Easy run 30 menit, dengerin badan')
        ->and($result->vibeLabel)->toBe('Lagi Glow')
        ->and($result->vibeEmoji)->toBe('✨')
        ->and($result->sigilPattern)->toBe(Temari::sigilForMoodPublic(Temari::MOOD_GLOW))
        ->and($result->accessory)->toBe(Temari::accessoryForMoodPublic(Temari::MOOD_GLOW))
        ->and($result->degraded)->toBeFalse();
});

it('throws LlmNarratorException when the response is not valid JSON', function (): void {
    ['user' => $user, 'narrator' => $narrator] = bootLlmNarrator('not json at all');
    $narrator->generate($user, Carbon::today());
})->throws(LlmNarratorException::class, 'non-JSON');

it('throws LlmNarratorException when required fields are missing', function (): void {
    ['user' => $user, 'narrator' => $narrator] = bootLlmNarrator(json_encode(['mood' => 'glow'], JSON_THROW_ON_ERROR));
    $narrator->generate($user, Carbon::today());
})->throws(LlmNarratorException::class, 'missing required fields');

it('throws LlmNarratorException when the Azure HTTP call itself throws', function (): void {
    $user = User::factory()->create();
    $client = new ClientFake([new RuntimeException('Azure 500')]);
    $azure = Mockery::mock(AzureOpenAiClient::class);
    $azure->shouldReceive('client')->andReturn($client);

    $narrator = new LlmBriefingNarrator(
        app(Vibe::class),
        app(TrainingLoad::class),
        app(VerdictNarrator::class),
        $azure,
    );
    $narrator->generate($user, Carbon::today());
})->throws(LlmNarratorException::class, 'Azure OpenAI call failed');
