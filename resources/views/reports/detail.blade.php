@extends('layouts.app')

@section('content')
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-[70px]">
    <h1 class="font-sf-display text-display font-semibold text-midnight">Detail Spesialis</h1>
    <p class="text-sub text-cloud mt-2 mb-[44px]">Grafik data pasien per spesialis berdasarkan unit dan rentang tanggal</p>

    <!-- Filter -->
    <div class="card mb-8">
        <h2 class="card-title mb-5">Filter</h2>
        <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="unit_id" class="form-label">Unit <span class="text-red-500">*</span></label>
                <select id="unit_id" name="unit_id" class="form-input" required>
                    <option value="">Pilih Unit</option>
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

    <!-- Info Box -->
    <div id="infoBox" class="mb-8 p-4 rounded-xl border border-ocean/20 bg-ocean/5 hidden">
        <p class="text-body text-midnight">
            <strong>Keterangan:</strong> Unit ini menggunakan sistem <em>hybrid</em> (sensus + mutasi).
        </p>
        <ul class="mt-2 text-body text-cloud list-disc list-inside space-y-1">
            <li><strong>Sensus</strong> — jumlah pasien saat ini per spesialis. Report mengambil data dari <strong>shift terakhir</strong> hari itu (tidak dijumlah antar shift).</li>
            <li><strong>Mutasi</strong> — pergerakan pasien (masuk baru, pulang, dll). Report <strong>menjumlahkan</strong> semua shift dalam sehari.</li>
        </ul>
    </div>

    <!-- Chart Sensus -->
    <div class="card" id="sensusCard">
        <h2 class="card-title mb-4" id="sensusChartTitle">Grafik Sensus per Spesialis</h2>
        <div id="loadingIndicator" class="flex items-center justify-center py-16 hidden">
            <div class="spinner spinner-lg"></div>
            <span class="ml-4 text-body-lg text-cloud">Memuat data...</span>
        </div>
        <div id="errorMessage" class="alert alert-error hidden"><p id="errorMessageText"></p></div>
        <div id="selectUnitState" class="flex flex-col items-center justify-center py-16">
            <svg class="w-16 h-16 text-steel mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
            </svg>
            <p class="text-body-lg text-cloud">Pilih unit terlebih dahulu untuk melihat grafik spesialis</p>
        </div>
        <div id="emptyState" class="flex flex-col items-center justify-center py-16 hidden">
            <svg class="w-16 h-16 text-steel mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-body-lg text-cloud">Tidak ada data untuk filter yang dipilih</p>
        </div>
        <div id="sensusChartContainer" class="w-full overflow-x-auto hidden">
            <canvas id="sensusChart" style="max-height: 420px;"></canvas>
        </div>
    </div>

    <!-- Chart Mutasi -->
    <div class="card mt-8 hidden" id="mutasiCard">
        <h2 class="card-title mb-4">Grafik Mutasi Pasien</h2>
        <div id="mutasiChartContainer" class="w-full overflow-x-auto">
            <canvas id="mutasiChart" style="max-height: 420px;"></canvas>
        </div>
    </div>

    <!-- Tabel Rata-rata -->
    <div class="card mt-8">
        <h2 class="card-title mb-5">Rata-rata per Kategori</h2>
        <div id="tableEmpty" class="flex flex-col items-center justify-center py-8 hidden">
            <p class="text-body-lg text-cloud">Tidak ada data untuk dihitung</p>
        </div>
        <div id="tableContainer" class="hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-steel/20">
                            <th class="py-3 px-4 text-sub font-medium text-cloud">Kategori</th>
                            <th class="py-3 px-4 text-sub font-medium text-cloud text-right">Tipe</th>
                            <th class="py-3 px-4 text-sub font-medium text-cloud text-right">Total</th>
                            <th class="py-3 px-4 text-sub font-medium text-cloud text-right">Jumlah Hari</th>
                            <th class="py-3 px-4 text-sub font-medium text-cloud text-right">Rata-rata / Hari</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
let sensusChartInstance = null;
let mutasiChartInstance = null;
let loadingTimer = null;

