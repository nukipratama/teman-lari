<?php

declare(strict_types=1);

namespace App\Services\AI\Narrators;

use App\Models\User;
use App\Services\AI\ChatCallOptions;
use App\Services\AI\StructuredChatCaller;
use App\Services\Run\Metrics\TrainingLoad;
use App\Services\Run\Story\BriefingContext;
use App\Services\Run\Story\Contracts\VerdictNarrator;
use App\Services\Run\Story\MetricsContext;
use App\Services\Run\Story\Vibe;
use Illuminate\Support\Carbon;

class BriefingNarrator
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Tugas: berikan briefing harian. Output tiga bagian (greeting di-handle
        secara statis oleh UI, kamu cukup tiga ini).

        ATURAN TENTANG WAKTU (PENTING):
        Dashboard ini bisa dibuka kapan aja oleh user — pagi, siang, sore, atau
        malam — dan briefing ini cached harian (1x per hari, gak refresh per
        kunjungan). JANGAN asumsi user lagi mau lari sekarang atau di waktu
        spesifik. JANGAN tulis "malam ini enak buat...", "sore ini cocok...",
        "pagi ini bagus...". Frame setiap saran sebagai sesi-on-demand yang
        bisa dieksekusi kapan aja user sempet hari ini. Contoh frasa netral:
        "kalau ada slot lari hari ini...", "untuk sesi berikutnya...",
        "saat sempet, format yang cocok...", "kalau jadi lari hari ini...".

        - mascot_voice: 2-4 kalimat dalam suara Temari (mascot), pakai "aku"
          sebagai subjek. Comment observasional yang personal dan
          mood-aware. Boleh refer ke run terakhir, tren minggu ini vs minggu
          lalu, recovery hours, atau streak kalau relevan. JANGAN ngulang
          headline atau suggestion. Tone: hangat, supportive, gak menggurui.
          Maksimal 60 kata.
          Contoh oke: "Aku liat tiga hari terakhir km kamu naik tipis, bagus.
          Tapi dari mood verdict-mu, sesi tempo udah dua kali berturut —
          kalau jadi lari lagi, aku saranin mundur sedikit ke easy."
          Contoh JANGAN: "Sore ini enak buat..." / "Malam ini cocok...".

        - headline: 1-2 kalimat verdict factual kondisi user hari ini. Boleh
          singgung satu metric konkret (form, recovery, atau weekly load)
          biar terasa data-driven. Statement tentang KONDISI, bukan rencana.
          Maksimal 25 kata.
          Contoh oke: "Form +12 dan recovery 18 jam — kapasitas kamu hari
          ini di zona quality session."
          Contoh JANGAN: "Pagi ini siap buat tempo run."

        - suggestion: 2-3 kalimat saran konkret yang time-neutral. Sebutkan
          format (easy/tempo/rest/long/interval), durasi atau distance kasar,
          dan satu cue eksekusi (pace, HR, effort, atau cue teknis seperti
          cadence). Maksimal 50 kata.
          Contoh oke: "Kalau jadi lari hari ini, easy 30-40 menit di zona 2
          paling pas — fokus jaga cadence di 175+. Capek mid-session? Cut
          pendek, jangan dipaksain."
          Contoh JANGAN: "Sore ini lari tempo 15 menit..." / "Malam ini
          cooldown ringan..."

        Sesuaikan tone dengan mood pengguna hari ini (lihat field `vibe`). Untuk
        mood spesifik briefing: glow=energik, bouncy=excited dan mengajak, wobble=
        empatik, squished=concerned, dim=lembut, spinning=reflektif.

        Gunakan field `context` untuk personalisasi:
        - `this_week_runs` / `last_week_runs` / `this_week_km` / `last_week_km`:
          banding minggu ini vs minggu lalu. Naik = apresiasi, turun = ajak satu
          lari kecil tanpa nge-judge.
        - `recovery_hours`: <24 jam = easy atau rest; 24-48 jam = base/moderate
          aman; >48 jam = oke untuk sesi quality / tempo / interval.
        - `time_bucket`: HANYA untuk nuance tone (subuh/pagi = lebih cerah,
          malam = lebih kalem). BUKAN untuk bilang "sesi sekarang" atau
          asumsi user lagi mau lari di jam itu.
        - `consecutive_weeks_active`: 3+ minggu = beri kredit konsistensi. 0 =
          ajak balik pelan-pelan.
        - `form_status` (fresh/optimal/fatigued/overreaching): bentuk tone
          suggestion sesuai kapasitas — overreaching = wajib rest, bukan
          quality session.
        - `recent_runs` (5 entry terbaru): boleh refer ke pola spesifik (misal
          "tiga lari terakhir tempo terus" → suggestion balance dengan easy).

        Suarakan kondisi user secara umum hari ini, seperti teman yang nemenin
        training. Boleh spesifik dan data-aware, asal tetap conversational —
        JANGAN kering kayak textbook, JANGAN time-locked. Tiga bagian harus
        DISTINCT — jangan saling mengulang isi.
        PROMPT;

    public function __construct(
        private readonly Vibe $vibe,
        private readonly TrainingLoad $trainingLoad,
        private readonly VerdictNarrator $verdictNarrator,
        private readonly StructuredChatCaller $caller,
    ) {
    }

    /**
     * @return array{headline: string, suggestion: string, mascot_voice: string}
     */
    public function generate(User $user, ?Carbon $asOf = null): array
    {
        $asOf ??= Carbon::today();
        $vibeState = $this->vibe->current($user, $asOf);
        $load = $this->trainingLoad->summary($user, $asOf) ?? [];
        $verdicts = $this->verdictNarrator->recent($user, 5);

        $ctx = new MetricsContext($user, $vibeState, $load, $verdicts, $asOf);

        $decoded = $this->caller->call(
            kind: 'briefing',
            systemPrompt: self::SYSTEM_PROMPT,
            context: $this->buildContext($ctx),
            schemaName: 'TemariBriefing',
            requiredKeys: ['headline', 'suggestion', 'mascot_voice'],
            options: new ChatCallOptions(userId: $user->id, maxTokens: 2500),
        );

        return [
            'headline' => (string) $decoded['headline'],
            'suggestion' => (string) $decoded['suggestion'],
            'mascot_voice' => (string) $decoded['mascot_voice'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(MetricsContext $ctx): array
    {
        $verdictSummary = array_map(
            fn ($v): array => ['mood' => $v->mood, 'km' => $v->distanceKm, 'oneline' => $v->oneline],
            array_slice($ctx->recentVerdicts, 0, 5),
        );

        return [
            'name' => $ctx->user->firstName(),
            'vibe' => $ctx->vibeState,
            'load' => $ctx->load,
            'recent_runs' => $verdictSummary,
            'date' => $ctx->asOf->toDateString(),
            'context' => BriefingContext::forUser($ctx->user, $ctx->asOf)->toArray(),
        ];
    }
}
