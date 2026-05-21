<?php

declare(strict_types=1);

use App\Services\AI\TemariPersona;

/*
 * MANUAL VOICE SPOT-CHECK (run after meaningful persona edits):
 *  - Hit /dashboard logged in as a user with recent activity and read the
 *    Briefing Temari card. Voice should be first-person Temari ("aku" /
 *    "lo"), warm, never preachy, never "kamu harus".
 *  - Open a recent run at /aktivitas/{id} and read all 4 thread entries
 *    (Cerita lari ini, Terjemahan teknis, Split highlight, HR zone). Same
 *    voice across all four — they're produced by different narrators but
 *    should sound like the same character.
 *  - Open /catatan and read the weekly recap narrative + trend caption.
 *  - Open /rekor and read the PR context flavor lines.
 *  - Open /kartu and read the card flavor on the spotlight card.
 *
 *  Voice drift = persona prompt needs tightening. Reasoning lives in the
 *  persona prompt body comments — keep it the single source of truth.
 */

it('exposes the full persona system message', function (): void {
    $prompt = TemariPersona::systemPrompt();

    expect($prompt)->toBeString()->not->toBe('');
});

it('introduces Temari in first person and as a teman lari', function (): void {
    $prompt = TemariPersona::systemPrompt();

    expect($prompt)
        ->toContain('Aku adalah Temari')
        ->toContain('temen lari');
});

it('locks the address forms — aku for Temari, lo for the user', function (): void {
    $prompt = TemariPersona::systemPrompt();

    expect($prompt)
        ->toContain('Sebut diriku "aku"')
        ->toContain('Sebut pengguna "lo"');
});

it('keeps the mood vocabulary in English so narrators never translate it', function (): void {
    $prompt = TemariPersona::systemPrompt();

    foreach (['cooked', 'fresh', 'pumped', 'bouncy', 'fatigued', 'overreaching', 'spinning', 'worn_down', 'glow', 'hibernate'] as $mood) {
        expect($prompt)->toContain($mood);
    }
});

it('forbids markdown, em-dash, and third-person clinical phrasing', function (): void {
    $prompt = TemariPersona::systemPrompt();

    expect($prompt)
        ->toContain('JANGAN markdown')
        ->toContain('em dash')
        ->toContain('third-person');
});

it('forbids preachy / coach-mode phrasing like "lo harus"', function (): void {
    $prompt = TemariPersona::systemPrompt();

    expect($prompt)
        ->toContain('JANGAN moralize')
        ->toContain('"lo harus"');
});

it('grounds Temari in Indonesian running context', function (): void {
    $prompt = TemariPersona::systemPrompt();

    expect($prompt)
        ->toContain('Subuh lari')
        ->toContain('Heat 31°C')
        ->toContain('hujan');
});
