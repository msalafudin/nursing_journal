@extends('layouts.app')

@section('content')
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-[70px]">
    <h1 class="font-sf-display text-display font-semibold text-midnight">Perbandingan Laporan</h1>
    <p class="text-sub text-cloud mt-2 mb-[44px]">Bandingkan data pasien antara dua rentang tanggal pada chart yang sama</p>

    <!-- Filter -->
    <div class="card mb-8">
        <h2 class="card-title mb-5">Filter</h2>
        <form id="filterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
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
            </div>

            <!-- Periode 1 -->
            <div class="p-4 rounded-xl border border-[#0071e3]/20 bg-[#0071e3]/5 mb-4">
                <h3 class="text-body font-semibold text-midnight mb-3 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#0071e3] inline-block"></span>
                    Periode 1
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date_1" class="form-label">Tanggal Mulai</label>
                        <input type="date" id="start_date_1" name="start_date_1" class="form-input">
                    </div>
                    <div>
                        <label for="end_date_1" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="end_date_1" name="end_date_1" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Periode 2 -->
            <div class="p-4 rounded-xl border border-[#ed6300]/20 bg-[#ed6300]/5">
                <h3 class="text-body font-semibold text-midnight mb-3 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#ed6300] inline-block"></span>
                    Periode 2
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date_2" class="form-label">Tanggal Mulai</label>
                        <input type="date" id="start_date_2" name="start_date_2" class="form-input">
                    </div>
                    <div>
                        <label for="end_date_2" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="end_date_2" name="end_date_2" class="form-input">
                    </div>
                </div>
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
            <canvas id="chart" style="max-height: 420px;"></canvas>
        </div>
    </div>

    <!-- Rata-rata Perbandingan -->
    <div class="card mt-8">
        <h2 class="card-title mb-5">Perbandingan Rata-rata</h2>
        <div id="averageEmpty" class="flex flex-col items-center justify-center py-8 hidden">
            <p class="text-body-lg text-cloud">Tidak ada data untuk dihitung</p>
        </div>
        <div id="averageContainer" class="hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-steel/20">
                            <th class="py-3 px-4 text-sub font-medium text-cloud">Unit</th>
                            <th class="py-3 px-4 text-sub font-medium text-cloud text-right">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#0071e3] inline-block"></span>
                                    Periode 1 (Rata-rata/Hari)
                                </span>
                            </th>
                            <th class="py-3 px-4 text-sub font-medium text-cloud text-right">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#ed6300] inline-block"></span>
                                    Periode 2 (Rata-rata/Hari)
                                </span>
                            </th>
                            <th class="py-3 px-4 text-sub font-medium text-cloud text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody id="averageTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
let chartInstance = null;
let loadingTimer = null;

document.addEventListener('DOMContentLoaded', function() {
    // Default: Periode 1 = minggu lalu, Periode 2 = minggu ini
    const today = new Date();
    const endDate1 = new Date(today);
    endDate1.setDate(today.getDate() - 7);
    const startDate1 = new Date(endDate1);
    startDate1.setDate(endDate1.getDate() - 6);

    const startDate2 = new Date(today);
    startDate2.setDate(today.getDate() - 6);

    document.getElementById('start_date_1').value = formatDate(startDate1);
    document.getElementById('end_date_1').value = formatDate(endDate1);
    document.getElementById('start_date_2').value = formatDate(startDate2);
    document.getElementById('end_date_2').value = formatDate(today);

    loadCompareData();

    ['unit_id','shift','start_date_1','end_date_1','start_date_2','end_date_2'].forEach(id =>
        document.getElementById(id).addEventListener('change', loadCompareData)
    );
});

function formatDate(d) {
    return d.toISOString().split('T')[0];
}

