<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('reports the generated TS enums are in sync with the PHP enums', function (): void {
    expect(Artisan::call('typescript:enums', ['--check' => true]))->toBe(0);
})->group('structure');

it('emits a string union and value array for each backed enum', function (): void {
    $generated = (string) file_get_contents(resource_path('js/types/generated.ts'));

    expect($generated)
        ->toContain("export type Rarity = 'common' | 'uncommon' | 'rare' | 'epic' | 'legendary';")
        ->toContain('export const RARITY_VALUES = [')
        ->toContain('export type AnalysisStatus =')
        ->toContain('export type AnalysisType =')
        ->toContain('export type PrCategory =');
});

it('fails the check when the target file is stale', function (): void {
    // --path points at a scratch file instead of the real committed
    // resources/js/types/generated.ts, so this never touches (and can never
    // corrupt) version-controlled content.
    $path = tempnam(sys_get_temp_dir(), 'ts-enums-stale-');
    file_put_contents($path, "stale\n");

    try {
        expect(Artisan::call('typescript:enums', ['--check' => true, '--path' => $path]))->toBe(1);
    } finally {
        unlink($path);
    }
});
