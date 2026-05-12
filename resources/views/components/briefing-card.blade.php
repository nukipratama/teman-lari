@props([
    /** App\Services\Run\Story\BriefingResult */
    'briefing',
])

@php
    use App\Services\Run\Story\Temari;
    use App\Services\Run\Story\Vibe;

    $tone = match ($briefing->vibeState) {
        Vibe::PUMPED, Vibe::FRESH, Vibe::BOUNCY => 'from-lime-100 to-lime-200 dark:from-lime-900/40 dark:to-lime-800/40',
        Vibe::COOKED, Vibe::STRETCHED_THIN => 'from-rose-100 to-rose-200 dark:from-rose-900/40 dark:to-rose-800/40',
        Vibe::WORN_DOWN => 'from-amber-100 to-amber-200 dark:from-amber-900/40 dark:to-amber-800/40',
        Vibe::HIBERNATING => 'from-slate-100 to-slate-200 dark:from-slate-800/60 dark:to-slate-700/60',
        default => 'from-sky-50 to-sky-100 dark:from-sky-900/30 dark:to-sky-800/30',
    };

    $sigilColor = match ($briefing->mood) {
        Temari::MOOD_GLOW => '#d97706',
        Temari::MOOD_BOUNCY => '#65a30d',
        Temari::MOOD_WOBBLE => '#e11d48',
        Temari::MOOD_SQUISHED => '#ea580c',
        Temari::MOOD_SPINNING => '#0284c7',
        default => '#64748b',
    };

    $bubbleRing = match ($briefing->mood) {
        Temari::MOOD_GLOW => 'ring-amber-300 dark:ring-amber-500/60',
        Temari::MOOD_BOUNCY => 'ring-lime-300 dark:ring-lime-500/60',
        Temari::MOOD_WOBBLE => 'ring-rose-300 dark:ring-rose-500/60',
        Temari::MOOD_SQUISHED => 'ring-orange-300 dark:ring-orange-500/60',
        Temari::MOOD_SPINNING => 'ring-sky-300 dark:ring-sky-500/60',
        default => 'ring-slate-300 dark:ring-slate-500/60',
    };

    $moodFace = match ($briefing->mood) {
        Temari::MOOD_GLOW => '✨',
        Temari::MOOD_BOUNCY => '🦘',
        Temari::MOOD_WOBBLE => '🥵',
        Temari::MOOD_SQUISHED => '🍳',
        Temari::MOOD_SPINNING => '💫',
        default => '🌧️',
    };

    $recoveryChipTone = match ($briefing->recoveryTone) {
        'positive' => 'bg-lime-100 text-lime-800 dark:bg-lime-900/40 dark:text-lime-200',
        'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        'alert' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
        default => 'bg-white/70 text-gray-700 dark:bg-white/10 dark:text-gray-200',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-3xl border border-black/5 p-6 dark:border-white/5 bg-gradient-to-br {$tone}"]) }}>
    <div class="flex flex-col items-start gap-6 sm:flex-row sm:items-center">
        <div class="relative shrink-0">
            <div class="relative flex h-28 w-28 items-center justify-center rounded-full bg-gradient-to-br from-lime-200 to-lime-400 ring-4 {{ $bubbleRing }} dark:from-lime-700 dark:to-lime-500">
                <span class="relative z-10 text-4xl">{{ $moodFace }}</span>
                <x-temari-sigil
                    :pattern="$briefing->sigilPattern"
                    :color="$sigilColor"
                    :accessory="$briefing->accessory"
                    :size="112"
                    class="absolute inset-0 mix-blend-multiply dark:mix-blend-screen"
                />
            </div>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-baseline gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                    Briefing Temari
                </span>
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                    {{ $briefing->vibeEmoji }} {{ $briefing->vibeLabel }}
                </span>
            </div>
            <p class="mt-2 text-lg font-semibold leading-snug tracking-tight text-gray-900 dark:text-white">
                {{ $briefing->headlineLine }}
            </p>
            <p class="mt-1 text-sm leading-relaxed text-gray-700 dark:text-gray-200">
                {{ $briefing->suggestionLine }}
            </p>

            <div class="mt-4 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full {{ $recoveryChipTone }} px-3 py-1 text-xs font-semibold">
                    <iconify-icon icon="mdi:heart-pulse" width="14" height="14" aria-hidden="true"></iconify-icon>
                    {{ $briefing->recoveryLabel }}
                </span>
                @if ($briefing->streakLabel !== null)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">
                        <iconify-icon icon="mdi:run" width="14" height="14" aria-hidden="true"></iconify-icon>
                        {{ $briefing->streakLabel }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
