<?php

declare(strict_types=1);

namespace App\Services\Run\Story;

class VibeMatrix
{
    /**
     * @param  array{form: float, form_status: string, days_since_run: ?int, recent_pr: bool, decoupling_avg: ?float}  $signals
     */
    public function pick(array $signals): string
    {
        $status = $signals['form_status'];
        $daysSince = $signals['days_since_run'];
        $hasRecentPr = $signals['recent_pr'];
        $decoupling = $signals['decoupling_avg'];

        if ($daysSince === null || $daysSince >= 10) {
            return 'hibernating';
        }

        if ($hasRecentPr && ! in_array($status, ['fatigued', 'overreaching'], strict: true)) {
            return 'pumped';
        }

        if ($status === 'fresh') {
            return 'fresh';
        }

        if ($status === 'overreaching') {
            if ($decoupling !== null && $decoupling > 5.0) {
                return 'stretched_thin';
            }

            return 'cooked';
        }

        if ($status === 'fatigued') {
            return 'worn_down';
        }

        if ($decoupling !== null && $decoupling < 0) {
            return 'bouncy';
        }

        return 'steady';
    }
}
