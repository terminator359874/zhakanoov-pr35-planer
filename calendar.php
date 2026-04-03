<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Task Planner Pro - Календарь</title>
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
            font-size: 13px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

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
        .topbar-btn:hover:not(:disabled) { background: var(--surface2); border-color: #b0b8c8; color: var(--text-head); }
        .topbar-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .topbar-btn.primary { border-color: var(--accent); color: var(--accent); }
        .topbar-btn.primary:hover:not(:disabled) { background: rgba(43,107,230,.08); }
        .topbar-btn.danger { border-color: #f5c6c6; color: var(--red); }
        .topbar-spacer { flex: 1; }

        .main-area {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .calendar-wrapper {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .calendar-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-head);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .weekday {
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-dim);
            text-align: center;
            padding-bottom: 8px;
        }

        .cal-day {
            min-height: 80px;
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 6px;
            display: flex;
            flex-direction: column;
            background: var(--surface2);
            transition: border-color .15s, background .15s;
        }
        .cal-day:hover {
            border-color: #b0b8c8;
        }
        .cal-day.empty {
            background: transparent;
            border-color: transparent;
            pointer-events: none;
        }
        .cal-day.today {
            border-color: var(--accent);
            background: rgba(43,107,230,.04);
        }
        .cal-day.has-tasks {
            cursor: pointer;
        }
        .cal-day.has-tasks:hover {
            background: var(--surface);
            box-shadow: 0 2px 6px rgba(0,0,0,.05);
        }

        .day-number {
            font-weight: 600;
            font-size: 13px;
            color: var(--text-head);
            align-self: flex-end;
        }

        .task-badge {
            margin-top: auto;
            background: var(--accent);
            color: white;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            align-self: flex-start;
        }
        .task-badge.none {
            background: transparent;
            color: var(--text-dim);
        }

    </style>
</head>
<body>

<div class="topbar">
    <span class="topbar-brand">⬡ Task Planner</span>
    <div class="topbar-sep"></div>
    <a href="index.php" class="topbar-btn">← К доскам проектов</a>
</div>

<div class="main-area">
    <div class="calendar-wrapper">
        <div class="calendar-header">
            <h2 id="monthTitle">...</h2>
            <div>
                <button id="btnPrev" class="topbar-btn" onclick="changeMonth(-1)">&#8592; Назад</button>
                <button id="btnNext" class="topbar-btn" onclick="changeMonth(1)">Вперёд &#8594;</button>
            </div>
        </div>
        <div class="calendar-grid" id="calendarGrid">
            <!-- Days of week -->
            <div class="weekday">Пн</div>
            <div class="weekday">Вт</div>
            <div class="weekday">Ср</div>
            <div class="weekday">Чт</div>
            <div class="weekday">Пт</div>
            <div class="weekday">Сб</div>
            <div class="weekday">Вс</div>
            
            <!-- JavaScript will inject days here -->
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="tasksModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tasksModalTitle" style="font-size:14px;">Задачи на</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tasksModalBody">
                <!-- Tasks will be listed here -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const monthsNames = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth(); 

    let viewYear = currentYear;
    let viewMonth = currentMonth;

    let monthlyTasksCache = {};

    function updateNavButtons() {
        const minAllowedDate = new Date(currentYear, currentMonth - 1, 1);
        const maxAllowedDate = new Date(currentYear, currentMonth + 3, 1);
        
        const currentViewDate = new Date(viewYear, viewMonth, 1);
        
        document.getElementById('btnPrev').disabled = currentViewDate <= minAllowedDate;
        document.getElementById('btnNext').disabled = currentViewDate >= maxAllowedDate;
    }

    async function loadCalendar() {
        updateNavButtons();
        document.getElementById('monthTitle').textContent = `${monthsNames[viewMonth]} ${viewYear}`;
        
        const grid = document.getElementById('calendarGrid');
        
        // Remove old days
        const days = grid.querySelectorAll('.cal-day');
        days.forEach(d => d.remove());

        // We fetch data
        let tasksData = {};
        try {
            const res = await fetch(`get_calendar_tasks.php?year=${viewYear}&month=${viewMonth + 1}`);
            const data = await res.json();
            if (data.success) {
                tasksData = data.tasksByDay;
                monthlyTasksCache = tasksData; // Save for modal
            }
        } catch (e) {
            console.error('Ошибка загрузки задач');
        }

        // Calculate calendar layout
        const firstDayOfMonth = new Date(viewYear, viewMonth, 1);
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
        
        // getDay() is 0 (Sun) to 6 (Sat). We want Monday=0
        let firstDayPos = firstDayOfMonth.getDay() - 1;
        if (firstDayPos === -1) firstDayPos = 6; // Sunday

        // Fill empty boxes before first day
        for (let i = 0; i < firstDayPos; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'cal-day empty';
            grid.appendChild(emptyCell);
        }

        // Create actual days
        for (let day = 1; day <= daysInMonth; day++) {
            const cell = document.createElement('div');
            cell.className = 'cal-day';
            
            // Check if today
            if (viewYear === currentYear && viewMonth === currentMonth && day === currentDate.getDate()) {
                cell.classList.add('today');
            }

            const yyyy = viewYear;
            const mm = String(viewMonth + 1).padStart(2, '0');
            const dd = String(day).padStart(2, '0');
            const dateStr = `${yyyy}-${mm}-${dd}`;

            const tasksForDay = tasksData[dateStr] || [];
            const taskCount = tasksForDay.length;

            if (taskCount > 0) {
                cell.classList.add('has-tasks');
                cell.onclick = () => openModal(dateStr, day, monthsNames[viewMonth]);
            }

            const numDiv = document.createElement('div');
            numDiv.className = 'day-number';
            numDiv.textContent = day;
            cell.appendChild(numDiv);

            const badgeDiv = document.createElement('div');
            if (taskCount > 0) {
                badgeDiv.className = 'task-badge';
                badgeDiv.textContent = `${taskCount} ${getPlural(taskCount, 'задача', 'задачи', 'задач')}`;
            } else {
                badgeDiv.className = 'task-badge none';
                badgeDiv.textContent = '0 задач';
            }
            cell.appendChild(badgeDiv);

            grid.appendChild(cell);
        }
    }

    function changeMonth(delta) {
        viewMonth += delta;
        if (viewMonth > 11) {
            viewMonth = 0;
            viewYear++;
        } else if (viewMonth < 0) {
            viewMonth = 11;
            viewYear--;
        }
        loadCalendar();
    }

    function openModal(dateStr, day, monthName) {
        const tasks = monthlyTasksCache[dateStr] || [];
        document.getElementById('tasksModalTitle').textContent = `Задачи на ${day} ${monthName}`;
        
        const body = document.getElementById('tasksModalBody');
        body.innerHTML = '';
        
        if (tasks.length === 0) {
            body.innerHTML = '<div style="color:var(--text-dim);text-align:center;">Нет задач</div>';
        } else {
            const list = document.createElement('div');
            list.style.display = 'flex';
            list.style.flexDirection = 'column';
            list.style.gap = '8px';
            
            tasks.forEach(t => {
                const item = document.createElement('div');
                item.style.border = '1px solid var(--border)';
                item.style.padding = '8px 12px';
                item.style.borderRadius = '4px';
                
                let p = document.createElement('div');
                p.style.fontSize = '10px';
                p.style.fontFamily = 'monospace';
                p.style.color = 'var(--text-dim)';
                p.textContent = t.project_name || 'Без проекта';

                let title = document.createElement('div');
                title.style.fontWeight = '600';
                title.style.fontSize = '13px';
                title.textContent = t.title;
                
                let link = document.createElement('a');
                link.href = 'view_task.php?id=' + t.id;
                link.style.fontSize = '12px';
                link.style.color = 'var(--accent)';
                link.textContent = 'Открыть задачу';

                item.appendChild(p);
                item.appendChild(title);
                item.appendChild(link);
                
                list.appendChild(item);
            });
            body.appendChild(list);
        }
        
        const m = new bootstrap.Modal(document.getElementById('tasksModal'));
        m.show();
    }

    // Helper for russian plurals
    function getPlural(number, one, two, five) {
        let n = Math.abs(number);
        n %= 100;
        if (n >= 5 && n <= 20) {
            return five;
        }
        n %= 10;
        if (n === 1) {
            return one;
        }
        if (n >= 2 && n <= 4) {
            return two;
        }
        return five;
    }

    // Init
    document.addEventListener('DOMContentLoaded', loadCalendar);
</script>
</body>
</html>
