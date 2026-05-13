<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Services\Run\Story\Briefing;
use App\Services\Run\Story\Contracts\BriefingNarrator;
use App\Services\Run\Story\Narrators\CachingBriefingNarrator;

it('binds the rule-based Briefing directly when Azure env is empty', function (): void {
    config()->set('azure_openai.uri', '');
    config()->set('azure_openai.api_key', '');
    app()->forgetInstance(BriefingNarrator::class);
    app()->register(AppServiceProvider::class, force: true);

    expect(app(BriefingNarrator::class))->toBeInstanceOf(Briefing::class);
});

it('binds the Caching → Fallback → Llm chain when Azure env is set', function (): void {
    config()->set('azure_openai.uri', 'https://x.openai.azure.com/openai/deployments/y/chat/completions?api-version=2024-10-21');
    config()->set('azure_openai.api_key', 'fake');
    app()->forgetInstance(BriefingNarrator::class);
    app()->register(AppServiceProvider::class, force: true);

    expect(app(BriefingNarrator::class))->toBeInstanceOf(CachingBriefingNarrator::class);
});
