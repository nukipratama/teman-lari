<?php

declare(strict_types=1);

namespace App\Services\AI\Narrators;

use App\Models\PersonalRecord;
use App\Services\AI\ChatCallOptions;
use App\Services\AI\Context\ActivityNarrationContext;
use App\Services\AI\StructuredChatCaller;
use App\Services\Run\Metrics\VdotEstimator;

class PrContextNarrator
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Tugas: 1-2 kalimat flavor untuk Personal Record, maksimal 35 kata.

        Highlight delta dari PR sebelumnya jika ada (sebutkan berapa detik
        lebih cepat). Kalau ini PR pertama di kategori, rayakan sebagai
        "PR pertama". Kalau gap-nya besar (>30 detik), soroti sebagai lompatan
        besar. Kalau tipis (<10 detik), akui effort konsisten.

        Contoh:
        - "PR 5km dipotong 12 detik dari yang lalu. Bukan kebetulan, ini
          hasil latihan yang konsisten."
        - "PR pertama di 10km! Langkah besar, kamu layak rayain."
        - "Dipotong tipis, cuma 3 detik, tapi PR tetap PR. Momentum naik."

        Tone: bangga, hangat, gak lebay.

        CUACA: kalau kondisi pas PR ekstrem (weather_temp_c tinggi di atas 30,
        atau weather_rain true), boleh sebut buat nambah bobot ("PR di tengah
        panas 32 derajat, respect"). weather_rain_source "forecast" cuma
        prakiraan, jadi hedge. Kalau adem, lewati, jangan dipaksa.

        EVENT TERKUAT: kalau is_strongest_event true, PR ini juga bikin kategori
        ini jadi event terkuat pengguna (VDOT tertinggi di antara semua jarak).
        Boleh diakui sebagai poin bangga, sebut skor vdot kalau enak ("sekarang
        ini event terkuatmu, VDOT 45"). Kalau false atau vdot null, jangan sebut
        VDOT sama sekali.

        ANTI-PATTERN:
        - "PR-nya hasil dari konsistensi minggu-minggu sebelumnya, bukan
          kebetulan." -- formula yang muncul terus.
        - Hyperbola ("INCREDIBLE!!!").
        PROMPT;

    public function __construct(
        private readonly StructuredChatCaller $caller,
        private readonly VdotEstimator $vdotEstimator,
    ) {
    }

    public function generate(PersonalRecord $pr): string
    {
        $decoded = $this->caller->call(
            kind: 'pr_context',
            systemPrompt: self::SYSTEM_PROMPT,
            context: $this->context($pr),
            schemaName: 'TemariPrContext',
            requiredKeys: ['flavor'],
            options: new ChatCallOptions(temperature: 0.7, userId: $pr->user_id, maxTokens: 500),
        );

        return (string) $decoded['flavor'];
    }

    /**
     * @return array<string, mixed>
     */
    public function context(PersonalRecord $pr): array
    {
        $previous = PersonalRecord::query()
            ->where('user_id', $pr->user_id)
            ->where('category', $pr->category)
            ->where('id', '<>', $pr->id)
            ->orderByDesc('set_at')
            ->first();

        $pr->loadMissing('activity.detail');
        $conditions = ActivityNarrationContext::fromDetail($pr->activity?->detail);

        $vdot = $this->vdotEstimator->estimate($pr->user);
        $isStrongestEvent = $vdot !== null && $vdot['source_category'] === $pr->category->value;

        return [
            'category' => $pr->category->value,
            'value_sec' => $pr->value_sec,
            'set_at' => $pr->set_at->toDateString(),
            'previous_value_sec' => $previous?->value_sec,
            'previous_set_at' => $previous?->set_at?->toDateString(),
            'delta_sec' => $previous !== null ? ($previous->value_sec - $pr->value_sec) : null,
            'vdot' => $vdot['vdot'] ?? null,
            'is_strongest_event' => $isStrongestEvent,
            'weather_temp_c' => $conditions->weatherTempC,
            'weather_rain' => $conditions->weatherRain,
            'weather_rain_source' => $conditions->weatherRainSource,
            'weather_wind_speed_kmh' => $conditions->weatherWindSpeedKmh,
            'weather_wind_gust_kmh' => $conditions->weatherWindGustKmh,
        ];
    }
}
