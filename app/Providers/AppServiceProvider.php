<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Run\Story\Briefing;
use App\Services\Run\Story\Contracts\BriefingNarrator;
use App\Services\Run\Story\Contracts\VerdictNarrator;
use App\Services\Run\Story\Narrators\CachingBriefingNarrator;
use App\Services\Run\Story\Narrators\FallbackBriefingNarrator;
use App\Services\Run\Story\Narrators\LlmBriefingNarrator;
use App\Services\Run\Story\VerdictTimeline;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Strava\StravaExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(VerdictNarrator::class, VerdictTimeline::class);

        // Empty Azure env → silent rule-based path (no degraded chip).
        $this->app->bind(BriefingNarrator::class, function (Application $app): BriefingNarrator {
            $rules = $app->make(Briefing::class);

            $enabled = filled(config('azure_openai.uri')) && filled(config('azure_openai.api_key'));
            if (! $enabled) {
                return $rules;
            }

            $llm = $app->make(LlmBriefingNarrator::class);
            $fallback = new FallbackBriefingNarrator($llm, $rules);

            return new CachingBriefingNarrator($fallback);
        });
    }

    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, StravaExtendSocialite::class);
    }
}
