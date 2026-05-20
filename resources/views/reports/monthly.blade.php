@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Laporan Bulanan</h1>
        <p class="text-gray-600 mt-2">Analisis tren data pasien per bulan dengan grafik candlestick</p>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Filter Laporan Bulanan</h2>
        
        <form id="filterForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Unit Filter -->
            <div>
                <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Unit
                </label>
                <select id="unit_id" name="unit_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Year Filter -->
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                    Tahun
                </label>
                <input type="number" id="year" name="year" min="2000" max="2100" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Month Filter -->
            <div>
                <label for="month" class="block text-sm font-medium text-gray-700 mb-2">
                    Bulan
                </label>
                <select id="month" name="month" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="1">Januari</option>
                    <option value="2">Februari</option>
                    <option value="3">Maret</option>
                    <option value="4">April</option>
                    <option value="5">Mei</option>
                    <option value="6">Juni</option>
                    <option value="7">Juli</option>
                    <option value="8">Agustus</option>
                    <option value="9">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
            </div>
        </form>

        <!-- Validation Error Message -->
        <div id="validationError" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md hidden">
            <p class="text-red-800" id="validationErrorText"></p>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="flex items-center justify-center py-12 hidden">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            <span class="ml-4 text-gray-600">Memuat data...</span>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-md hidden">
            <p class="text-red-800" id="errorMessageText"></p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="flex flex-col items-center justify-center py-12 hidden">
            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <p class="text-gray-600 text-lg">Tidak ada data untuk bulan yang dipilih</p>
        </div>

        <!-- Chart Container -->
        <div id="chartContainer" class="w-full overflow-x-auto hidden">
            <canvas id="chart" style="max-height: 400px;"></canvas>
        </div>
    </div>
</div>

<!-- Include Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
    let chartInstance = null;

    // Initialize filter form with current date
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        document.getElementById('year').value = now.getFullYear();
        document.getElementById('month').value = now.getMonth() + 1;

        // Load initial data
        loadMonthlyData();

        // Add event listeners for filter changes
        document.getElementById('unit_id').addEventListener('change', loadMonthlyData);
        document.getElementById('year').addEventListener('change', loadMonthlyData);
        document.getElementById('month').addEventListener('change', loadMonthlyData);
    });

    function loadMonthlyData() {
        const unitId = document.getElementById('unit_id').value;
        const year = document.getElementById('year').value;
        const month = document.getElementById('month').value;

        // Clear previous messages
        document.getElementById('validationError').classList.add('hidden');
        document.getElementById('errorMessage').classList.add('hidden');
        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('chartContainer').classList.add('hidden');

        // Show loading indicator
        document.getElementById('loadingIndicator').classList.remove('hidden');

        // Build query parameters
        const params = new URLSearchParams();
        params.append('unit_id', unitId);
        params.append('year', year);
        params.append('month', month);

        // Fetch data
        fetch(`/reports/monthly?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingIndicator').classList.add('hidden');

                if (!data.success) {
                    document.getElementById('errorMessageText').textContent = data.message || 'Terjadi kesalahan saat memuat data';
                    document.getElementById('errorMessage').classList.remove('hidden');
                    return;
                }

                if (data.data.length === 0) {
                    document.getElementById('emptyState').classList.remove('hidden');
                    return;
                }

                // Render chart
                renderCandleChart(data.data);
            })
            .catch(error => {
                document.getElementById('loadingIndicator').classList.add('hidden');
                document.getElementById('errorMessageText').textContent = 'Gagal memuat data. Silakan coba lagi.';
                document.getElementById('errorMessage').classList.remove('hidden');
                console.error('Error:', error);
            });
    }

    function renderCandleChart(data) {
        // Prepare data for chart
        const labels = data.map(item => item.date);
        const opens = data.map(item => item.open);
        const highs = data.map(item => item.high);
        const lows = data.map(item => item.low);
        const closes = data.map(item => item.close);

        // Create chart
        const ctx = document.getElementById('chart').getContext('2d');
        
        // Destroy existing chart if it exists
        if (chartInstance) {
            chartInstance.destroy();
        }

        // Create custom candle chart using bar chart
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'High',
                        data: highs,
                        borderColor: '#10b981',
                        backgroundColor: '#d1fae5',
                        borderWidth: 1,
                        order: 2,
                    },
                    {
                        label: 'Low',
                        data: lows,
                        borderColor: '#ef4444',
                        backgroundColor: '#fee2e2',
                        borderWidth: 1,
                        order: 3,
                    },
                    {
                        label: 'Open',
                        data: opens,
                        borderColor: '#3b82f6',
                        backgroundColor: '#dbeafe',
                        borderWidth: 1,
                        order: 1,
                    },
                    {
                        label: 'Close',
                        data: closes,
                        borderColor: '#f59e0b',
                        backgroundColor: '#fef3c7',
                        borderWidth: 1,
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y + ' pasien';
                                }
                                return label;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Pasien',
                        },
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tanggal',
                        },
                    },
                },
            },
        });

        // Display chart
        document.getElementById('chartContainer').classList.remove('hidden');
    }
</script>
@endsection
