<?php

declare(strict_types=1);

namespace App\Services\AI\Narrators;

use App\Models\User;
use App\Models\WeeklySnapshot;
use App\Services\AI\ChatCallOptions;
use App\Services\AI\StructuredChatCaller;
use App\Services\Run\Metrics\TrainingLoad;
use Illuminate\Support\Carbon;

class TrendCaptionNarrator
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Tugas: 1-2 kalimat caption maksimal 40 kata untuk chart Fitness/Form +
        Weekly Volume.

        Fokus ke tren (naik, turun, plateau, peak). Sebutkan konteks bila ada
        (PR week, recovery week, taper).

        Gunakan data `weeks` yang ada di context: bandingkan 4 minggu terakhir
        dengan 4 minggu sebelumnya. Sebut perubahan spesifik kalau menonjol
        (distance naik/turun, form positif/negatif, CTL meningkat).

        Contoh:
        - "Fitness naik 3 minggu berturut, volume juga meningkat. Base lagi
          dibangun solid."
        - "Tren volume turun 2 minggu terakhir, form positif. Kayaknya lagi
          taper atau recovery alami."
        - "CTL stagnan di 40-an, volume flat. Perlu variasi buat naik level."

        ANTI-PATTERN:
        - "Tren beberapa minggu terakhir relatif rata. Solid base." --
          terlalu generik.
        - Caption yang sama setiap refresh.
        PROMPT;

    public function __construct(
        private readonly StructuredChatCaller $caller,
        private readonly TrainingLoad $trainingLoad,
    ) {
    }

    public function generate(User $user, Carbon $asOf): string
    {
        $weeks = WeeklySnapshot::query()
            ->where('user_id', $user->id)
            ->orderByDesc('week_ending')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $decoded = $this->caller->call(
            kind: 'trend_caption',
            systemPrompt: self::SYSTEM_PROMPT,
            context: [
                'as_of' => $asOf->toDateString(),
                'load_today' => $this->trainingLoad->summary($user, $asOf),
                'weeks' => $weeks->map(fn (WeeklySnapshot $w): array => [
                    'ending' => $w->week_ending->toDateString(),
                    'distance_km' => $w->distance_km,
                    'trimp' => $w->weekly_trimp,
                    'ctl_42d' => $w->ctl_42d,
                    'atl_7d' => $w->atl_7d,
                    'form' => $w->form,
                    'status' => $w->form_status,
                ])->all(),
            ],
            schemaName: 'TemariTrendCaption',
            requiredKeys: ['caption'],
            options: new ChatCallOptions(temperature: 0.7, userId: $user->id, maxTokens: 600),
        );

        return (string) $decoded['caption'];
    }
}
