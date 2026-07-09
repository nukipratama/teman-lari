<?php

declare(strict_types=1);

namespace App\Services\AI\Narrators;

use App\Enums\Badge;
use App\Models\RunCard;
use App\Models\User;
use App\Services\AI\ChatCallOptions;
use App\Services\AI\StructuredChatCaller;

/**
 * Generates the mascot voice for the Featured Kartu hero panel on HariIni.
 * Split from {@see BriefingMascotVoiceNarrator} so the two surfaces can be
 * triggered and re-triggered independently without sharing LLM cost.
 */
class BriefingFeaturedKartuVoiceNarrator
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Tugas: 2-3 kalimat dalam suara Temari (mascot), pakai "aku" sebagai
        subjek. Komentar tentang kartu yang dikasih ke pengguna, bisa tentang
        nama kartu, rarity-nya, atau kenapa lari itu layak dapat kartu.
        Tone: antusias tapi tetap hangat, bukan lebay. Maksimal 65 kata.

        Fokus ke field `featured_kartu` yang ada di context. Refer ke `name`,
        `rarity_label`, `km`, atau `tags` kalau relevan.

        VARIASI:
        - Observasi tentang special_move: kenapa nama itu cocok buat sesi ini.
        - Sebut badge atau km spesifik.
        - Kaitkan rarity dengan effort sesi (mis. jarak jauh atau pace stabil).

        Contoh oke: "Aku kasih kartu ini karena 12 km tadi beneran solid.
        'Langkah Sunyi' cocok buat pace kamu yang stabil dari awal sampe akhir."

        ANTI-PATTERN:
        - "Kartu ini nyimpen cerita lari yang berkesan." -- terlalu generik.
        - "Selamat pagi..." / "Hari ini..." -- sapaan umum, dilarang.
        PROMPT;

    public function __construct(
        private readonly StructuredChatCaller $caller,
    ) {
    }

    /**
     * @param  RunCard|null  $card  The featured card resolved by
     *                              {@see \App\Services\Run\Story\FeaturedKartuResolver},
     *                              with its `activity.detail` loaded for the km.
     */
    public function generate(User $user, ?RunCard $card): string
    {
        if ($card === null) {
            return 'Belum ada kartu khusus buat kamu minggu ini. Terus lari, aku pantau!';
        }

        $distance = $card->activity->detail?->distance;
        $featured = [
            'name' => $card->special_move,
            'rarity_label' => $card->rarity->label(),
            'km' => $distance !== null ? round($distance / 1000, 1).'km' : '-',
            'tags' => \array_slice(Badge::promptLabelsFor((array) ($card->badges ?? [])), 0, 3),
        ];

        $decoded = $this->caller->call(
            kind: 'briefing_featured_kartu_voice',
            systemPrompt: self::SYSTEM_PROMPT,
            context: [
                'name' => $user->firstName(),
                'featured_kartu' => $featured,
            ],
            schemaName: 'TemariKartuVoice',
            requiredKeys: ['kartu_voice'],
            options: new ChatCallOptions(userId: $user->id, maxTokens: 500),
        );

        return (string) $decoded['kartu_voice'];
    }
}