// Label mapping
const fieldLabels = {
    // Rawat Inap - Sensus
    'sensus_anak': 'Anak',
    'sensus_dalam': 'Dalam',
    'sensus_saraf': 'Saraf',
    'sensus_obsgyn': 'Obsgyn',
    'sensus_bedah': 'Bedah',
    // Rawat Inap - Mutasi
    'masuk_baru': 'Masuk Baru',
    'pasien_pulang': 'Pulang',
    'jumlah_inden': 'Inden',
    'jumlah_rpl': 'RPL',
    // ICU/HCU
    'jumlah_pasien_anak': 'Anak',
    'jumlah_pasien_dalam': 'Dalam',
    'jumlah_pasien_saraf': 'Saraf',
    'jumlah_pasien_obsgyn': 'Obsgyn',
    'jumlah_pasien_bedah': 'Bedah',
    'jumlah_pasien_inden': 'Inden',
    'jumlah_pasien_pulang': 'Pulang',
    // IGD
    'jumlah_pasien_rawat_inap': 'Rawat Inap',
    'jumlah_pasien_rawat_jalan': 'Rawat Jalan',
    'jumlah_pasien_pulang_paksa': 'Pulang Paksa',
    // VK
    'jumlah_pasien_vk': 'Pasien VK',
    // Rawat Jalan
    'jumlah_poli_obgyn': 'Poli Obgyn',
    'jumlah_poli_dalam': 'Poli Dalam',
    'jumlah_poli_anak': 'Poli Anak',
    'jumlah_poli_bedah': 'Poli Bedah',
    'jumlah_poli_saraf': 'Poli Saraf',
    'jumlah_poli_fisioterapi': 'Poli Fisioterapi',
};

// Fields yang termasuk sensus (snapshot, ambil shift terakhir)
const sensusFields = ['sensus_anak', 'sensus_dalam', 'sensus_saraf', 'sensus_obsgyn', 'sensus_bedah'];

// Fields yang termasuk mutasi (dijumlahkan antar shift)
const mutasiFields = ['masuk_baru', 'pasien_pulang', 'jumlah_inden', 'jumlah_rpl'];

// Shift order for determining "last shift"
const shiftOrder = { 'Pagi': 1, 'Siang': 2, 'Malam': 3 };

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const startDate = new Date(today);
    startDate.setDate(today.getDate() - 6);

    document.getElementById('start_date').value = formatDate(startDate);
    document.getElementById('end_date').value = formatDate(today);

    ['unit_id','shift','start_date','end_date'].forEach(id =>
        document.getElementById(id).addEventListener('change', loadDetailData)
    );
});

function formatDate(d) {
    return d.toISOString().split('T')[0];
}

function loadDetailData() {
    const unitId = document.getElementById('unit_id').value;
    const shift = document.getElementById('shift').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    // Hide all states
    ['validationError','errorMessage','emptyState','sensusChartContainer','selectUnitState','tableContainer','tableEmpty'].forEach(id =>
        document.getElementById(id).classList.add('hidden')
    );
    document.getElementById('mutasiCard').classList.add('hidden');
    document.getElementById('infoBox').classList.add('hidden');

    if (!unitId) {
        document.getElementById('selectUnitState').classList.remove('hidden');
        return;
    }

    if (!startDate || !endDate) {
        document.getElementById('validationErrorText').textContent = 'Tanggal mulai dan akhir harus diisi.';
        document.getElementById('validationError').classList.remove('hidden');
        return;
    }

    document.getElementById('loadingIndicator').classList.remove('hidden');

    loadingTimer = setTimeout(() => {
        document.getElementById('loadingIndicator').classList.add('hidden');
        document.getElementById('errorMessageText').textContent = 'Waktu loading melebihi 5 detik.';
        document.getElementById('errorMessage').classList.remove('hidden');
    }, 5000);

    const params = new URLSearchParams();
    params.append('unit_id', unitId);
    if (shift) params.append('shift', shift);
    params.append('start_date', startDate);
    params.append('end_date', endDate);

    fetch(`/reports/detail-data?${params}`)
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

            if (!data.data.length) {
                document.getElementById('emptyState').classList.remove('hidden');
                document.getElementById('tableEmpty').classList.remove('hidden');
                return;
            }

            // Detect if this unit has hybrid data (sensus + mutasi)
            const hasHybrid = data.data.some(r => sensusFields.some(f => f in r.details));

            if (hasHybrid) {
                document.getElementById('infoBox').classList.remove('hidden');
                renderHybridCharts(data.data);
            } else {
                renderSimpleChart(data.data);
            }

            renderTable(data.data, hasHybrid);
        })
        .catch(() => {
            clearTimeout(loadingTimer);
            document.getElementById('loadingIndicator').classList.add('hidden');
            document.getElementById('errorMessageText').textContent = 'Gagal memuat data.';
            document.getElementById('errorMessage').classList.remove('hidden');
        });
}