function loadCompareData() {
    const unitId = document.getElementById('unit_id').value;
    const shift = document.getElementById('shift').value;
    const startDate1 = document.getElementById('start_date_1').value;
    const endDate1 = document.getElementById('end_date_1').value;
    const startDate2 = document.getElementById('start_date_2').value;
    const endDate2 = document.getElementById('end_date_2').value;

    // Hide all states
    ['validationError','errorMessage','emptyState','chartContainer','averageContainer','averageEmpty'].forEach(id =>
        document.getElementById(id).classList.add('hidden')
    );

    // Client-side validation
    if (!startDate1 || !endDate1 || !startDate2 || !endDate2) {
        document.getElementById('validationErrorText').textContent = 'Semua tanggal harus diisi.';
        document.getElementById('validationError').classList.remove('hidden');
        return;
    }
    if (startDate1 > endDate1 || startDate2 > endDate2) {
        document.getElementById('validationErrorText').textContent = 'Tanggal mulai harus lebih kecil atau sama dengan tanggal akhir.';
        document.getElementById('validationError').classList.remove('hidden');
        return;
    }

    document.getElementById('loadingIndicator').classList.remove('hidden');

    loadingTimer = setTimeout(() => {
        document.getElementById('loadingIndicator').classList.add('hidden');
        document.getElementById('errorMessageText').textContent = 'Waktu loading melebihi 5 detik.';
        document.getElementById('errorMessage').classList.remove('hidden');
    }, 5000);

    // Fetch both periods in parallel
    const buildParams = (start, end) => {
        const params = new URLSearchParams();
        if (unitId) params.append('unit_id', unitId);
        if (shift) params.append('shift', shift);
        params.append('start_date', start);
        params.append('end_date', end);
        return params;
    };

    Promise.all([
        fetch(`/reports/data?${buildParams(startDate1, endDate1)}`).then(r => r.json()),
        fetch(`/reports/data?${buildParams(startDate2, endDate2)}`).then(r => r.json()),
    ])
    .then(([res1, res2]) => {
        clearTimeout(loadingTimer);
        document.getElementById('loadingIndicator').classList.add('hidden');

        if (!res1.success) {
            document.getElementById('validationErrorText').textContent = 'Periode 1: ' + res1.message;
            document.getElementById('validationError').classList.remove('hidden');
            return;
        }
        if (!res2.success) {
            document.getElementById('validationErrorText').textContent = 'Periode 2: ' + res2.message;
            document.getElementById('validationError').classList.remove('hidden');
            return;
        }

        if (!res1.data.length && !res2.data.length) {
            document.getElementById('emptyState').classList.remove('hidden');
            document.getElementById('averageEmpty').classList.remove('hidden');
            return;
        }

        renderCompareChart(res1.data, res2.data, startDate1, endDate1, startDate2, endDate2);
        renderCompareAverage(res1.data, res2.data);
    })
    .catch(() => {
        clearTimeout(loadingTimer);
        document.getElementById('loadingIndicator').classList.add('hidden');
        document.getElementById('errorMessageText').textContent = 'Gagal memuat data.';
        document.getElementById('errorMessage').classList.remove('hidden');
    });
}

function renderCompareChart(data1, data2, startDate1, endDate1, startDate2, endDate2) {
    // Aggregate total patients per day (across all units)
    function aggregateByDay(data) {
        const map = {};
        data.forEach(r => {
            map[r.date] = (map[r.date] || 0) + r.total_patients;
        });
        return Object.entries(map).sort(([a],[b]) => a.localeCompare(b));
    }

    const period1 = aggregateByDay(data1);
    const period2 = aggregateByDay(data2);

    // Use day index (Hari ke-1, Hari ke-2, ...) as x-axis labels
    const maxLen = Math.max(period1.length, period2.length);
    const labels = Array.from({ length: maxLen }, (_, i) => `Hari ke-${i + 1}`);

    const formatRange = (s, e) => `${s} s/d ${e}`;

    const datasets = [
        {
            label: `Periode 1 (${formatRange(startDate1, endDate1)})`,
            data: period1.map(([, v]) => v),
            borderColor: '#0071e3',
            backgroundColor: '#0071e312',
            borderWidth: 2.5, fill: false, tension: 0.4,
            pointRadius: 5, pointHoverRadius: 7,
            pointBackgroundColor: '#0071e3',
            pointBorderColor: '#fff', pointBorderWidth: 2,
        },
        {
            label: `Periode 2 (${formatRange(startDate2, endDate2)})`,
            data: period2.map(([, v]) => v),
            borderColor: '#ed6300',
            backgroundColor: '#ed630012',
            borderWidth: 2.5, fill: false, tension: 0.4,
            pointRadius: 5, pointHoverRadius: 7,
            pointBackgroundColor: '#ed6300',
            pointBorderColor: '#fff', pointBorderWidth: 2,
        },
    ];

    const ctx = document.getElementById('chart').getContext('2d');
    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { family: 'SF Pro Text, system-ui', size: 13 }, color: '#6b6c6c', usePointStyle: true, pointStyle: 'circle' }
                },
                tooltip: {
                    mode: 'index', intersect: false,
                    backgroundColor: '#1d1d1f', cornerRadius: 12, padding: 12,
                    titleFont: { family: 'SF Pro Text, system-ui', size: 13 },
                    bodyFont: { family: 'SF Pro Text, system-ui', size: 13 },
                    callbacks: {
                        title: function(items) {
                            const idx = items[0].dataIndex;
                            let title = items[0].label;
                            const date1 = period1[idx] ? period1[idx][0] : '-';
                            const date2 = period2[idx] ? period2[idx][0] : '-';
                            return `${title}\nP1: ${date1} | P2: ${date2}`;
                        },
                        label: c => `${c.dataset.label.split('(')[0].trim()}: ${c.parsed.y} pasien`
                    }
                },
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f6f6' }, ticks: { color: '#6b6c6c', font: { size: 12 } }, title: { display: true, text: 'Jumlah Pasien', color: '#6b6c6c' } },
                x: { grid: { display: false }, ticks: { color: '#6b6c6c', font: { size: 12 } }, title: { display: true, text: 'Hari ke-', color: '#6b6c6c' } },
            },
        },
    });
    document.getElementById('chartContainer').classList.remove('hidden');
}

