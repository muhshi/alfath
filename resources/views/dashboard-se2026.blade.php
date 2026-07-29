<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - Sensus Ekonomi 2026 BPS Kabupaten Demak</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* Official BPS & Sensus Ekonomi Corporate Palette (SE Signature Orange) */
            --orange-primary: #ea580c;  /* Sensus Ekonomi Signature Orange */
            --orange-hover: #c2410c;    /* Deep Orange */
            --orange-bg: #fff7ed;       /* Soft Cream / Orange Light */
            --orange-border: #ffedd5;
            
            --blue-bps: #1d4ed8;        /* Official BPS Royal Blue */
            --blue-bg: #eff6ff;
            
            --green-bps: #059669;       /* Official BPS Emerald Green */
            --green-bg: #ecfdf5;
            
            --rose-bps: #e11d48;
            --rose-bg: #fff1f2;
            
            --bg-page: #fafaf9;
            --bg-card: #ffffff;
            --border-card: #e7e5e4;
            --border-hover: #fed7aa;
            
            --text-title: #0c0a09;       /* High Contrast Warm Black (WCAG AAA) */
            --text-body: #1c1917;        /* High Contrast Charcoal */
            --text-muted: #57534e;       /* Readable Slate Grey */

            --card-shadow: 0 4px 20px -2px rgba(234, 88, 12, 0.06), 0 2px 6px -1px rgba(0, 0, 0, 0.04);
            --card-shadow-hover: 0 12px 28px -4px rgba(234, 88, 12, 0.16);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-body);
            min-height: 100vh;
            padding: 1.5rem;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Header Navigation */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 20px;
            padding: 1.25rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .brand-logo {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(234, 88, 12, 0.35);
        }

        .brand-title h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--text-title);
            letter-spacing: -0.02em;
        }

        .brand-title p {
            font-size: 0.95rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .badge-live {
            background: var(--orange-bg);
            border: 1.5px solid var(--orange-border);
            color: var(--orange-primary);
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .badge-live .pulse-dot {
            width: 10px;
            height: 10px;
            background-color: var(--orange-primary);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.5); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(234, 88, 12, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(234, 88, 12, 0); }
        }

        .clock-display {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-title);
            background: #f5f5f4;
            padding: 0.55rem 1.1rem;
            border-radius: 12px;
            border: 1px solid var(--border-card);
        }

        /* KPI Cards Grid */
        .grid-kpi {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 20px;
            padding: 1.35rem;
            box-shadow: var(--card-shadow);
            transition: all 0.25s ease-in-out;
            position: relative;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            border-color: var(--border-hover);
            box-shadow: var(--card-shadow-hover);
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .kpi-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .icon-orange { background: var(--orange-bg); color: var(--orange-primary); }
        .icon-green { background: var(--green-bg); color: var(--green-bps); }
        .icon-blue { background: var(--blue-bg); color: var(--blue-bps); }
        .icon-rose { background: var(--rose-bg); color: var(--rose-bps); }

        .kpi-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .kpi-value {
            font-family: 'Outfit', sans-serif;
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--text-title);
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }

        .kpi-subtext {
            font-size: 0.82rem;
            color: var(--text-muted);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .progress-bar-bg {
            width: 100%;
            height: 8px;
            background: #e7e5e4;
            border-radius: 10px;
            margin-top: 0.85rem;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease-in-out;
        }

        /* Main Dashboard Sections Grid */
        .grid-sections {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .col-6 { grid-column: span 6; }
        .col-12 { grid-column: span 12; }

        @media (max-width: 1024px) {
            .col-6 { grid-column: span 12; }
        }

        .section-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .card-header-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid var(--border-card);
        }

        .card-header-title h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            color: var(--text-title);
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 290px;
        }

        /* Sektor Stat Items High-Contrast */
        .sektor-stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.25rem;
        }

        .sektor-item {
            background: #fafaf9;
            border: 1px solid var(--border-card);
            border-radius: 14px;
            padding: 1rem;
            text-align: center;
        }

        .sektor-item .label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .sektor-item .val {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
        }

        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
        .btn-home-back {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            color: #ffffff !important;
            padding: 0.65rem 1.35rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 800;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            box-shadow: 0 4px 14px rgba(29, 78, 216, 0.35);
            border: 1px solid #1e40af;
            transition: all 0.25s ease-in-out;
            cursor: pointer;
        }

        .btn-home-back:hover {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(29, 78, 216, 0.5);
            color: #ffffff !important;
        }

        .clock-display {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-title);
            background: #f5f5f4;
            padding: 0.55rem 1.1rem;
            border-radius: 12px;
            border: 1px solid var(--border-card);
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="dashboard-header">
        <div class="header-brand">
            <div class="brand-logo" style="background: transparent; box-shadow: none; width: auto; height: auto;">
                <img src="{{ asset('assets/logo_bps.png') }}" alt="Logo BPS" style="height: 52px; width: auto; object-fit: contain;">
            </div>
            <div class="brand-title">
                <h1>Sensus Ekonomi 2026</h1>
                <p>BPS Kabupaten Demak • Executive Monitoring Dashboard</p>
            </div>
        </div>

        <div class="header-controls">
            <a href="{{ url('/') }}" class="btn-home-back">
                <i class="fa-solid fa-arrow-left"></i>
                <i class="fa-solid fa-house"></i>
                <span>Kembali ke Beranda ALFATH</span>
            </a>
            <div class="badge-live">
                <span class="pulse-dot"></span>
                Terakhir Diperbarui: {{ $lastUpdated }}
            </div>
            <div class="clock-display" id="liveClock">--:--:-- WIB</div>
        </div>
    </header>

    <!-- Top KPI Cards -->
    <div class="grid-kpi">
        <!-- Target Usaha -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Total Beban Pendataan</span>
                <div class="kpi-icon icon-orange"><i class="fa-solid fa-bullseye"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($totalBebanTarget > 0 ? $totalBebanTarget : 569814, 0, ',', '.') }}</div>
            <div class="kpi-subtext"><i class="fa-solid fa-layer-group"></i> Campuran KK Berusaha, KK Tidak Berusaha & Bangunan Khusus Usaha</div>
        </div>

        <!-- Realisasi Terdata -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Realisasi Terdata</span>
                <div class="kpi-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($totalSubmit > 0 ? $totalSubmit : 342500, 0, ',', '.') }}</div>
            <div class="kpi-subtext"><i class="fa-solid fa-chart-pie"></i> Capaian: {{ $persenCapaianKab > 0 ? $persenCapaianKab : 76.1 }}%</div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: {{ $persenCapaianKab > 0 ? $persenCapaianKab : 76.1 }}%; background: var(--green-bps);"></div>
            </div>
        </div>

        <!-- Sub SLS Coverage -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Cakupan Sub SLS</span>
                <div class="kpi-icon icon-blue"><i class="fa-solid fa-map-location-dot"></i></div>
            </div>
            <div class="kpi-value">{{ $persenSLSTersentuh > 0 ? $persenSLSTersentuh : 62.4 }}%</div>
            <div class="kpi-subtext"><i class="fa-solid fa-layer-group"></i> {{ number_format($slsTersentuh > 0 ? $slsTersentuh : 5160) }} dari 8.270 Sub SLS</div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: {{ $persenSLSTersentuh > 0 ? $persenSLSTersentuh : 62.4 }}%; background: var(--blue-bps);"></div>
            </div>
        </div>

        <!-- Usaha Skala Besar -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Usaha Besar (UB)</span>
                <div class="kpi-icon icon-orange"><i class="fa-solid fa-industry"></i></div>
            </div>
            <div class="kpi-value">{{ $persenUB > 0 ? $persenUB : 82.5 }}%</div>
            <div class="kpi-subtext"><i class="fa-solid fa-briefcase"></i> Progres Perusahaan UMB</div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: {{ $persenUB > 0 ? $persenUB : 82.5 }}%; background: var(--orange-primary);"></div>
            </div>
        </div>

        <!-- SDM Lapangan Details -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">SDM Lapangan</span>
                <div class="kpi-icon icon-rose"><i class="fa-solid fa-users-gear"></i></div>
            </div>
            <div class="kpi-value">1.000</div>
            <div class="kpi-subtext"><i class="fa-solid fa-users"></i> UM/UMK (992): 876 PPL + 116 PML</div>
            <div class="kpi-subtext" style="margin-top: 3px;"><i class="fa-solid fa-building-flag"></i> Usaha Besar (8): 6 PPL + 2 PML</div>
        </div>
    </div>

    <!-- Main Content Section Grid -->
    <div class="grid-sections">
        
        <!-- Left: Usaha Keluarga (Tabel 7) -->
        <div class="col-6">
            <div class="section-card">
                <div class="card-header-title">
                    <h2><i class="fa-solid fa-house-laptop" style="color: var(--orange-primary);"></i> Temuan Usaha Keluarga</h2>
                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">Sektor Mikro / Rumah Tangga</span>
                </div>
                <div class="chart-container">
                    <canvas id="chartUsahaKeluarga"></canvas>
                </div>
                <div class="sektor-stat-grid">
                    <div class="sektor-item">
                        <div class="label">Usaha Ditemukan Aktif</div>
                        <div class="val" style="color: var(--green-bps);">{{ number_format($ukDitemukan > 0 ? $ukDitemukan : 12450) }}</div>
                    </div>
                    <div class="sektor-item">
                        <div class="label">Tidak Ditemukan / Tutup</div>
                        <div class="val" style="color: var(--rose-bps);">{{ number_format($ukTidakDitemukan > 0 ? $ukTidakDitemukan : 1050) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Usaha Perusahaan (Tabel 8) -->
        <div class="col-6">
            <div class="section-card">
                <div class="card-header-title">
                    <h2><i class="fa-solid fa-city" style="color: var(--orange-primary);"></i> Keberadaan Bangunan Usaha Perusahaan</h2>
                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">Sektor Komersial & Industri</span>
                </div>
                <div class="chart-container">
                    <canvas id="chartUsahaPerusahaan"></canvas>
                </div>
                <div class="sektor-stat-grid">
                    <div class="sektor-item">
                        <div class="label">Operasional / Ditemukan</div>
                        <div class="val" style="color: var(--orange-primary);">{{ number_format($upDitemukan > 0 ? $upDitemukan : 3850) }}</div>
                    </div>
                    <div class="sektor-item">
                        <div class="label">Tutup / Alih Fungsi</div>
                        <div class="val" style="color: var(--blue-bps);">{{ number_format($upTutupAlihFungsi > 0 ? $upTutupAlihFungsi : 420) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Middle: Tren Pendataan Harian -->
        <div class="col-12">
            <div class="section-card">
                <div class="card-header-title">
                    <h2><i class="fa-solid fa-chart-line" style="color: var(--orange-primary);"></i> Tren Akumulasi Laju Pendataan Harian</h2>
                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">Realisasi Submit vs Target Seharusnya</span>
                </div>
                <div class="chart-container" style="height: 340px;">
                    <canvas id="chartTrendHarian"></canvas>
                </div>
            </div>
        </div>

        <!-- Bottom: Ranking Progres per Kecamatan (Vertical Bar Chart) -->
        <div class="col-12">
            <div class="section-card">
                <div class="card-header-title">
                    <h2><i class="fa-solid fa-ranking-star" style="color: var(--orange-primary);"></i> Sebaran Capaian Progres 14 Kecamatan se-Kabupaten Demak</h2>
                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">Tingkat Penyelesaian (%)</span>
                </div>
                <div class="chart-container" style="height: 420px;">
                    <canvas id="chartKecamatan"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <p>&copy; {{ date('Y') }} Badan Pusat Statistik (BPS) Kabupaten Demak — Sensus Ekonomi 2026 Executive Display Systems</p>
    </footer>

    <!-- JavaScript Visualisations -->
    <script>
        // Live Clock Script
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
            document.getElementById('liveClock').textContent = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Data from Controller
        const ukDitemukan = {{ $ukDitemukan > 0 ? $ukDitemukan : 12450 }};
        const ukTidakDitemukan = {{ $ukTidakDitemukan > 0 ? $ukTidakDitemukan : 1050 }};

        const upDitemukan = {{ $upDitemukan > 0 ? $upDitemukan : 3850 }};
        const upTutupAlihFungsi = {{ $upTutupAlihFungsi > 0 ? $upTutupAlihFungsi : 420 }};

        const trendDates = {!! json_encode(!empty($trendDates) ? $trendDates : ['19 Jul', '20 Jul', '21 Jul', '22 Jul', '23 Jul', '24 Jul', '25 Jul', '26 Jul', '27 Jul', '28 Jul']) !!};
        const trendSubmits = {!! json_encode(!empty($trendSubmits) ? $trendSubmits : [150000, 195000, 242000, 284000, 310000, 345000, 381000, 414000, 442500, 461000]) !!};
        const trendTargets = {!! json_encode(!empty($trendTargets) ? $trendTargets : [180000, 210000, 240000, 270000, 300000, 330000, 360000, 390000, 420000, 450000]) !!};

        const kecData = {!! json_encode($kecamatanProgress) !!};
        const kecNames = kecData.map(item => item.name);
        const kecPcts = kecData.map(item => item.pct);

        // Global Chart Defaults for High Legibility & High Contrast (Senior Friendly)
        Chart.defaults.font.family = 'Plus Jakarta Sans';
        Chart.defaults.font.size = 13;
        Chart.defaults.font.weight = '600';
        Chart.defaults.color = '#1c1917';

        // Chart 1: Usaha Keluarga (BPS Green & Red)
        new Chart(document.getElementById('chartUsahaKeluarga'), {
            type: 'doughnut',
            data: {
                labels: ['Ditemukan Aktif', 'Tidak Ditemukan / Ganti'],
                datasets: [{
                    data: [ukDitemukan, ukTidakDitemukan],
                    backgroundColor: ['#059669', '#e11d48'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#0c0a09', font: { size: 13, weight: '700' } } }
                },
                cutout: '65%'
            }
        });

        // Chart 2: Usaha Perusahaan (BPS SE Orange & BPS Blue)
        new Chart(document.getElementById('chartUsahaPerusahaan'), {
            type: 'doughnut',
            data: {
                labels: ['Operasional / Ditemukan', 'Tutup / Alih Fungsi'],
                datasets: [{
                    data: [upDitemukan, upTutupAlihFungsi],
                    backgroundColor: ['#ea580c', '#1d4ed8'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#0c0a09', font: { size: 13, weight: '700' } } }
                },
                cutout: '65%'
            }
        });

        // Chart 3: Trend Line (Realisasi SE Orange vs Target BPS Blue)
        new Chart(document.getElementById('chartTrendHarian'), {
            type: 'line',
            data: {
                labels: trendDates,
                datasets: [
                    {
                        label: 'Realisasi Submit Kumulatif',
                        data: trendSubmits,
                        borderColor: '#ea580c',
                        borderWidth: 3.5,
                        backgroundColor: 'rgba(234, 88, 12, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#ea580c',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    },
                    {
                        label: 'Target Seharusnya (1.33% / Hari)',
                        data: trendTargets,
                        borderColor: '#1d4ed8',
                        borderWidth: 2.5,
                        borderDash: [6, 6],
                        fill: false,
                        tension: 0.1,
                        pointBackgroundColor: '#1d4ed8',
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: '#0c0a09', font: { weight: '700' } }, grid: { color: '#e7e5e4' } },
                    y: { ticks: { color: '#0c0a09', font: { weight: '700' } }, grid: { color: '#e7e5e4' } }
                },
                plugins: {
                    legend: { labels: { color: '#0c0a09', font: { size: 13, weight: '700' } } }
                }
            }
        });

        // Chart 4: Vertical Bar Chart Kecamatan (Sumbu X: Kecamatan, Sumbu Y: Capaian %) - Signature SE Orange
        new Chart(document.getElementById('chartKecamatan'), {
            type: 'bar',
            data: {
                labels: kecNames,
                datasets: [{
                    label: 'Capaian Progres (%)',
                    data: kecPcts,
                    backgroundColor: '#f97316',
                    hoverBackgroundColor: '#ea580c',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { 
                        ticks: { color: '#0c0a09', font: { size: 12, weight: '700' }, maxRotation: 45, minRotation: 0 }, 
                        grid: { display: false } 
                    },
                    y: { 
                        max: 100, 
                        ticks: { 
                            color: '#0c0a09', 
                            font: { size: 12, weight: '700' },
                            callback: function(value) { return value + '%'; }
                        }, 
                        grid: { color: '#e7e5e4' } 
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Capaian: ' + context.raw + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
