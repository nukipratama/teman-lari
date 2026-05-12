@props([
    /**
     * list<App\Services\Run\Story\VerdictTimelineItem>
     */
    'items' => [],
])

@php
    use App\Services\Run\Story\Temari;

    $ringFor = fn (string $mood): string => match ($mood) {
        Temari::MOOD_GLOW => 'ring-amber-300 dark:ring-amber-500/60',
        Temari::MOOD_BOUNCY => 'ring-lime-300 dark:ring-lime-500/60',
        Temari::MOOD_WOBBLE => 'ring-rose-300 dark:ring-rose-500/60',
        Temari::MOOD_SQUISHED => 'ring-orange-300 dark:ring-orange-500/60',
        Temari::MOOD_SPINNING => 'ring-sky-300 dark:ring-sky-500/60',
        default => 'ring-slate-300 dark:ring-slate-500/60',
    };
@endphp

@if (! empty($items))
    <section class="mt-6">
        <div class="flex items-baseline justify-between">
            <h2 class="text-lg font-bold tracking-tight">Kata Temari</h2>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($items) }} run terakhir</span>
        </div>

        <div class="mt-3 -mx-6 overflow-x-auto px-6">
            <div class="flex gap-3 pb-2">
                @foreach ($items as $item)
                    <a
                        href="{{ route('runs.show', $item->activityId) }}"
                        class="group flex w-64 shrink-0 flex-col gap-2 rounded-2xl border border-black/5 bg-white p-4 transition hover:border-lime-400/60 hover:shadow-sm dark:border-white/5 dark:bg-[#161615] dark:hover:border-lime-500/40"
                    >
                        <div class="flex items-center gap-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-lime-200 to-lime-400 text-base ring-2 {{ $ringFor($item->mood) }} dark:from-lime-700 dark:to-lime-500">
                                {{ $item->moodFace }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-xs font-semibold text-gray-700 dark:text-gray-200">
                                    {{ number_format($item->distanceKm, 1) }} km
                                </div>
                                <div class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                    {{ $item->startedAt->translatedFormat('d M') }}
                                </div>
                            </div>
                        </div>
                        <p class="line-clamp-3 text-xs leading-relaxed text-gray-700 dark:text-gray-300">
                            {{ $item->oneline }}
                        </p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
