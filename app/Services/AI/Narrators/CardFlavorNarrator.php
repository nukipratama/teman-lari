<?php

declare(strict_types=1);

namespace App\Services\AI\Narrators;

use App\Enums\Badge;
use App\Models\RunCard;
use App\Services\AI\ChatCallOptions;
use App\Services\AI\Context\ActivityNarrationContext;
use App\Services\AI\StructuredChatCaller;
use App\Services\Run\Metrics\PaceCalculator;

class CardFlavorNarrator
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Tugas: berikan 1 kalimat flavor maksimal 30 kata untuk kartu aktivitas.
        Setiap kartu punya rarity (common, uncommon, rare, epic, legendary) +
        special move + badges. Saat menyebut rarity dalam kalimat, gunakan
        label Bahasa Indonesia: Biasa / Berkesan / Langka / Istimewa / Legendaris.

        Rajut kombinasi badge, pacing, dan cuaca jadi 1 kalimat yang
        nunjukin kenapa kartu ini spesial. Sebut special_move kalau namanya
        unik, sebut badge spesifik kalau ada, sebut cuaca kalau ekstrem
        ("cuaca 33 derajat" atau "hujan").

        ANGIN: sebut angin cuma kalau kencang atau bergust (weather_wind_speed_kmh
        atau weather_wind_gust_kmh tinggi) DAN dia punya peran, misalnya headwind
        yang bikin negative split makin berkesan. Angin bukan detail wajib tiap
        kartu, kalau adem lewati saja.

        HUJAN: cek weather_rain_source. "observed" boleh tegas ("pas hujan").
        "forecast" cuma prakiraan, jadi hedge ("kayaknya sempat gerimis"), jangan
        klaim "hujan deras".

        ANTI-PATTERN:
        - Kalimat generik yang bisa berlaku untuk kartu mana pun.
        - Mengulang formula yang sama untuk rarity yang sama.

        Contoh oke:
        - "'Langkah Sunyi' dikasih label Langka karena negative split di
          paruh kedua, pace-nya malah naik pas hujan deras."
        - "Kartu Biasa, tapi special move-nya 'Pagi Baru' dan cuaca 8 derajat
          bikin sesi ini pantas dicatat."
        PROMPT;

    public function __construct(private readonly StructuredChatCaller $caller)
    {
    }

    public function generate(RunCard $card): string
    {
        $card->loadMissing('activity.detail');
        $detail = $card->activity->detail;
        $shared = ActivityNarrationContext::fromDetail($detail);
        $paceSecPerKm = PaceCalculator::secPerKm($shared->distanceMeters, $detail?->moving_time);

        $context = [
            'rarity' => $card->rarity->value,
            'rarity_label' => $card->rarity->label(),
            'special_move' => $card->special_move,
            'badges' => Badge::promptLabelsFor((array) ($card->badges ?? [])),
            'distance_km' => $shared->distanceKmOrNull(2),
            'pace_sec_per_km' => $paceSecPerKm !== null ? round($paceSecPerKm, 1) : null,
            'weather_temp_c' => $shared->weatherTempC,
            'weather_rain' => $shared->weatherRain,
            'weather_rain_source' => $shared->weatherRainSource,
            'weather_wind_speed_kmh' => $shared->weatherWindSpeedKmh,
            'weather_wind_gust_kmh' => $shared->weatherWindGustKmh,
            'weather_wind_direction_deg' => $shared->weatherWindDirectionDeg,
        ];

        $decoded = $this->caller->call(
            kind: 'card_flavor',
            systemPrompt: self::SYSTEM_PROMPT,
            context: $context,
            schemaName: 'TemariCardFlavor',
            requiredKeys: ['flavor'],
            options: new ChatCallOptions(userId: $card->activity->user_id, maxTokens: 400),
        );

        return (string) $decoded['flavor'];
    }
}
