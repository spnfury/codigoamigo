<?php
session_start();

// Admin access control
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];

if (!in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header("Location: /");
    exit;
}

$title = "Analytics Dashboard - Admin Panel";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .kpi-card { 
            background: white; 
            border-radius: 12px; 
            padding: 24px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .kpi-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .kpi-value { 
            font-size: 2.5rem; 
            font-weight: 700; 
            margin: 12px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .kpi-label { 
            color: #6c757d; 
            font-size: 0.875rem; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .kpi-icon { 
            font-size: 2rem; 
            opacity: 0.2; 
            position: absolute; 
            right: 24px; 
            top: 24px;
        }
        .chart-container { 
            background: white; 
            border-radius: 12px; 
            padding: 24px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }
        .site-selector { 
            background: white; 
            border-radius: 50px; 
            padding: 4px;
            display: inline-flex;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .site-btn { 
            border: none; 
            background: transparent; 
            padding: 12px 32px; 
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }
        .site-btn.active { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .date-selector { 
            background: white; 
            border-radius: 8px; 
            padding: 8px 16px;
            border: 1px solid #e0e0e0;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .spinner { 
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .comparison-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        .badge-up { background: #d4edda; color: #155724; }
        .badge-down { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/public/admin_dashboard.php">
                <i class="fas fa-arrow-left me-2"></i> Admin Panel
            </a>
            <span class="navbar-text text-white">
                <i class="fas fa-chart-line me-2"></i> Analytics Dashboard
            </span>
        </div>
    </nav>

    <div class="container-fluid px-4">
        
        <!-- Header Controls -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="fw-bold mb-3">
                    <i class="fas fa-chart-bar me-2" style="color: #667eea;"></i>
                    Analytics Overview
                </h2>
            </div>
            <div class="col-md-6 text-end">
                <!-- Site Selector -->
                <div class="site-selector me-3 d-inline-flex">
                    <button class="site-btn active" data-site="codigoamigo" onclick="switchSite('codigoamigo')">
                        <i class="fas fa-tag me-2"></i> CodigoAmigo
                    </button>
                    <button class="site-btn" data-site="casinuevo" onclick="switchSite('casinuevo')">
                        <i class="fas fa-shopping-cart me-2"></i> Casinuevo
                    </button>
                </div>
                
                <!-- Date Range Selector -->
                <select class="date-selector" id="dateRangeSelector" onchange="changeDateRange()">
                    <option value="7daysAgo">Últimos 7 días</option>
                    <option value="30daysAgo" selected>Últimos 30 días</option>
                    <option value="90daysAgo">Últimos 90 días</option>
                </select>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row mb-4" id="kpiCards">
            <div class="col-md-2">
                <div class="kpi-card position-relative">
                    <i class="fas fa-users kpi-icon text-primary"></i>
                    <div class="kpi-label">Usuarios</div>
                    <div class="kpi-value" id="kpi-users">-</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card position-relative">
                    <i class="fas fa-chart-line kpi-icon text-success"></i>
                    <div class="kpi-label">Sesiones</div>
                    <div class="kpi-value" id="kpi-sessions">-</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card position-relative">
                    <i class="fas fa-eye kpi-icon text-info"></i>
                    <div class="kpi-label">Páginas Vistas</div>
                    <div class="kpi-value" id="kpi-pageviews">-</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card position-relative">
                    <i class="fas fa-clock kpi-icon text-warning"></i>
                    <div class="kpi-label">Duración Media</div>
                    <div class="kpi-value" id="kpi-duration">-</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card position-relative">
                    <i class="fas fa-percentage kpi-icon text-danger"></i>
                    <div class="kpi-label">Tasa de Rebote</div>
                    <div class="kpi-value" id="kpi-bounce">-</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card position-relative">
                    <i class="fas fa-trophy kpi-icon text-success"></i>
                    <div class="kpi-label">Conversiones</div>
                    <div class="kpi-value" id="kpi-conversions">-</div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-chart-area me-2 text-primary"></i>
                        Tendencia de Usuarios
                    </h5>
                    <canvas id="usersChart" height="80"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-mobile-alt me-2 text-info"></i>
                        Dispositivos
                    </h5>
                    <canvas id="devicesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-file-alt me-2 text-success"></i>
                        Top 10 Páginas
                    </h5>
                    <canvas id="topPagesChart" height="120"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-share-alt me-2 text-warning"></i>
                        Fuentes de Tráfico
                    </h5>
                    <canvas id="trafficSourcesChart" height="120"></canvas>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentSite = 'codigoamigo';
        let currentDateRange = '30daysAgo';
        let charts = {};

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadAnalyticsData();
        });

        function switchSite(site) {
            currentSite = site;
            
            // Update button states
            document.querySelectorAll('.site-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-site="${site}"]`).classList.add('active');
            
            loadAnalyticsData();
        }

        function changeDateRange() {
            currentDateRange = document.getElementById('dateRangeSelector').value;
            loadAnalyticsData();
        }

        async function loadAnalyticsData() {
            showLoading();
            
            try {
                const response = await fetch('../ajax/get_analytics_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        site: currentSite,
                        dateRange: currentDateRange,
                        action: 'all'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    updateKPIs(data.overview.totals);
                    updateUsersChart(data.overview.timeline);
                    updateTopPagesChart(data.topPages);
                    updateTrafficSourcesChart(data.trafficSources);
                    updateDevicesChart(data.devices);
                } else {
                    alert('Error: ' + (data.error || 'No se pudieron cargar los datos'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            } finally {
                hideLoading();
            }
        }

        function updateKPIs(totals) {
            document.getElementById('kpi-users').textContent = formatNumber(totals.users);
            document.getElementById('kpi-sessions').textContent = formatNumber(totals.sessions);
            document.getElementById('kpi-pageviews').textContent = formatNumber(totals.pageviews);
            document.getElementById('kpi-duration').textContent = formatDuration(totals.avgSessionDuration);
            document.getElementById('kpi-bounce').textContent = totals.bounceRate.toFixed(1) + '%';
            document.getElementById('kpi-conversions').textContent = formatNumber(totals.conversions);
        }

        function updateUsersChart(timeline) {
            const ctx = document.getElementById('usersChart').getContext('2d');
            
            if (charts.users) charts.users.destroy();
            
            charts.users = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timeline.map(d => formatDate(d.date)),
                    datasets: [{
                        label: 'Usuarios',
                        data: timeline.map(d => d.users),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Sesiones',
                        data: timeline.map(d => d.sessions),
                        borderColor: '#764ba2',
                        backgroundColor: 'rgba(118, 75, 162, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        function updateTopPagesChart(pages) {
            const ctx = document.getElementById('topPagesChart').getContext('2d');
            
            if (charts.topPages) charts.topPages.destroy();
            
            const top10 = pages.slice(0, 10);
            
            charts.topPages = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: top10.map(p => truncate(p.title, 30)),
                    datasets: [{
                        label: 'Páginas Vistas',
                        data: top10.map(p => p.pageviews),
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: '#667eea',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        function updateTrafficSourcesChart(sources) {
            const ctx = document.getElementById('trafficSourcesChart').getContext('2d');
            
            if (charts.traffic) charts.traffic.destroy();
            
            const top5 = sources.slice(0, 5);
            
            charts.traffic = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: top5.map(s => s.source),
                    datasets: [{
                        data: top5.map(s => s.sessions),
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#f093fb',
                            '#4facfe',
                            '#43e97b'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
        }

        function updateDevicesChart(devices) {
            const ctx = document.getElementById('devicesChart').getContext('2d');
            
            if (charts.devices) charts.devices.destroy();
            
            charts.devices = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: devices.map(d => d.device),
                    datasets: [{
                        data: devices.map(d => d.users),
                        backgroundColor: ['#667eea', '#764ba2', '#f093fb']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        // Utility functions
        function formatNumber(num) {
            return new Intl.NumberFormat('es-ES').format(num);
        }

        function formatDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}m ${secs}s`;
        }

        function formatDate(dateStr) {
            const year = dateStr.substring(0, 4);
            const month = dateStr.substring(4, 6);
            const day = dateStr.substring(6, 8);
            return `${day}/${month}`;
        }

        function truncate(str, length) {
            return str.length > length ? str.substring(0, length) + '...' : str;
        }

        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
    </script>
</body>
</html>
