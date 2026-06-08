<?php

declare(strict_types=1);

namespace App\Services\AI\Narrators;

use App\Models\User;
use App\Services\AI\ChatCallOptions;
use App\Services\AI\StructuredChatCaller;
use App\Services\Run\Story\Vibe;

class DailyGreetingNarrator
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Tugas: 1-2 kalimat greeting, maksimal 30 kata.

        Sesuaikan tone dengan vibe state pengguna:
        - pumped/fresh/bouncy: energik, antusias, mengajak. "Halo! Kamu lagi
          fresh nih, sayang kalau gak dipake lari."
        - worn_down/cooked: lembut, permisif. "Halo. Badan lagi capek ya,
          istirahat juga progres."
        - stretched_thin: empatik, gak ngedesak. "Halo. Semoga harimu
          tenang, kapanpun kamu siap aku nunggu."
        - hibernating: mengajak pelan-pelan. "Halo! Udah beberapa hari gak
          lari, gimana kalau jalan kaki dulu?"

        Gunakan field `name` kalau ada untuk personalisasi ("Halo, Budi!").
        Boleh pakai 1 emoji yang cocok.

        ANTI-PATTERN:
        - "Halo. Semoga harimu tenang, kapanpun kamu siap lari aku nunggu."
          -- muncul terus untuk semua vibe.
        - Time-locked greeting ("Selamat pagi").
        PROMPT;

    public function __construct(private readonly StructuredChatCaller $caller)
    {
    }

    public function generate(User $user, string $vibeState): string
    {
        $decoded = $this->caller->call(
            kind: 'daily_greeting',
            systemPrompt: self::SYSTEM_PROMPT,
            context: [
                'name' => $user->firstName(),
                'vibe' => $vibeState,
                'vibe_label' => Vibe::label($vibeState),
            ],
            schemaName: 'TemariDailyGreeting',
            requiredKeys: ['speech'],
            options: new ChatCallOptions(userId: $user->id, maxTokens: 400),
        );

        return (string) $decoded['speech'];
    }
}
