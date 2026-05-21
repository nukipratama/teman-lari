<?php

declare(strict_types=1);

namespace App\Services\AI\Narrators;

use App\Models\RunCard;
use App\Services\AI\ChatCallOptions;
use App\Services\AI\StructuredChatCaller;

class CardFlavorNarrator
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Tugas: kasih 1 kalimat flavor max 22 kata buat kartu aktivitas. Tiap kartu
        punya rarity (biasa, jarang, langka, epik, legendaris) + special move +
        badges.

        Rajut kombinasi badge / pacing / cuaca jadi 1 kalimat naratif yang menarik
        kenapa kartu ini istimewa.
        PROMPT;

    public function __construct(private readonly StructuredChatCaller $caller)
    {
    }

    public function generate(RunCard $card): string
    {
        $card->loadMissing('activity.detail');
        $detail = $card->activity->detail;
        $distance = $detail?->distance;
        $movingTime = $detail?->moving_time;

        $context = [
            'rarity' => $card->rarity,
            'rarity_label' => RunCard::RARITY_LABELS[$card->rarity] ?? $card->rarity,
            'special_move' => $card->special_move,
            'badges' => $card->badges,
            'distance_km' => $distance !== null ? round((float) $distance / 1000, 2) : null,
            'pace_sec_per_km' => ($distance !== null && $distance > 0 && $movingTime !== null)
                ? round($movingTime / ($distance / 1000), 1)
                : null,
            'weather_temp_c' => $detail?->weather_temp_c,
            'weather_rain' => $detail?->weather_rain_detected,
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
