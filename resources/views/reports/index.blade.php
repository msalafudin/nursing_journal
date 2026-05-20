@extends('layouts.app')

@section('content')
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-[70px]">
    <h1 class="font-sf-display text-display font-semibold text-midnight">Laporan</h1>
    <p class="text-sub text-cloud mt-2 mb-[44px]">Analisis data pasien berdasarkan unit, shift, dan rentang tanggal</p>

    <!-- Filter -->
    <div class="card mb-8">
        <h2 class="card-title mb-5">Filter</h2>
        <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="unit_id" class="form-label">Unit</label>
                <select id="unit_id" name="unit_id" class="form-input">
                    <option value="">Semua Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="shift" class="form-label">Shift</label>
                <select id="shift" name="shift" class="form-input">
                    <option value="">Semua Shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift }}">{{ $shift }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="start_date" class="form-label">Tanggal Mulai</label>
                <input type="date" id="start_date" name="start_date" class="form-input">
            </div>
            <div>
                <label for="end_date" class="form-label">Tanggal Akhir</label>
                <input type="date" id="end_date" name="end_date" class="form-input">
            </div>
        </form>
        <div id="validationError" class="mt-4 alert alert-error hidden"><p id="validationErrorText"></p></div>
    </div>

    <!-- Chart -->
    <div class="card">
        <div id="loadingIndicator" class="flex items-center justify-center py-16 hidden">
            <div class="spinner spinner-lg"></div>
            <span class="ml-4 text-body-lg text-cloud">Memuat data...</span>
        </div>
        <div id="errorMessage" class="alert alert-error hidden"><p id="errorMessageText"></p></div>
        <div id="emptyState" class="flex flex-col items-center justify-center py-16 hidden">
            <svg class="w-16 h-16 text-steel mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-body-lg text-cloud">Tidak ada data untuk filter yang dipilih</p>
        </div>
        <div id="chartContainer" class="w-full overflow-x-auto hidden">
            <canvas id="chart" style="max-height: 400px;"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
let chartInstance = null;
let loadingTimer = null;

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start_date').value = today;
    document.getElementById('end_date').value = today;
    loadReportData();
    ['unit_id','shift','start_date','end_date'].forEach(id => document.getElementById(id).addEventListener('change', loadReportData));
});

function loadReportData() {
    const unitId = document.getElementById('unit_id').value;
    const shift = document.getElementById('shift').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    ['validationError','errorMessage','emptyState','chartContainer'].forEach(id => document.getElementById(id).classList.add('hidden'));
    document.getElementById('loadingIndicator').classList.remove('hidden');

    loadingTimer = setTimeout(() => {
        document.getElementById('loadingIndicator').classList.add('hidden');
        document.getElementById('errorMessageText').textContent = 'Waktu loading melebihi 5 detik.';
        document.getElementById('errorMessage').classList.remove('hidden');
    }, 5000);

    const params = new URLSearchParams();
    if (unitId) params.append('unit_id', unitId);
    if (shift) params.append('shift', shift);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);

    fetch(`/reports/data?${params}`)
        .then(r => r.json())
        .then(data => {
            clearTimeout(loadingTimer);
            document.getElementById('loadingIndicator').classList.add('hidden');
            if (!data.success) {
                const el = data.error?.includes('date') ? 'validationError' : 'errorMessage';
                const txt = el === 'validationError' ? 'validationErrorText' : 'errorMessageText';
                document.getElementById(txt).textContent = data.message;
                document.getElementById(el).classList.remove('hidden');
                return;
            }
            if (!data.data.length) { document.getElementById('emptyState').classList.remove('hidden'); return; }
            renderChart(data.data);
        })
        .catch(() => {
            clearTimeout(loadingTimer);
            document.getElementById('loadingIndicator').classList.add('hidden');
            document.getElementById('errorMessageText').textContent = 'Gagal memuat data.';
            document.getElementById('errorMessage').classList.remove('hidden');
        });
}

function renderChart(data) {
    const units = [...new Set(data.map(d => d.unit_name))];
    const dateMap = {};
    data.forEach(r => { if (!dateMap[r.date]) dateMap[r.date] = {}; dateMap[r.date][r.unit_name] = (dateMap[r.date][r.unit_name] || 0) + r.total_patients; });
    const sorted = Object.entries(dateMap).sort(([a],[b]) => a.localeCompare(b));
    const labels = sorted.map(([d]) => d);

    const colors = ['#0071e3','#1d1d1f','#00a1b3','#8668ff','#ed6300','#b64400'];
    const datasets = units.map((u, i) => ({
        label: u,
        data: sorted.map(([,v]) => v[u] || null),
        borderColor: colors[i % colors.length],
        backgroundColor: colors[i % colors.length] + '12',
        borderWidth: 2, fill: false, tension: 0.4,
        pointRadius: 4, pointHoverRadius: 6,
        pointBackgroundColor: colors[i % colors.length],
        pointBorderColor: '#fff', pointBorderWidth: 2,
    }));

    const ctx = document.getElementById('chart').getContext('2d');
    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top', labels: { font: { family: 'SF Pro Text, system-ui', size: 13 }, color: '#6b6c6c', usePointStyle: true, pointStyle: 'circle' } },
                tooltip: { mode: 'index', intersect: false, backgroundColor: '#1d1d1f', cornerRadius: 12, padding: 12,
                    titleFont: { family: 'SF Pro Text, system-ui', size: 13 }, bodyFont: { family: 'SF Pro Text, system-ui', size: 13 },
                    callbacks: { label: c => `${c.dataset.label}: ${c.parsed.y} pasien` } },
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f6f6' }, ticks: { color: '#6b6c6c', font: { size: 12 } }, title: { display: true, text: 'Jumlah Pasien', color: '#6b6c6c' } },
                x: { grid: { display: false }, ticks: { color: '#6b6c6c', font: { size: 12 } }, title: { display: true, text: 'Tanggal', color: '#6b6c6c' } },
            },
        },
    });
    document.getElementById('chartContainer').classList.remove('hidden');
}
</script>
@endsection
