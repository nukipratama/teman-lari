<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\ActivityDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-05-11 12:00:00'));
afterEach(fn () => Carbon::setTestNow());

it('renders the Briefing Temari hero on the dashboard', function (): void {
    $user = User::factory()->create();
    // Single recent run so the briefing has a streak label and a vibe state.
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today(),
        'trimp_edwards' => 60.0,
    ]);

    $this->actingAs($user)->get('/dashboard')
        ->assertSuccessful()
        ->assertSeeText('Briefing Temari')
        ->assertSeeText('Vibe hari ini')
        ->assertSeeText('Rencana');
});

it('shows "Lari hari ini" streak chip when there is a run today', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today(),
        'trimp_edwards' => 60.0,
    ]);

    $this->actingAs($user)->get('/dashboard')
        ->assertSuccessful()
        ->assertSeeText('Lari hari ini');
});

it('escalates streak chip past 4 days away', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today()->subDays(6),
        'trimp_edwards' => 60.0,
    ]);

    $this->actingAs($user)->get('/dashboard')
        ->assertSuccessful()
        ->assertSeeText('Sudah 6 hari nih');
});

it('renders the hibernating briefing for a user with no runs', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')
        ->assertSuccessful()
        ->assertSeeText('Briefing Temari')
        ->assertSeeText('Hibernasi')
        ->assertSeeText('saatnya keluar pintu lagi');
});

it('still renders the existing KPI + empty-state surfaces alongside the briefing', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')
        ->assertSuccessful()
        ->assertSeeText('Briefing Temari')
        ->assertSeeText('Belum ada aktivitas tersinkron');
});
