<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AI\TokenUsageReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TokenUsageController extends Controller
{
    public function __construct(private readonly TokenUsageReport $report)
    {
    }

    public function show(Request $request): Response
    {
        $validated = $request->validate([
            'range' => 'sometimes|in:today,7d,30d,month,all,custom',
            'from' => 'sometimes|date_format:Y-m-d',
            'to' => 'sometimes|date_format:Y-m-d',
            'kind' => 'sometimes|string',
        ]);

        [$range, $from, $to] = $this->resolveRange($validated);
        $kind = $validated['kind'] ?? null;

        $report = $this->report->build($from, $to, $kind, includePrevious: $range !== 'all');

        return Inertia::render('AiUsage', [
            'range' => $range,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'kind' => $kind,
            'totals' => $report['totals'],
            'previousTotals' => $report['previousTotals'],
            'byKind' => $report['byKind'],
            'byUser' => $report['byUser'],
            'byDeployment' => $report['byDeployment'],
            'daily' => $report['daily'],
            'availableKinds' => $report['availableKinds'],
            'budget' => $report['budget'],
        ]);
    }

    /**
     * Resolve the relative range token to concrete dates on every request, so
     * preset links stay correct as the calendar rolls (a baked-in absolute
     * `to` would silently point at yesterday). Bare requests default to the
     * rolling last 7 days; legacy absolute `from`+`to` links (no `range`) map
     * to a `custom` range for back-compat.
     *
     * @param  array{range?:string, from?:string, to?:string}  $validated
     * @return array{0:string, 1:Carbon, 2:Carbon}
     */
    private function resolveRange(array $validated): array
    {
        $hasCustomDates = isset($validated['from'], $validated['to']);
        $range = $validated['range'] ?? ($hasCustomDates ? 'custom' : '7d');
        if ($range === 'custom' && ! $hasCustomDates) {
            $range = '7d';
        }

        $sevenDaysAgo = Carbon::today()->subDays(6)->startOfDay();

        [$from, $to] = match ($range) {
            'today' => [Carbon::today()->startOfDay(), Carbon::now()],
            '30d' => [Carbon::today()->subDays(29)->startOfDay(), Carbon::now()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            'all' => [Carbon::createFromTimestamp(0), Carbon::now()],
            'custom' => [Carbon::parse($validated['from'])->startOfDay(), Carbon::parse($validated['to'])->endOfDay()],
            default => [$sevenDaysAgo, Carbon::now()],
        };

        return [$range, $from, $to];
    }
}