/**
 * Render charts for hybrid units (sensus + mutasi).
 * Sensus: ambil shift terakhir per hari.
 * Mutasi: jumlahkan semua shift per hari.
 */
function renderHybridCharts(data) {
    const shift = document.getElementById('shift').value;

    // Group data by date
    const byDate = {};
    data.forEach(r => {
        if (!byDate[r.date]) byDate[r.date] = [];
        byDate[r.date].push(r);
    });

    const sortedDates = Object.keys(byDate).sort();

    // === SENSUS: ambil shift terakhir per hari ===
    const sensusData = {};
    sortedDates.forEach(date => {
        const records = byDate[date];
        // Jika user sudah filter shift tertentu, langsung pakai data itu
        // Jika semua shift, ambil shift terakhir
        let record;
        if (shift) {
            record = records[0]; // sudah difilter dari backend
        } else {
            record = records.sort((a, b) => (shiftOrder[b.shift] || 0) - (shiftOrder[a.shift] || 0))[0];
        }
        sensusData[date] = {};
        sensusFields.forEach(f => {
            if (f in record.details) {
                sensusData[date][f] = record.details[f] || 0;
            }
        });
    });

    // === MUTASI: jumlahkan semua shift per hari ===
    const mutasiData = {};
    sortedDates.forEach(date => {
        mutasiData[date] = {};
        byDate[date].forEach(r => {
            mutasiFields.forEach(f => {
                if (f in r.details) {
                    mutasiData[date][f] = (mutasiData[date][f] || 0) + (r.details[f] || 0);
                }
            });
        });
    });

    // Render sensus chart
    const activeSensusFields = sensusFields.filter(f => sortedDates.some(d => sensusData[d][f] !== undefined));
    if (activeSensusFields.length > 0) {
        renderChart('sensusChart', sensusChartInstance, sortedDates, sensusData, activeSensusFields, 'Jumlah Pasien (Sensus)', (inst) => { sensusChartInstance = inst; });
        document.getElementById('sensusChartContainer').classList.remove('hidden');
        document.getElementById('sensusChartTitle').textContent = 'Grafik Sensus per Spesialis (Shift Terakhir/Hari)';
    }

    // Render mutasi chart
    const activeMutasiFields = mutasiFields.filter(f => sortedDates.some(d => mutasiData[d][f] !== undefined));
    if (activeMutasiFields.length > 0) {
        renderChart('mutasiChart', mutasiChartInstance, sortedDates, mutasiData, activeMutasiFields, 'Jumlah Pasien (Mutasi)', (inst) => { mutasiChartInstance = inst; });
        document.getElementById('mutasiCard').classList.remove('hidden');
    }
}

/**
 * Render chart for non-hybrid units (semua field dijumlahkan per hari).
 */
function renderSimpleChart(data) {
    const allFields = new Set();
    data.forEach(r => {
        Object.entries(r.details).forEach(([key, val]) => {
            if (typeof val === 'number') allFields.add(key);
        });
    });

    const byDate = {};
    data.forEach(r => {
        if (!byDate[r.date]) byDate[r.date] = {};
        allFields.forEach(f => {
            byDate[r.date][f] = (byDate[r.date][f] || 0) + (r.details[f] || 0);
        });
    });

    const sortedDates = Object.keys(byDate).sort();
    const fields = [...allFields];

    renderChart('sensusChart', sensusChartInstance, sortedDates, byDate, fields, 'Jumlah Pasien', (inst) => { sensusChartInstance = inst; });
    document.getElementById('sensusChartContainer').classList.remove('hidden');
    document.getElementById('sensusChartTitle').textContent = 'Grafik per Kategori';
}

/**
 * Generic chart renderer.
 */
