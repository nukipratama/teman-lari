@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#FAFAF7] dark:bg-[#0a0a0a]">
    <header class="border-b border-black/5 bg-white/60 backdrop-blur dark:border-white/5 dark:bg-[#0a0a0a]/60">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-lime-500 text-[#0a0a0a]">
                    <iconify-icon icon="mdi:run-fast" width="20" height="20" aria-hidden="true"></iconify-icon>
                </span>
                <span class="font-semibold tracking-tight">Teman Lari</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <div class="text-sm font-medium leading-tight">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Strava ID {{ auth()->user()->stravaConnection?->strava_athlete_id }}</div>
                </div>
                @if (auth()->user()->avatar_url)
                    <img src="{{ auth()->user()->avatar_url }}" alt="" class="h-9 w-9 rounded-full ring-2 ring-black/5 dark:ring-white/10">
                @else
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-lime-500/15 text-sm font-semibold text-lime-600 dark:text-lime-400">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                @endif
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-black/10 px-3 py-1.5 text-sm transition hover:border-black/40 dark:border-white/10 dark:hover:border-white/40">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-10">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Halo, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-200">Berikut ringkasan lari kamu.</p>
            </div>
        </div>

        <section class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
            @foreach ([
                ['label' => 'Total jarak', 'value' => '0,0 km', 'sub' => 'minggu ini'],
                ['label' => 'Total durasi', 'value' => '0j 0m', 'sub' => 'minggu ini'],
                ['label' => 'Aktivitas', 'value' => '0', 'sub' => 'minggu ini'],
            ] as $stat)
                <div class="rounded-2xl border border-black/5 bg-white p-5 dark:border-white/5 dark:bg-[#161615]">
                    <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight">{{ $stat['value'] }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">{{ $stat['sub'] }}</div>
                </div>
            @endforeach
        </section>

        <section class="mt-6 rounded-2xl border border-dashed border-black/10 bg-white/40 p-10 text-center dark:border-white/10 dark:bg-[#161615]/40">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-lime-500/15 text-lime-600 dark:text-lime-400">
                <iconify-icon icon="mdi:run-fast" width="28" height="28" aria-hidden="true"></iconify-icon>
            </div>
            <h2 class="mt-4 text-base font-semibold">Belum ada aktivitas tersinkron</h2>
            <p class="mx-auto mt-1 max-w-sm text-sm text-gray-600 dark:text-gray-200">
                Sinkronisasi Strava akan muncul di sini setelah integrasi dinyalakan.
            </p>
        </section>
    </main>
</div>
@endsection
