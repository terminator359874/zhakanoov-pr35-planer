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
    <div class="dashboard-container">
        
        <div class="dashboard-header">
            <div>
                <h1 class="dash-title">Аналитика задач</h1>
                <div class="dash-subtitle">Данные за последние 12 месяцев. Показатели по всем доступным проектам.</div>
            </div>
            <div class="refresh-status" id="refreshStatus">
                <span class="status-dot"></span> <span id="refreshText">Обновлено только что</span>
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
        </div>

    </div>
</div>

<script>
    async function loadMetrics() {
        const dot = document.querySelector('.status-dot');
        const text = document.getElementById('refreshText');
        
        try {
            dot.style.background = 'var(--yellow)';
            text.innerText = 'Обновление...';

            const response = await fetch('get_dashboard_metrics.php');
            if (response.ok) {
                const data = await response.json();
                
                // Удаляем скелетоны и ставим значения
                document.getElementById('val-total').className = '';
                document.getElementById('val-total').innerText = data.total;
                
                document.getElementById('val-completed').className = '';
                document.getElementById('val-completed').innerText = data.completed;
                
                document.getElementById('val-progress').className = '';
                document.getElementById('val-progress').innerText = data.in_progress;
                
                document.getElementById('val-overdue').className = '';
                document.getElementById('val-overdue').innerText = data.overdue;

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

    // Загружаем метрики при загрузке страницы
    loadMetrics();

    // Настраиваем периодическое обновление каждые 5 секунд
    setInterval(loadMetrics, 5000);
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
