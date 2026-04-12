<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Дашборд - Task Planner Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --bg:        #f0f2f5;
            --surface:   #ffffff;
            --surface2:  #f7f8fa;
            --border:    #dde1e7;
            --text:      #3d4452;
            --text-dim:  #8b95a5;
            --text-head: #1a1f2e;
            --accent:    #2b6be6;
            --red:       #e53935;
            --yellow:    #e8a000;
            --green:     #1e9e52;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 14px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── TOPBAR ── */
        .topbar {
            height: 44px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 8px;
            flex-shrink: 0;
        }
        .topbar-brand {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 500;
            color: var(--accent);
            letter-spacing: 0.05em;
            margin-right: 12px;
        }
        .topbar-sep { width: 1px; height: 20px; background: var(--border); }
        .topbar-btn {
            height: 28px;
            padding: 0 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background .15s, border-color .15s, color .15s;
        }
        .topbar-btn:hover { background: var(--surface2); border-color: #b0b8c8; color: var(--text-head); }
        .topbar-btn.primary { border-color: var(--accent); color: var(--accent); }
        .topbar-btn.primary:hover { background: rgba(43,107,230,.08); }
        .topbar-btn.danger { border-color: #f5c6c6; color: var(--red); }
        .topbar-btn.danger:hover { background: rgba(229,57,53,.06); border-color: var(--red); }
        .topbar-btn.active { background: var(--accent); color: white; border-color: var(--accent); }
        .topbar-btn.active:hover { background: #2358b5; color: white; }
        .topbar-spacer { flex: 1; }

        /* ── DASHBOARD AREA ── */
        .main-area {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
            display: flex;
            justify-content: center;
        }

        .dashboard-container {
            width: 100%;
            max-width: 900px;
        }

        .dashboard-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .dash-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-head);
            margin: 0;
            line-height: 1;
        }

        .dash-subtitle {
            font-size: 13px;
            color: var(--text-dim);
            margin-top: 8px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .metric-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
        }
        .metric-total::before    { background: #9ca3af; }
        .metric-done::before     { background: var(--green); }
        .metric-progress::before { background: var(--accent); }
        .metric-overdue::before  { background: var(--red); }
        .metric-avg::before      { background: #8b5cf6; }

        .metric-title {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dim);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 36px;
            font-weight: 600;
            color: var(--text-head);
            line-height: 1;
            display: flex;
            align-items: center;
        }

        .skeleton {
            background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
            width: 60px;
            height: 40px;
        }

        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .refresh-status {
            font-size: 12px;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--green);
        }

        /* ── CHART SECTION ── */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }
        @media (max-width: 900px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        .chart-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            position: relative;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-head);
            margin: 0;
        }

        .period-toggle {
            display: flex;
            border: 1px solid var(--border);
            border-radius: 4px;
            overflow: hidden;
        }

        .toggle-btn {
            background: var(--surface);
            border: none;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-dim);
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .toggle-btn.active {
            background: var(--accent);
            color: white;
        }

        .toggle-btn:not(.active):hover {
            background: var(--surface2);
            color: var(--text-head);
        }

        .chart-empty {
            position: absolute;
            top: 70px; left: 0; right: 0; bottom: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dim);
            font-size: 14px;
            font-weight: 500;
            z-index: 10;
        }


    </style>
</head>
<body>

<div class="topbar">
    <span class="topbar-brand">⬡ Task Planner</span>
    <div class="topbar-sep"></div>
    <a href="index.php" class="topbar-btn">⬅ Доски</a>
    <a href="create_task.php" class="topbar-btn primary">+ Задача</a>
    <a href="create_project.php" class="topbar-btn">+ Проект</a>
    <a href="calendar.php" class="topbar-btn" style="color:var(--accent); border-color:var(--accent);">📅 Календарь</a>
    <a href="dashboard.php" class="topbar-btn active">📊 Дашборд</a>
    <div class="topbar-spacer"></div>
    <a href="logout.php" class="topbar-btn danger">Выйти</a>
</div>

