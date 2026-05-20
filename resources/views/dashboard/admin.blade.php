@extends('layouts.app')

@section('content')
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-[70px]">
    <!-- Welcome -->
    <h1 class="font-sf-display text-display font-semibold text-midnight">Halo, {{ $user->full_name }}</h1>
    <p class="text-sub text-cloud mt-2 mb-[44px]">Administrator Dashboard</p>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-[44px]">
        <div class="card-frost rounded-card p-7">
            <p class="text-body text-cloud uppercase tracking-wider font-semibold mb-2">Total Unit</p>
            <p class="font-sf-display text-display-lg font-semibold text-midnight">{{ $totalUnits }}</p>
        </div>
        <div class="card-frost rounded-card p-7">
            <p class="text-body text-cloud uppercase tracking-wider font-semibold mb-2">Pengguna Aktif</p>
            <p class="font-sf-display text-display-lg font-semibold text-midnight">{{ $totalActiveUsers }}</p>
        </div>
    </div>

    <!-- Management -->
    <h2 class="font-sf-display text-heading font-semibold text-midnight mb-5">Manajemen</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <a href="{{ route('units.index') }}" class="card group hover:shadow-xl transition-shadow duration-300">
            <div class="w-10 h-10 rounded-full bg-frost flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-midnight" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h3 class="text-body-lg font-semibold text-midnight">Manajemen Unit</h3>
            <p class="text-body text-cloud mt-1">Kelola unit keperawatan</p>
            <span class="text-body text-ocean mt-3 inline-block group-hover:underline">Buka →</span>
        </a>

        <a href="{{ route('users.index') }}" class="card group hover:shadow-xl transition-shadow duration-300">
            <div class="w-10 h-10 rounded-full bg-frost flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-midnight" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-body-lg font-semibold text-midnight">Manajemen Pengguna</h3>
            <p class="text-body text-cloud mt-1">Kelola akun perawat</p>
            <span class="text-body text-ocean mt-3 inline-block group-hover:underline">Buka →</span>
        </a>

        <a href="{{ route('reports.index') }}" class="card group hover:shadow-xl transition-shadow duration-300">
            <div class="w-10 h-10 rounded-full bg-vivid/10 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-vivid" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h3 class="text-body-lg font-semibold text-midnight">Laporan</h3>
            <p class="text-body text-cloud mt-1">Lihat laporan dan analisis</p>
            <span class="text-body text-ocean mt-3 inline-block group-hover:underline">Buka →</span>
        </a>
    </div>
</div>
@endsection
