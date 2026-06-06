<?php

declare(strict_types=1);

use App\Enums\Badge;

it('returns a non-empty label for every case', function (Badge $badge): void {
    expect($badge->label())->toBeString()->not->toBe('');
})->with(Badge::cases());

it('exposes a representative label', function (): void {
    expect(Badge::HariPanas->label())->toBe('🔥 Tahan Gerah')
        ->and(Badge::HariSpesial->label())->toBe('🎉 Hari Spesial');
});

it('lists the tracked badges for unlock criteria', function (): void {
    expect(Badge::tracked())->toBe([
        Badge::AnakMalam,
        Badge::AnakPagi,
        Badge::PejuangHujan,
        Badge::NegativeSplit,
        Badge::HariPanas,
        Badge::Z2Master,
    ]);
});

it('builds a slug to label catalog covering every case', function (): void {
    $labels = Badge::labels();

    expect($labels)->toHaveCount(count(Badge::cases()));

    foreach (Badge::cases() as $badge) {
        expect($labels)->toHaveKey($badge->value)
            ->and($labels[$badge->value])->toBe($badge->label());
    }
});