function renderCompareAverage(data1, data2) {
    function calcStats(data) {
        const unitStats = {};
        data.forEach(r => {
            if (!unitStats[r.unit_name]) unitStats[r.unit_name] = { total: 0, dates: new Set() };
            unitStats[r.unit_name].total += r.total_patients;
            unitStats[r.unit_name].dates.add(r.date);
        });
        return unitStats;
    }

    const stats1 = calcStats(data1);
    const stats2 = calcStats(data2);
    const allUnits = [...new Set([...Object.keys(stats1), ...Object.keys(stats2)])].sort();

    const tbody = document.getElementById('averageTableBody');
    tbody.innerHTML = '';

    let totalP1 = 0, totalP2 = 0, daysP1 = new Set(), daysP2 = new Set();

    allUnits.forEach(unit => {
        const s1 = stats1[unit] || { total: 0, dates: new Set() };
        const s2 = stats2[unit] || { total: 0, dates: new Set() };
        const avg1 = s1.dates.size > 0 ? (s1.total / s1.dates.size) : 0;
        const avg2 = s2.dates.size > 0 ? (s2.total / s2.dates.size) : 0;
        const diff = avg2 - avg1;

        totalP1 += s1.total;
        totalP2 += s2.total;
        s1.dates.forEach(d => daysP1.add(d));
        s2.dates.forEach(d => daysP2.add(d));

        const diffClass = diff > 0 ? 'text-green-600' : diff < 0 ? 'text-red-600' : 'text-cloud';
        const diffIcon = diff > 0 ? '↑' : diff < 0 ? '↓' : '—';

        const tr = document.createElement('tr');
        tr.className = 'border-b border-steel/10 hover:bg-mist/50 transition-colors';
        tr.innerHTML = `
            <td class="py-3 px-4 text-body text-midnight font-medium">${unit}</td>
            <td class="py-3 px-4 text-body text-midnight text-right">${avg1.toFixed(1)}</td>
            <td class="py-3 px-4 text-body text-midnight text-right">${avg2.toFixed(1)}</td>
            <td class="py-3 px-4 text-body text-right font-semibold ${diffClass}">${diffIcon} ${Math.abs(diff).toFixed(1)}</td>
        `;
        tbody.appendChild(tr);
    });

    // Total row
    const grandAvg1 = daysP1.size > 0 ? (totalP1 / daysP1.size) : 0;
    const grandAvg2 = daysP2.size > 0 ? (totalP2 / daysP2.size) : 0;
    const grandDiff = grandAvg2 - grandAvg1;
    const grandDiffClass = grandDiff > 0 ? 'text-green-600' : grandDiff < 0 ? 'text-red-600' : 'text-cloud';
    const grandDiffIcon = grandDiff > 0 ? '↑' : grandDiff < 0 ? '↓' : '—';

    const totalRow = document.createElement('tr');
    totalRow.className = 'border-t-2 border-midnight/20 bg-mist/30';
    totalRow.innerHTML = `
        <td class="py-3 px-4 text-body text-midnight font-semibold">Total Semua Unit</td>
        <td class="py-3 px-4 text-body text-midnight text-right font-semibold">${grandAvg1.toFixed(1)}</td>
        <td class="py-3 px-4 text-body text-midnight text-right font-semibold">${grandAvg2.toFixed(1)}</td>
        <td class="py-3 px-4 text-body text-right font-semibold ${grandDiffClass}">${grandDiffIcon} ${Math.abs(grandDiff).toFixed(1)}</td>
    `;
    tbody.appendChild(totalRow);

    document.getElementById('averageContainer').classList.remove('hidden');
}
</script>
@endsection
