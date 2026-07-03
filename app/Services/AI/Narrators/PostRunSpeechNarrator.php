<?php

declare(strict_types=1);

namespace App\Services\AI\Narrators;

use App\Models\Activity;
use App\Models\ActivityDetail;
use App\Models\PersonalRecord;
use App\Services\AI\AnalysisType;
use App\Services\AI\ChatCallOptions;
use App\Services\AI\Context\ActivityNarrationContext;
use App\Services\AI\Narrators\Concerns\ReadsPreviousActivityNarrative;
use App\Services\AI\StructuredChatCaller;

use function is_string;

class PostRunSpeechNarrator
{
    use ReadsPreviousActivityNarrative;

    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Tugas: cerita post-run hangat setelah pengguna selesai lari. Rangkai
        2-4 kalimat (maksimal 75 kata) jadi satu cerita kecil beralur: buka dari
        satu sorotan, tunjukin satu titik menarik di tengah (misalnya pace sempat
        pecah lalu balik, atau finish yang nyala), lalu tutup dengan enak.

        Kamu menerima tiga analisis teknis yang sudah jadi di field insights:
        - technical: terjemahan teknis (cadence, decoupling, HR).
        - splits: split atau pola pacing paling menarik.
        - zones: interpretasi HR zone.
        Tafsirkan ketiganya jadi satu cerita manusiawi. Pilih SATU sorotan paling
        menonjol buat dibuka, sisanya jadi latar. JANGAN mendaftar semua angka
        atau mengulang isi analisis mentah-mentah.

        Buka dari sorotan itu, bukan dari status atau basa-basi. Kalau ada,
        sematkan detail suasana (jam lari, cuaca, medan) biar kerasa nyata.
        Sesuaikan tone ke mood di field `mood` (kode Daybreak), ikut kalibrasi
        mood di persona.

        JANGAN PERNAH menyebut "PR" atau "personal record" kecuali has_pr bernilai
        true. Kalau has_pr false, rayakan sorotan nyata lain (jarak, konsistensi,
        finish, atau cuaca), bukan PR yang tidak ada.
        PROMPT;

    public function __construct(private readonly StructuredChatCaller $caller)
    {
    }

    /**
     * @param  array{technical: string, splits: string, zones: string}  $insights
     */
    public function generate(Activity $activity, ActivityDetail $detail, string $mood, array $insights): string
    {
        $decoded = $this->caller->call(
            kind: 'post_run_speech',
            systemPrompt: self::SYSTEM_PROMPT."\n\n".NarratorContinuity::RULE,
            context: $this->context($activity, $detail, $mood, $insights),
            schemaName: 'TemariPostRunSpeech',
            requiredKeys: ['speech'],
            options: new ChatCallOptions(userId: $activity->user_id, maxTokens: 1500),
        );

        return (string) $decoded['speech'];
    }

    /**
     * @param  array{technical: string, splits: string, zones: string}  $insights
     * @return array<string, mixed>
     */
    public function context(Activity $activity, ActivityDetail $detail, string $mood, array $insights): array
    {
        $hasPr = PersonalRecord::query()->where('activity_id', $activity->id)->exists();
        $shared = ActivityNarrationContext::fromDetail($detail);
        $dominantZone = $shared->zonePct === []
            ? null
            : array_search(max($shared->zonePct), $shared->zonePct, strict: true);

        $prevNarrative = $this->previousActivityNarrative(
            $activity,
            $detail,
            AnalysisType::PostRunSpeech,
        );

        return [
            'mood' => $mood,
            'has_pr' => $hasPr,
            'insights' => $insights,
            'distance_km' => $shared->distanceKm(1),
            'dominant_zone' => is_string($dominantZone) ? $dominantZone : null,
            'decoupling_pct' => $shared->decouplingPct,
            'negative_split' => $shared->negativeSplit,
            'weather_temp_c' => $shared->weatherTempC,
            'weather_rain' => $shared->weatherRain,
            ...NarratorContinuity::fields($prevNarrative),
        ];
    }
}
