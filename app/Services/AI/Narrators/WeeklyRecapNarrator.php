<?php

declare(strict_types=1);

namespace App\Services\AI\Narrators;

use App\Models\WeeklySnapshot;
use App\Services\AI\ChatCallOptions;
use App\Services\AI\StructuredChatCaller;
use App\Services\Run\Metrics\PaceCalculator;

class WeeklyRecapNarrator
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Tugas: 2-3 kalimat baca kondisi minggu pengguna, maksimal 65 kata.

        Cakupan: rangkum VIBE minggu ini pakai data konkret. Sebutkan 1-2
        angka yang menonjol (total km, jumlah lari, perubahan pace, atau
        pergeseran form). Tutup dengan 1 observasi atau dorongan halus.

        Sesuaikan tone ke form_status:
        - fresh: energik, mengajak manfaatkan. "Kamu lagi fresh, minggu depan
          bisa coba quality session."
        - optimal: positif, apresiasi konsistensi. "Balance-nya pas, pertahanin."
        - fatigued: empatik, sarankan istirahat bukan push. "Minggu ini cukup
          berat, istirahat dulu gak rugi."
        - overreaching: concerned, warning halus. "Load-nya tinggi, mundur
          sedikit minggu depan."

        Gunakan data yang tersedia:
        - runs, distance_km: bandingkan secara implisit (banyak/sedikit/
          konsisten).
        - pace_sec_per_km: catatan pace kalau ada perubahan menonjol.
        - weekly_trimp: indikator beban mingguan.
        - form (CTL - ATL): positif = segar, negatif = lelah.
        - monotony: > 2 = terlalu seragam, ajak variasi.
        - strain: > 500 = berat.

        ANTI-PATTERN:
        - Mengulang angka mentah tanpa konteks.
        - "Minggu ini ritme kamu cukup teratur" tanpa spesifik.
        - Memberi jadwal ("minggu depan lari 4 kali"). Dorongan, bukan rencana.
        PROMPT;

    public function __construct(private readonly StructuredChatCaller $caller)
    {
    }

    public function generate(WeeklySnapshot $snapshot): string
    {
        $paceSecPerKm = PaceCalculator::secPerKm(
            $snapshot->distance_km === null ? null : $snapshot->distance_km * 1000,
            $snapshot->moving_time_sec,
        );

        $decoded = $this->caller->call(
            kind: 'weekly_recap',
            systemPrompt: self::SYSTEM_PROMPT,
            context: [
                'week_ending' => $snapshot->week_ending->toDateString(),
                'runs' => $snapshot->runs,
                'distance_km' => $snapshot->distance_km,
                'pace_sec_per_km' => $paceSecPerKm,
                'weekly_trimp' => $snapshot->weekly_trimp,
                'ctl_42d' => $snapshot->ctl_42d,
                'atl_7d' => $snapshot->atl_7d,
                'form' => $snapshot->form,
                'form_status' => $snapshot->form_status,
                'monotony' => $snapshot->monotony,
                'strain' => $snapshot->strain,
            ],
            schemaName: 'TemariWeeklyRecap',
            requiredKeys: ['narrative'],
            options: new ChatCallOptions(temperature: 0.7, userId: $snapshot->user_id, maxTokens: 1500),
        );

        return (string) $decoded['narrative'];
    }
}
