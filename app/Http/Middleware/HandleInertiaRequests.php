<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Activity;
use App\Models\AI\Analysis;
use App\Models\PersonalRecord;
use App\Models\RunCard;
use App\Models\User;
use App\Models\UserUnlock;
use App\Models\WeeklySnapshot;
use App\Services\AI\AnalysisStatus;
use App\Services\AI\AnalysisType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Override;

class HandleInertiaRequests extends Middleware
{
    private const array IN_FLIGHT_STATUSES = [
        AnalysisStatus::Pending,
        AnalysisStatus::Queued,
        AnalysisStatus::Processing,
    ];

    private const array USER_DAY_SUBJECT_TYPES = [
        AnalysisType::BRIEFING_SUBJECT_TYPE,
        AnalysisType::DAILY_GREETING_SUBJECT_TYPE,
        AnalysisType::TREND_CAPTION_SUBJECT_TYPE,
    ];

    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->firstName(),
                    'avatar_url' => $user->avatar_url ?? null,
                ],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'demoLoginEnabled' => (bool) config('demo.login_enabled'),
            'onboarding' => [
                'forceShow' => (bool) config('onboarding.force_show'),
            ],
            'unlockedAccessories' => fn () => $user === null
                ? []
                : UserUnlock::query()->where('user_id', $user->id)->pluck('unlock_key')->all(),
            'aiActivity' => $this->aiActivityCounts($user),
        ];
    }

    /**
     * @return array{pending: int, queued: int, processing: int}
     */
    private function aiActivityCounts(?User $user): array
    {
        if ($user === null) {
            return ['pending' => 0, 'queued' => 0, 'processing' => 0];
        }

        $rows = Analysis::query()
            ->whereIn('status', self::IN_FLIGHT_STATUSES)
            ->where(fn (Builder $q) => $this->scopeToUser($q, $user))
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return [
            'pending' => (int) ($rows[AnalysisStatus::Pending->value] ?? 0),
            'queued' => (int) ($rows[AnalysisStatus::Queued->value] ?? 0),
            'processing' => (int) ($rows[AnalysisStatus::Processing->value] ?? 0),
        ];
    }

    /**
     * @param  Builder<Analysis>  $query
     */
    private function scopeToUser(Builder $query, User $user): void
    {
        $userOwnedIds = [
            Activity::class => Activity::query()->where('user_id', $user->id)->select('id'),
            PersonalRecord::class => PersonalRecord::query()->where('user_id', $user->id)->select('id'),
            WeeklySnapshot::class => WeeklySnapshot::query()->where('user_id', $user->id)->select('id'),
            RunCard::class => RunCard::query()
                ->whereHas('activity', fn ($a) => $a->where('user_id', $user->id))
                ->select('id'),
        ];

        $query->where(fn (Builder $q) => $q
            ->whereIn('subject_type', self::USER_DAY_SUBJECT_TYPES)
            ->where('subject_id', $user->id));

        foreach ($userOwnedIds as $subjectType => $idQuery) {
            $query->orWhere(fn (Builder $q) => $q
                ->where('subject_type', $subjectType)
                ->whereIn('subject_id', $idQuery));
        }
    }
}