<div class="main-area">
    <div class="dashboard-container" id="dashboard-content">
        
        <div class="dashboard-header">
            <div>
                <h1 class="dash-title">Аналитика задач</h1>
                <div class="dash-subtitle">Данные за последние 12 месяцев. Показатели по всем доступным проектам.</div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="refresh-status" id="refreshStatus" data-html2canvas-ignore="true">
                    <span class="status-dot"></span> <span id="refreshText">Обновлено только что</span>
                </div>
                <button onclick="exportToCSV()" class="topbar-btn" data-html2canvas-ignore="true" style="padding: 6px 12px; height: auto;">⬇️ Скачать CSV</button>
                <button onclick="exportToPDF()" class="topbar-btn primary" data-html2canvas-ignore="true" style="padding: 6px 12px; height: auto;">📄 Скачать PDF</button>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card metric-total">
                <div class="metric-title">Всего задач</div>
                <div class="metric-value">
                    <div id="val-total" class="skeleton"></div>
                </div>
            </div>
            <div class="metric-card metric-done">
                <div class="metric-title">Выполнено</div>
                <div class="metric-value">
                    <div id="val-completed" class="skeleton"></div>
                </div>
            </div>
            <div class="metric-card metric-progress">
                <div class="metric-title">В процессе</div>
                <div class="metric-value">
                    <div id="val-progress" class="skeleton"></div>
                </div>
            </div>
            <div class="metric-card metric-overdue">
                <div class="metric-title">Просрочено</div>
                <div class="metric-value">
                    <div id="val-overdue" class="skeleton"></div>
                </div>
            </div>
            <div class="metric-card metric-avg">
                <div class="metric-title">Ср. время выполнения</div>
                <div class="metric-value" style="font-size: 28px;">
                    <div id="val-avg" class="skeleton"></div>
                </div>
            </div>
        </div>

        <!-- ГРАФИКИ -->
        <div class="charts-grid">
            <div class="chart-section">
                <div class="chart-header">
                    <h2 class="chart-title">График выполнения по дням (история)</h2>
                    <div class="period-toggle" data-html2canvas-ignore="true">
                        <button class="toggle-btn active" onclick="setPeriod('week')" id="btn-period-week">Неделя</button>
                        <button class="toggle-btn" onclick="setPeriod('month')" id="btn-period-month">Месяц</button>
                    </div>
                </div>
                
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="completionChart"></canvas>
                    <div id="chartEmptyMessage" class="chart-empty">Нет завершенных задач за выбранный период</div>
                </div>
            </div>

            <!-- ПРИОРИТЕТЫ -->
            <div class="chart-section">
                <div class="chart-header">
                    <h2 class="chart-title">Приоритеты</h2>
                </div>
                <div style="position: relative; height: 300px; width: 100%; display: flex; align-items: center; justify-content: center;">
                    <canvas id="priorityChart"></canvas>
                    <div id="priorityEmptyMessage" class="chart-empty">Нет данных за 12 месяцев</div>
                </div>
            </div>

            <!-- РАСПРЕДЕЛЕНИЕ ПО ПРОЕКТАМ -->
            <div class="chart-section" style="grid-column: 1 / -1;">
                <div class="chart-header">
                    <h2 class="chart-title">РАСПРЕДЕЛЕНИЕ ПО ПРОЕКТАМ</h2>
                </div>
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="projectsChart"></canvas>
                    <div id="projectsEmptyMessage" class="chart-empty">Вы не состоите ни в одном проекте</div>
                </div>
            </div>

            <!-- СРАВНЕНИЕ КОМАНД -->
            <div class="chart-section" style="grid-column: 1 / -1;">
                <div class="chart-header">
                    <h2 class="chart-title">Сравнение команд (Статусы по проектам)</h2>
                </div>
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="teamsChart"></canvas>
                    <div id="teamsEmptyMessage" class="chart-empty">Нет данных для сравнения</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    let dashboardData = null;

    async function loadMetrics() {
        const dot = document.querySelector('.status-dot');
        const text = document.getElementById('refreshText');
        
        try {
            dot.style.background = 'var(--yellow)';
            text.innerText = 'Обновление...';

            const response = await fetch('get_dashboard_metrics.php');
            if (response.ok) {
                const data = await response.json();
                dashboardData = data;
                
                // Удаляем скелетоны и ставим значения
                document.getElementById('val-total').className = '';
                document.getElementById('val-total').innerText = data.total;
                
                document.getElementById('val-completed').className = '';
                document.getElementById('val-completed').innerText = data.completed;
                
                document.getElementById('val-progress').className = '';
                document.getElementById('val-progress').innerText = data.in_progress;
                
                document.getElementById('val-overdue').className = '';
                document.getElementById('val-overdue').innerText = data.overdue;

                let avgMin = data.avg_minutes || 0;
                let avgText = '-';
                if (avgMin > 0) {
                    if (avgMin < 60) {
                        avgText = avgMin + ' мин';
                    } else if (avgMin < 1440) {
                        avgText = Math.floor(avgMin / 60) + ' ч';
                    } else {
                        avgText = Math.floor(avgMin / 1440) + ' дн';
                    }
                }
                document.getElementById('val-avg').className = '';
                document.getElementById('val-avg').innerText = avgText;

                if (data.priorities) {
                    renderPriorityChart(data.priorities);
                }

                if (data.projects) {
                    renderProjectsChart(data.projects);
                }

                if (data.teams) {
                    renderTeamsChart(data.teams);
                }

                dot.style.background = 'var(--green)';
                
                const now = new Date();
                const timeStr = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0') + ':' + now.getSeconds().toString().padStart(2,'0');
                text.innerText = 'Актуально на ' + timeStr;
            } else {
                throw new Error("Bad response");
            }
        } catch (e) {
            // Технические ошибки скрываем
            dot.style.background = 'var(--red)';
            text.innerText = 'Невозможно обновить';
        }
    }

    // --- Chart Logic ---
    let completionChart = null;
    let currentPeriod = 'week';

    function setPeriod(period) {
        currentPeriod = period;
        document.getElementById('btn-period-week').classList.toggle('active', period === 'week');
        document.getElementById('btn-period-month').classList.toggle('active', period === 'month');
        loadChart();
    }

    async function loadChart() {
        try {
            const response = await fetch(`get_chart_data.php?period=${currentPeriod}`);
            if (!response.ok) return;
            const data = await response.json();
            
            const emptyMsg = document.getElementById('chartEmptyMessage');
            if (!data.hasData) {
                emptyMsg.style.display = 'flex';
            } else {
                emptyMsg.style.display = 'none';
            }

            renderChart(data.labels, data.data);
        } catch (e) {
            // Игнорируем ошибки (Не показывать технические ошибки)
        }
    }

    function renderChart(labels, dataArray) {
        const ctx = document.getElementById('completionChart').getContext('2d');
        
        if (completionChart) {
            completionChart.data.labels = labels;
            completionChart.data.datasets[0].data = dataArray;
            completionChart.update();
            return;
        }

        completionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Завершено задач',
                    data: dataArray,
                    backgroundColor: 'rgba(30, 158, 82, 0.8)', // var(--green)
                    borderColor: 'rgba(30, 158, 82, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // только целые числа
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    let priorityChartInstance = null;
    function renderPriorityChart(priorities) {
        const ctx = document.getElementById('priorityChart').getContext('2d');
        const emptyMsg = document.getElementById('priorityEmptyMessage');
        
        const total = priorities.high + priorities.medium + priorities.low;
        if (total === 0) {
            emptyMsg.style.display = 'flex';
            if (priorityChartInstance) {
                priorityChartInstance.destroy();
                priorityChartInstance = null;
            }
            return;
        } else {
            emptyMsg.style.display = 'none';
        }

        const dataArray = [priorities.high, priorities.medium, priorities.low];
        
        if (priorityChartInstance) {
            priorityChartInstance.data.datasets[0].data = dataArray;
            priorityChartInstance.update();
            return;
        }

        priorityChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Высокий', 'Средний', 'Низкий'],
                datasets: [{
                    data: dataArray,
                    backgroundColor: [
                        '#e53935', // red (high)
                        '#e8a000', // yellow (medium)
                        '#1e9e52'  // green (low)
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { family: "'IBM Plex Sans', sans-serif", size: 12 }
                        }
                    }
                }
            }
        });
    }

    let projectsChartInstance = null;
    function renderProjectsChart(projects) {
        const ctx = document.getElementById('projectsChart').getContext('2d');
        const emptyMsg = document.getElementById('projectsEmptyMessage');
        
        if (!projects || !projects.labels || projects.labels.length === 0) {
            emptyMsg.style.display = 'flex';
            if (projectsChartInstance) {
                projectsChartInstance.destroy();
                projectsChartInstance = null;
            }
            return;
        } else {
            emptyMsg.style.display = 'none';
        }

        if (projectsChartInstance) {
            projectsChartInstance.data.labels = projects.labels;
            projectsChartInstance.data.datasets[0].data = projects.data;
            projectsChartInstance.update();
            return;
        }

        projectsChartInstance = new Chart(ctx, {
            type: 'bar', // Горизонтальный столбчатый график
            data: {
                labels: projects.labels,
                datasets: [{
                    label: 'Задач',
                    data: projects.data,
                    backgroundColor: 'rgba(43, 107, 230, 0.7)', // var(--accent)
                    borderColor: 'rgba(43, 107, 230, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y', // Горизонтально
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    let teamsChartInstance = null;
    function renderTeamsChart(teams) {
        const ctx = document.getElementById('teamsChart').getContext('2d');
        const emptyMsg = document.getElementById('teamsEmptyMessage');
        
        if (!teams || teams.length === 0) {
            emptyMsg.style.display = 'flex';
            if (teamsChartInstance) {
                teamsChartInstance.destroy();
                teamsChartInstance = null;
            }
            return;
        } else {
            emptyMsg.style.display = 'none';
        }

        const labels = teams.map(t => t.name);
        const dataCompleted = teams.map(t => t.completed);
        const dataInProgress = teams.map(t => t.in_progress);
        const dataOverdue = teams.map(t => t.overdue);

        if (teamsChartInstance) {
            teamsChartInstance.data.labels = labels;
            teamsChartInstance.data.datasets[0].data = dataCompleted;
            teamsChartInstance.data.datasets[1].data = dataInProgress;
            teamsChartInstance.data.datasets[2].data = dataOverdue;
            teamsChartInstance.update();
            return;
        }

        teamsChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Выполнено',
                        data: dataCompleted,
                        backgroundColor: 'rgba(30, 158, 82, 0.8)', // var(--green)
                        borderColor: 'rgba(30, 158, 82, 1)',
                        borderWidth: 1,
                        borderRadius: 2
                    },
                    {
                        label: 'В процессе',
                        data: dataInProgress,
                        backgroundColor: 'rgba(43, 107, 230, 0.8)', // var(--accent)
                        borderColor: 'rgba(43, 107, 230, 1)',
                        borderWidth: 1,
                        borderRadius: 2
                    },
                    {
                        label: 'Просрочено',
                        data: dataOverdue,
                        backgroundColor: 'rgba(229, 57, 53, 0.8)', // var(--red)
                        borderColor: 'rgba(229, 57, 53, 1)',
                        borderWidth: 1,
                        borderRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            font: { family: "'IBM Plex Sans', sans-serif", size: 12 }
                        }
                    }
                }
            }
        });
    }

    // PDF Export Functionality
    function exportToPDF() {
        const element = document.getElementById('dashboard-content');
        const opt = {
            margin:       [10, 10, 10, 10], // top, left, bottom, right
            filename:     'analytics_report.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, backgroundColor: '#f0f2f5' },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        // Notify user
        const btn = document.querySelector('button[onclick="exportToPDF()"]');
        const originalText = btn.innerText;
        btn.innerText = '⏳ Подготовка...';
        
        html2pdf().set(opt).from(element).save().then(() => {
            btn.innerText = originalText;
        }).catch(() => {
            btn.innerText = originalText;
            alert('Произошла ошибка при экспорте в PDF');
        });
    }

    // CSV Export Functionality
    function exportToCSV() {
        if (!dashboardData) {
            alert('Данные еще не загрузились');
            return;
        }

        // BOM для корректного отображения кириллицы в Excel + разделитель точка с запятой
        let csv = "\uFEFF";
        
        csv += "Глобальные показатели;Значение\n";
        csv += "Всего задач;" + dashboardData.total + "\n";
        csv += "Выполнено;" + dashboardData.completed + "\n";
        csv += "В процессе;" + dashboardData.in_progress + "\n";
        csv += "Просрочено;" + dashboardData.overdue + "\n";
        csv += "Ср. время выполнения (мин);" + dashboardData.avg_minutes + "\n\n";
        
        if (dashboardData.priorities) {
            csv += "Приоритеты;Количество\n";
            csv += "Высокий;" + dashboardData.priorities.high + "\n";
            csv += "Средний;" + dashboardData.priorities.medium + "\n";
            csv += "Низкий;" + dashboardData.priorities.low + "\n\n";
        }

        if (dashboardData.projects && dashboardData.projects.labels) {
            csv += "Проекты;Количество задач\n";
            dashboardData.projects.labels.forEach((label, i) => {
                let safeLabel = String(label).replace(/"/g, '""');
                csv += `"${safeLabel}";${dashboardData.projects.data[i]}\n`;
            });
            csv += "\n";
        }

        if (dashboardData.teams && dashboardData.teams.length > 0) {
            csv += "Сравнение команд;Выполнено;В процессе;Просрочено\n";
            dashboardData.teams.forEach(t => {
                let safeName = String(t.name).replace(/"/g, '""');
                csv += `"${safeName}";${t.completed};${t.in_progress};${t.overdue}\n`;
            });
        }

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "analytics_report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Загружаем метрики при загрузке страницы
    loadMetrics();
    loadChart();

    // Настраиваем периодическое обновление каждые 5 секунд
    setInterval(() => {
        loadMetrics();
        loadChart();
    }, 5000);
</script>

<!-- Фоновая музыка аналогично index.php (если требуется) -->
<audio id="bgMusic" src="musik.mp3" loop preload="auto"></audio>
<script>
    document.addEventListener('click', function initAudio() {
        const audio = document.getElementById('bgMusic');
        audio.play().catch(e => {});
        document.removeEventListener('click', initAudio);
    });
</script>
</body>
</html>
