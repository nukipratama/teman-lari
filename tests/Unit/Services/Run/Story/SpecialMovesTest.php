<?php

declare(strict_types=1);

use App\Services\Run\Story\SpecialMoves;

it('returns the default move (Lari Santai) when nothing special matched', function (): void {
    expect((new SpecialMoves())->pick([], []))->toBe(SpecialMoves::DEFAULT_MOVE);
});

it('returns Tancap di Akhir on PR + negative split', function (): void {
    $move = (new SpecialMoves())->pick(
        ['negative_split' => true],
        ['pr_set' => true, 'distance_m' => 5_000],
    );

    expect($move)->toBe('Tancap di Akhir');
});

it('returns Jauh Santuy for a long run held sub-Z3', function (): void {
    $move = (new SpecialMoves())->pick(
        [
            'time_in_zone_pct' => ['Z1' => 8, 'Z2' => 89, 'Z3' => 3, 'Z4' => 0, 'Z5' => 0],
        ],
        ['distance_m' => 15_000, 'pr_set' => false],
    );

    expect($move)->toBe('Jauh Santuy');
});

it('returns Tahan Tempo when Z3 share exceeds 60 percent', function (): void {
    $move = (new SpecialMoves())->pick(
        [
            'time_in_zone_pct' => ['Z2' => 25, 'Z3' => 65, 'Z4' => 10],
        ],
        ['distance_m' => 10_000, 'pr_set' => false],
    );

    expect($move)->toBe('Tahan Tempo');
});

it('returns Kaki Mesin when cadence stays mostly above 175', function (): void {
    $move = (new SpecialMoves())->pick(
        [
            'cadence_distribution_pct' => ['<165' => 5, '165-175' => 25, '>175' => 70],
        ],
        ['distance_m' => 5_000, 'pr_set' => false],
    );

    expect($move)->toBe('Kaki Mesin');
});

it('returns Adem Ayem on Z2-dominant runs', function (): void {
    $move = (new SpecialMoves())->pick(
        [
            'time_in_zone_pct' => ['Z1' => 10, 'Z2' => 85, 'Z3' => 5],
        ],
        ['distance_m' => 5_000, 'pr_set' => false],
    );

    expect($move)->toBe('Adem Ayem');
});

it('returns Pecah Rekor on a PR without negative split', function (): void {
    $move = (new SpecialMoves())->pick(
        ['negative_split' => false, 'time_in_zone_pct' => ['Z3' => 40, 'Z4' => 30]],
        ['pr_set' => true, 'distance_m' => 5_000],
    );

    expect($move)->toBe('Pecah Rekor');
});

it('returns Anti Ngedrop on minimal cadence drop at 5k+', function (): void {
    $move = (new SpecialMoves())->pick(
        ['cadence_drop_spm' => 0.5, 'time_in_zone_pct' => ['Z2' => 40, 'Z3' => 40]],
        ['distance_m' => 8_000, 'pr_set' => false],
    );

    expect($move)->toBe('Anti Ngedrop');
});
