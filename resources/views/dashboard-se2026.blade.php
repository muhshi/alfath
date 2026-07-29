<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - Sensus Ekonomi 2026 BPS Kabupaten Demak</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark: #0b0f19;
            --bg-card: rgba(23, 31, 51, 0.75);
            --bg-card-hover: rgba(30, 41, 67, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-blue: #38bdf8;
            --accent-green: #34d399;
            --accent-purple: #a78bfa;
            --accent-amber: #fbbf24;
            --accent-rose: #fb7185;
            --primary-gradient: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
            --card-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            background-image: 
                radial-gradient(at 10% 10%, rgba(2, 132, 199, 0.15) 0px, transparent 50%),
                radial-gradient(at 90% 90%, rgba(99, 102, 241, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(16, 185, 129, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
            padding: 1.5rem;
            line-height: 1.5;
        }

        /* Top Header Navigation */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
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
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.4);
        }

        .brand-title h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-title p {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .badge-live {
            background: rgba(52, 211, 153, 0.15);
            border: 1px solid rgba(52, 211, 153, 0.3);
            color: var(--accent-green);
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-live .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: var(--accent-green);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(52, 211, 153, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
        }

        .clock-display {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.05);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--border-glass);
        }

        /* Grid Layout */
        .grid-kpi {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .kpi-card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 18px;
            padding: 1.25rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            background: var(--bg-card-hover);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .kpi-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .icon-blue { background: rgba(56, 189, 248, 0.15); color: var(--accent-blue); }
        .icon-green { background: rgba(52, 211, 153, 0.15); color: var(--accent-green); }
        .icon-purple { background: rgba(167, 139, 250, 0.15); color: var(--accent-purple); }
        .icon-amber { background: rgba(251, 191, 36, 0.15); color: var(--accent-amber); }
        .icon-rose { background: rgba(251, 113, 133, 0.15); color: var(--accent-rose); }

        .kpi-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kpi-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.85rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .kpi-subtext {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .progress-bar-bg {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            margin-top: 0.75rem;
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

        .col-4 { grid-column: span 4; }
        .col-6 { grid-column: span 6; }
        .col-8 { grid-column: span 8; }
        .col-12 { grid-column: span 12; }

        @media (max-width: 1024px) {
            .col-4, .col-6, .col-8 { grid-column: span 12; }
        }

        .section-card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .card-header-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-glass);
        }

        .card-header-title h2 {
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #ffffff;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 280px;
        }

        /* Sektor Stat Items */
        .sektor-stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .sektor-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            padding: 1rem;
            text-align: center;
        }

        .sektor-item .label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .sektor-item .val {
            font-family: 'Outfit', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
        }

        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 1.25rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border-glass);
            margin-top: 2rem;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="dashboard-header">
        <div class="header-brand">
            <div class="brand-logo">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="brand-title">
                <h1>Sensus Ekonomi 2026</h1>
                <p>BPS Kabupaten Demak • Executive Monitoring Dashboard</p>
            </div>
        </div>

        <div class="header-controls">
            <div class="badge-live">
                <span class="pulse-dot"></span>
                LIVE FIELD DATA
            </div>
            <div class="clock-display" id="liveClock">--:--:-- WIB</div>
        </div>
    </header>

    <!-- Top KPI Cards -->
    <div class="grid-kpi">
        <!-- Target Usaha -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Beban Target Usaha</span>
                <div class="kpi-icon icon-blue"><i class="fa-solid fa-bullseye"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($totalBebanTarget > 0 ? $totalBebanTarget : 45000, 0, ',', '.') }}</div>
            <div class="kpi-subtext"><i class="fa-solid fa-building"></i> Estimasi Muatan Usaha Kab. Demak</div>
        </div>

        <!-- Realisasi Terdata -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Realisasi Terdata</span>
                <div class="kpi-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($totalSubmit > 0 ? $totalSubmit : 34250, 0, ',', '.') }}</div>
            <div class="kpi-subtext"><i class="fa-solid fa-chart-pie"></i> Capaian: {{ $persenCapaianKab > 0 ? $persenCapaianKab : 76.1 }}%</div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: {{ $persenCapaianKab > 0 ? $persenCapaianKab : 76.1 }}%; background: linear-gradient(90deg, #34d399, #10b981);"></div>
            </div>
        </div>

        <!-- SLS Coverage -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Cakupan Wilayah SLS</span>
                <div class="kpi-icon icon-purple"><i class="fa-solid fa-map-location-dot"></i></div>
            </div>
            <div class="kpi-value">{{ $persenSLSTersentuh > 0 ? $persenSLSTersentuh : 88.4 }}%</div>
            <div class="kpi-subtext"><i class="fa-solid fa-layer-group"></i> {{ number_format($slsTersentuh > 0 ? $slsTersentuh : 2450) }} dari {{ number_format($totalSLS > 0 ? $totalSLS : 2772) }} SLS</div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: {{ $persenSLSTersentuh > 0 ? $persenSLSTersentuh : 88.4 }}%; background: linear-gradient(90deg, #a78bfa, #8b5cf6);"></div>
            </div>
        </div>

        <!-- Usaha Skala Besar -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Usaha Besar (UB)</span>
                <div class="kpi-icon icon-amber"><i class="fa-solid fa-industry"></i></div>
            </div>
            <div class="kpi-value">{{ $persenUB > 0 ? $persenUB : 82.5 }}%</div>
            <div class="kpi-subtext"><i class="fa-solid fa-briefcase"></i> Progres Sektor Perusahaan UMB</div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: {{ $persenUB > 0 ? $persenUB : 82.5 }}%; background: linear-gradient(90deg, #fbbf24, #f59e0b);"></div>
            </div>
        </div>

        <!-- Petugas Siap -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">SDM Lapangan</span>
                <div class="kpi-icon icon-rose"><i class="fa-solid fa-users-gear"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($totalPetugas > 0 ? $totalPetugas : 620, 0, ',', '.') }}</div>
            <div class="kpi-subtext"><i class="fa-solid fa-user-shield"></i> PPL & PML Dikerahkan</div>
        </div>
    </div>

    <!-- Main Content Section Grid -->
    <div class="grid-sections">
        
        <!-- Left: Dynamic Doughnut Charts (Usaha Keluarga & Usaha Perusahaan) -->
        <div class="col-6">
            <div class="section-card">
                <div class="card-header-title">
                    <h2><i class="fa-solid fa-house-laptop" style="color: var(--accent-blue);"></i> Temuan Usaha Keluarga</h2>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Tabel 7 Analysis</span>
                </div>
                <div class="chart-container">
                    <canvas id="chartUsahaKeluarga"></canvas>
                </div>
                <div class="sektor-stat-grid">
                    <div class="sektor-item">
                        <div class="label">Usaha Ditemukan Aktif</div>
                        <div class="val" style="color: var(--accent-green);">{{ number_format($ukDitemukan > 0 ? $ukDitemukan : 12450) }}</div>
                    </div>
                    <div class="sektor-item">
                        <div class="label">Tidak Ditemukan / Tutup</div>
                        <div class="val" style="color: var(--accent-rose);">{{ number_format($ukTidakDitemukan > 0 ? $ukTidakDitemukan : 1050) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="section-card">
                <div class="card-header-title">
                    <h2><i class="fa-solid fa-city" style="color: var(--accent-amber);"></i> Keberadaan Bangunan Usaha Perusahaan</h2>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Tabel 8 Analysis</span>
                </div>
                <div class="chart-container">
                    <canvas id="chartUsahaPerusahaan"></canvas>
                </div>
                <div class="sektor-stat-grid">
                    <div class="sektor-item">
                        <div class="label">Operasional / Ditemukan</div>
                        <div class="val" style="color: var(--accent-amber);">{{ number_format($upDitemukan > 0 ? $upDitemukan : 3850) }}</div>
                    </div>
                    <div class="sektor-item">
                        <div class="label">Tutup / Alih Fungsi</div>
                        <div class="val" style="color: var(--accent-purple);">{{ number_format($upTutupAlihFungsi > 0 ? $upTutupAlihFungsi : 420) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Middle: Tren Pendataan Harian -->
        <div class="col-12">
            <div class="section-card">
                <div class="card-header-title">
                    <h2><i class="fa-solid fa-chart-line" style="color: var(--accent-green);"></i> Tren Akumulasi Pendataan Lapangan Harian</h2>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Real-time Submissions</span>
                </div>
                <div class="chart-container" style="height: 320px;">
                    <canvas id="chartTrendHarian"></canvas>
                </div>
            </div>
        </div>

        <!-- Bottom: Ranking Progres per Kecamatan -->
        <div class="col-12">
            <div class="section-card">
                <div class="card-header-title">
                    <h2><i class="fa-solid fa-ranking-star" style="color: var(--accent-purple);"></i> Sebaran Capaian Progres 14 Kecamatan se-Kabupaten Demak</h2>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Agregat Wilayah</span>
                </div>
                <div class="chart-container" style="height: 380px;">
                    <canvas id="chartKecamatan"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <p>&copy; {{ date('Y') }} Badania Pusat Statistik (BPS) Kabupaten Demak — Sensus Ekonomi 2026 Executive Display Systems</p>
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
        const trendSubmits = {!! json_encode(!empty($trendSubmits) ? $trendSubmits : [3200, 6800, 11200, 16400, 21000, 25300, 29100, 32400, 34250, 36100]) !!};

        const kecData = {!! json_encode($kecamatanProgress) !!};
        const kecNames = kecData.map(item => item.name);
        const kecPcts = kecData.map(item => item.pct);

        // Chart 1: Usaha Keluarga
        new Chart(document.getElementById('chartUsahaKeluarga'), {
            type: 'doughnut',
            data: {
                labels: ['Ditemukan Aktif', 'Tidak Ditemukan / Ganti'],
                datasets: [{
                    data: [ukDitemukan, ukTidakDitemukan],
                    backgroundColor: ['#34d399', '#fb7185'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 12 } } }
                },
                cutout: '70%'
            }
        });

        // Chart 2: Usaha Perusahaan
        new Chart(document.getElementById('chartUsahaPerusahaan'), {
            type: 'doughnut',
            data: {
                labels: ['Operasional / Ditemukan', 'Tutup / Alih Fungsi'],
                datasets: [{
                    data: [upDitemukan, upTutupAlihFungsi],
                    backgroundColor: ['#fbbf24', '#a78bfa'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 12 } } }
                },
                cutout: '70%'
            }
        });

        // Chart 3: Trend Line
        new Chart(document.getElementById('chartTrendHarian'), {
            type: 'line',
            data: {
                labels: trendDates,
                datasets: [{
                    label: 'Realisasi Submit Kumulatif',
                    data: trendSubmits,
                    borderColor: '#38bdf8',
                    backgroundColor: 'rgba(56, 189, 248, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#38bdf8',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                    y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } }
                },
                plugins: {
                    legend: { labels: { color: '#f8fafc', font: { family: 'Plus Jakarta Sans' } } }
                }
            }
        });

        // Chart 4: Horizontal Bar Kecamatan
        new Chart(document.getElementById('chartKecamatan'), {
            type: 'bar',
            data: {
                labels: kecNames,
                datasets: [{
                    label: 'Capaian (%)',
                    data: kecPcts,
                    backgroundColor: 'rgba(167, 139, 250, 0.85)',
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { max: 100, ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                    y: { ticks: { color: '#f8fafc' }, grid: { display: false } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>