function renderChart(canvasId, existingInstance, dates, dataMap, fields, yLabel, setInstance) {
    const colors = ['#0071e3','#ed6300','#00a1b3','#8668ff','#1d1d1f','#b64400','#34c759','#ff2d55','#5856d6','#ff9500'];

    const datasets = fields.map((field, i) => ({
        label: fieldLabels[field] || field,
        data: dates.map(d => dataMap[d][field] || 0),
        borderColor: colors[i % colors.length],
        backgroundColor: colors[i % colors.length] + '12',
        borderWidth: 2, fill: false, tension: 0.4,
        pointRadius: 4, pointHoverRadius: 6,
        pointBackgroundColor: colors[i % colors.length],
        pointBorderColor: '#fff', pointBorderWidth: 2,
    }));

    const ctx = document.getElementById(canvasId).getContext('2d');
    if (existingInstance) existingInstance.destroy();

    const instance = new Chart(ctx, {
        type: 'line',
        data: { labels: dates, datasets },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top', labels: { font: { family: 'SF Pro Text, system-ui', size: 13 }, color: '#6b6c6c', usePointStyle: true, pointStyle: 'circle' } },
                tooltip: { mode: 'index', intersect: false, backgroundColor: '#1d1d1f', cornerRadius: 12, padding: 12,
                    titleFont: { family: 'SF Pro Text, system-ui', size: 13 }, bodyFont: { family: 'SF Pro Text, system-ui', size: 13 },
                    callbacks: { label: c => `${c.dataset.label}: ${c.parsed.y} pasien` }
                },
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f6f6' }, ticks: { color: '#6b6c6c', font: { size: 12 } }, title: { display: true, text: yLabel, color: '#6b6c6c' } },
                x: { grid: { display: false }, ticks: { color: '#6b6c6c', font: { size: 12 } }, title: { display: true, text: 'Tanggal', color: '#6b6c6c' } },
            },
        },
    });
    setInstance(instance);
}

/**
 * Render the summary table.
 */
function renderTable(data, hasHybrid) {
    const shift = document.getElementById('shift').value;
    const byDate = {};
    data.forEach(r => {
        if (!byDate[r.date]) byDate[r.date] = [];
        byDate[r.date].push(r);
    });

    const sortedDates = Object.keys(byDate).sort();
    const days = sortedDates.length;
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    if (hasHybrid) {
        // Sensus fields: ambil shift terakhir per hari, lalu rata-rata
        sensusFields.forEach(field => {
            let total = 0;
            let count = 0;
            sortedDates.forEach(date => {
                const records = byDate[date];
                let record;
                if (shift) {
                    record = records[0];
                } else {
                    record = records.sort((a, b) => (shiftOrder[b.shift] || 0) - (shiftOrder[a.shift] || 0))[0];
                }
                if (record.details[field] !== undefined) {
                    total += record.details[field] || 0;
                    count++;
                }
            });
            if (count > 0) {
                appendTableRow(tbody, fieldLabels[field] || field, 'Sensus', total, count);
            }
        });

        // Mutasi fields: jumlahkan semua shift per hari
        mutasiFields.forEach(field => {
            let total = 0;
            let hasData = false;
            sortedDates.forEach(date => {
                byDate[date].forEach(r => {
                    if (r.details[field] !== undefined) {
                        total += r.details[field] || 0;
                        hasData = true;
                    }
                });
            });
            if (hasData) {
                appendTableRow(tbody, fieldLabels[field] || field, 'Mutasi', total, days);
            }
        });
    } else {
        // Non-hybrid: semua field dijumlahkan
        const allFields = new Set();
        const fieldTotals = {};
        data.forEach(r => {
            Object.entries(r.details).forEach(([key, val]) => {
                if (typeof val === 'number') {
                    allFields.add(key);
                    fieldTotals[key] = (fieldTotals[key] || 0) + val;
                }
            });
        });

        [...allFields].forEach(field => {
            appendTableRow(tbody, fieldLabels[field] || field, '-', fieldTotals[field], days);
        });
    }

    document.getElementById('tableContainer').classList.remove('hidden');
}

function appendTableRow(tbody, label, type, total, days) {
    const avg = days > 0 ? (total / days).toFixed(1) : 0;
    const tr = document.createElement('tr');
    tr.className = 'border-b border-steel/10 hover:bg-mist/50 transition-colors';
    const typeClass = type === 'Sensus' ? 'text-ocean' : type === 'Mutasi' ? 'text-[#ed6300]' : 'text-cloud';
    tr.innerHTML = `
        <td class="py-3 px-4 text-body text-midnight font-medium">${label}</td>
        <td class="py-3 px-4 text-body text-right"><span class="text-xs font-medium ${typeClass}">${type}</span></td>
        <td class="py-3 px-4 text-body text-midnight text-right">${total}</td>
        <td class="py-3 px-4 text-body text-midnight text-right">${days}</td>
        <td class="py-3 px-4 text-body text-midnight text-right font-semibold">${avg}</td>
    `;
    tbody.appendChild(tr);
}
</script>
@endsection
