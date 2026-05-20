@extends('layouts.app')

@section('content')
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-[70px]">
    <!-- Welcome -->
    <h1 class="font-sf-display text-display font-semibold text-midnight">Halo, {{ $user->full_name }}</h1>
    <p class="text-sub text-cloud mt-2 mb-[44px]">Selamat datang di Nursing Journal</p>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-[44px]">
        <div class="card-frost rounded-card p-7">
            <p class="text-body text-cloud uppercase tracking-wider font-semibold mb-2">Unit Penugasan</p>
            <p class="font-sf-display text-heading-lg font-semibold text-midnight">
                {{ $assignedUnit ? $assignedUnit->name : 'Belum ditugaskan' }}
            </p>
        </div>
        <div class="card-frost rounded-card p-7">
            <p class="text-body text-cloud uppercase tracking-wider font-semibold mb-2">Shift Saat Ini</p>
            <p class="font-sf-display text-heading-lg font-semibold text-midnight">{{ $currentShift }}</p>
        </div>
    </div>

    <!-- Quick Access -->
    <h2 class="font-sf-display text-heading font-semibold text-midnight mb-5">Akses Cepat</h2>
    <a href="{{ route('patient-data.form') }}" class="card group hover:shadow-xl transition-shadow duration-300 block">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-vivid/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-vivid" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-body-lg font-semibold text-midnight">Input Data Pasien</h3>
                <p class="text-body text-cloud">Masukkan data pasien untuk shift Anda</p>
            </div>
            <span class="ml-auto text-ocean group-hover:underline hidden sm:inline">Buka →</span>
        </div>
    </a>

    <!-- Shift Info -->
    <div class="card-frost rounded-card p-7 mt-[44px]">
        <h3 class="text-body-lg font-semibold text-midnight mb-3">Informasi Shift</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-body text-cloud">
            <div><span class="font-semibold text-midnight">Pagi:</span> 07:00 – 13:59</div>
            <div><span class="font-semibold text-midnight">Siang:</span> 14:00 – 20:59</div>
            <div><span class="font-semibold text-midnight">Malam:</span> 21:00 – 06:59</div>
        </div>
    </div>
</div>
@endsection
