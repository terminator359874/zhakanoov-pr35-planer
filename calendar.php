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
            z-index: 100;
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
            max-width: 1000px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            gap: 15px;
            min-height: 700px;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .calendar-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-head);
            min-width: 200px;
        }
        .header-controls {
            display: flex;
            gap: 10px;
        }

        /* Monthly View */
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
        .cal-day:hover { border-color: #b0b8c8; }
        .cal-day.empty { background: transparent; border-color: transparent; pointer-events: none; }
        .cal-day.today { border-color: var(--accent); background: rgba(43,107,230,.04); }
        .cal-day.has-tasks { cursor: pointer; }
        .cal-day.has-tasks:hover { background: var(--surface); box-shadow: 0 2px 6px rgba(0,0,0,.05); }
        .day-number { font-weight: 600; font-size: 13px; color: var(--text-head); align-self: flex-end; }
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
        .task-badge.none { background: transparent; color: var(--text-dim); }

        /* Weekly View */
        .weekly-grid {
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            flex: 1;
        }
        .week-header {
            display: grid;
            grid-template-columns: 50px repeat(7, 1fr);
            border-bottom: 1px solid var(--border);
            background: var(--surface2);
        }
        .time-col-header, .week-day-header {
            padding: 8px;
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-dim);
            border-right: 1px solid var(--border);
        }
        .week-day-header:last-child { border-right: none; }
        .week-day-header.today {
            color: var(--accent);
            background: rgba(43,107,230,.08);
        }
        .week-body {
            display: grid;
            grid-template-columns: 50px repeat(7, 1fr);
            height: 600px;
            overflow-y: auto;
            background: var(--surface);
        }
        .time-col {
            border-right: 1px solid var(--border);
            background: var(--surface2);
            position: relative;
        }
        .day-col {
            border-right: 1px solid var(--border);
            position: relative;
        }
        .day-col:last-child { border-right: none; }

        .time-slot {
            height: 60px;
            text-align: right;
            padding: 2px 4px 0 0;
            color: var(--text-dim);
            font-size: 10px;
            box-sizing: border-box;
        }
        .all-day-row-label {
            min-height: 40px;
            border-bottom: 2px solid var(--text-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: var(--text-dim);
            background: var(--surface2);
        }
        .all-day-slot {
            min-height: 40px;
            border-bottom: 2px solid var(--text-dim);
            background: rgba(43,107,230,.04);
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 2px;
            position: relative;
            z-index: 5;
        }
        .hours-slot {
            position: relative;
            height: 1440px; 
            background-size: 100% 60px;
            background-image: linear-gradient(to bottom, var(--border) 1px, transparent 1px);
        }
        .week-task {
            position: absolute;
            left: 2px;
            right: 2px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-left: 3px solid var(--accent);
            border-radius: 4px;
            padding: 4px;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: box-shadow 0.1s;
            z-index: 10;
        }
        .week-task.all-day {
            position: static;
            margin-bottom: 2px;
        }
        .week-task:hover { box-shadow: 0 3px 8px rgba(0,0,0,0.15); z-index: 20 !important; }
        .week-task-title {
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-head);
            line-height:1.2;
        }
        .week-task-status { font-size: 9px; color: var(--text-dim); line-height:1; margin-top:2px; }

        .month-task-item {
            font-size: 10px;
            font-weight: 500;
            background: var(--surface);
            border: 1px solid var(--border);
            border-left: 2px solid var(--accent);
            padding: 2px 4px;
            border-radius: 3px;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: grab;
            position: relative;
            z-index: 10;
        }
        .month-task-item:active { cursor: grabbing; }
        .month-task-item.dragging { opacity: 0.5; }
        .month-more-badge {
            font-size: 9px;
            color: var(--text-dim);
            text-align: center;
            margin-top: 2px;
            font-weight: 600;
        }
        .cal-day.drag-over {
            background: rgba(43,107,230,.15);
            border-style: dashed;
        }
        .day-col.drag-over {
            background: rgba(43,107,230,.08);
        }
        .week-task { cursor: grab !important; }
        .week-task:active { cursor: grabbing !important; }
        .week-task.dragging { opacity: 0.5; z-index: 100 !important; }

        /* Day View */
        .day-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            flex: 1;
        }
        .day-empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-dim);
            font-size: 14px;
            background: var(--surface2);
            border-radius: 8px;
            border: 1px dashed var(--border);
        }
        .day-task-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            border-left: 4px solid var(--accent);
            cursor: pointer;
            transition: box-shadow 0.15s, transform 0.15s;
        }
        .day-task-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: translateY(-1px);
        }
        .day-task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .day-task-time {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-head);
            background: var(--surface2);
            padding: 2px 8px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .day-task-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-head);
            margin-top: 4px;
        }
        .day-task-desc {
            font-size: 13px;
            color: var(--text-dim);
            line-height: 1.5;
            white-space: pre-wrap;
            margin-top: 4px;
        }
        .day-task-meta {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .day-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            border: 1px solid var(--border);
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
            <h2 id="viewTitle">...</h2>
            <div class="header-controls">
                <select id="viewSelect" class="topbar-btn" onchange="setViewMode(this.value)">
                    <option value="month">Месяц</option>
                    <option value="week">Неделя</option>
                    <option value="day">День</option>
                </select>
                <button id="btnToday" class="topbar-btn" onclick="navToday()">Сегодня</button>
                <button id="btnPrev" class="topbar-btn" onclick="navStep(-1)">&#8592; Назад</button>
                <button id="btnNext" class="topbar-btn" onclick="navStep(1)">Вперёд &#8594;</button>
            </div>
        </div>

        <!-- Month View Grid -->
        <div class="calendar-grid" id="monthGrid">
            <div class="weekday">Пн</div>
            <div class="weekday">Вт</div>
            <div class="weekday">Ср</div>
            <div class="weekday">Чт</div>
            <div class="weekday">Пт</div>
            <div class="weekday">Сб</div>
            <div class="weekday">Вс</div>
            <!-- Days injected via JS -->
        </div>

        <!-- Day View Grid -->
        <div class="day-grid" id="dayGrid" style="display: none;">
            <!-- Rendered via JS -->
        </div>

        <!-- Week View Grid -->
        <div class="weekly-grid" id="weekGrid" style="display: none;">
            <div class="week-header">
                <div class="time-col-header">Время</div>
                <div class="week-day-header" id="wh-0">Пн</div>
                <div class="week-day-header" id="wh-1">Вт</div>
                <div class="week-day-header" id="wh-2">Ср</div>
                <div class="week-day-header" id="wh-3">Чт</div>
                <div class="week-day-header" id="wh-4">Пт</div>
                <div class="week-day-header" id="wh-5">Сб</div>
                <div class="week-day-header" id="wh-6">Вс</div>
            </div>
            <div class="week-body" id="weekBody">
                <div class="time-col" id="timeCol">
                    <div class="all-day-row-label">Весь день</div>
                    <!-- 00:00 - 23:00 -->
                </div>
                <!-- 7 days -->
                <div class="day-col" id="wd-0"><div class="all-day-slot" id="wad-0"></div><div class="hours-slot" id="whs-0"></div></div>
                <div class="day-col" id="wd-1"><div class="all-day-slot" id="wad-1"></div><div class="hours-slot" id="whs-1"></div></div>
                <div class="day-col" id="wd-2"><div class="all-day-slot" id="wad-2"></div><div class="hours-slot" id="whs-2"></div></div>
                <div class="day-col" id="wd-3"><div class="all-day-slot" id="wad-3"></div><div class="hours-slot" id="whs-3"></div></div>
                <div class="day-col" id="wd-4"><div class="all-day-slot" id="wad-4"></div><div class="hours-slot" id="whs-4"></div></div>
                <div class="day-col" id="wd-5"><div class="all-day-slot" id="wad-5"></div><div class="hours-slot" id="whs-5"></div></div>
                <div class="day-col" id="wd-6"><div class="all-day-slot" id="wad-6"></div><div class="hours-slot" id="whs-6"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="tasksModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tasksModalTitle" style="font-size:14px;">Задачи</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tasksModalBody"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const monthsNames = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    const shortDays = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
    
    const currentDate = new Date();
    currentDate.setHours(0,0,0,0);
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth(); 
    
    // Limits
    const minAllowedDate = new Date(currentYear, currentMonth - 1, 1);
    const maxAllowedDate = new Date(currentYear, currentMonth + 4, 0); // End of month +3

    let viewMode = 'month';
    let viewYear = currentYear;
    let viewMonth = currentMonth;
    
    let viewWeekStart = getMonday(currentDate);
    let viewDayDate = new Date(currentDate);

    let tasksCache = []; // flat list of current frame's tasks

    let draggedTaskId = null;

    function handleDragStart(e) {
        draggedTaskId = e.target.dataset.taskId;
        // Need to wait until next tick so drag image is normal
        setTimeout(() => e.target.classList.add('dragging'), 0);
    }

    function handleDragEnd(e) {
        e.target.classList.remove('dragging');
        draggedTaskId = null;
    }

    function handleDragOver(e) {
        e.preventDefault(); 
        const dateStr = e.currentTarget.dataset.date;
        if (!dateStr) return;
        const hoverDate = new Date(dateStr);
        if (hoverDate < minAllowedDate || hoverDate > maxAllowedDate) {
            e.dataTransfer.dropEffect = 'none';
            return;
        }
        e.dataTransfer.dropEffect = 'move';
        e.currentTarget.classList.add('drag-over');
    }

    function handleDragLeave(e) {
        e.currentTarget.classList.remove('drag-over');
    }

    async function handleDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('drag-over');
        const taskId = draggedTaskId;
        const newDate = e.currentTarget.dataset.date;
        
        if (!taskId || !newDate) return;

        const hoverDate = new Date(newDate);
        if (hoverDate < minAllowedDate || hoverDate > maxAllowedDate) {
            alert('Выход за допустимый диапазон дат');
            return;
        }

        try {
            const res = await fetch('update_task_date.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: taskId, new_date: newDate })
            });
            const data = await res.json();
            if (data.success) {
                loadData(); // Re-render silently
            } else {
                alert('Ошибка: ' + data.error);
            }
        } catch (err) {
            alert('Ошибка сети при обновлении задачи');
        }
    }

    function getMonday(d) {
        d = new Date(d);
        var day = d.getDay(), diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.getFullYear(), d.getMonth(), diff);
    }
    
    function formatDateYMD(d) {
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }

    // Init time column in weekly view
    const timeCol = document.getElementById('timeCol');
    for (let i = 0; i <= 23; i++) {
        let slot = document.createElement('div');
        slot.className = 'time-slot';
        slot.textContent = String(i).padStart(2, '0') + ':00';
        timeCol.appendChild(slot);
    }

    function setViewMode(mode) {
        viewMode = mode;
        document.getElementById('monthGrid').style.display = (mode === 'month') ? 'grid' : 'none';
        document.getElementById('weekGrid').style.display = (mode === 'week') ? 'flex' : 'none';
        document.getElementById('dayGrid').style.display = (mode === 'day') ? 'flex' : 'none';
        loadData();
    }

    function navToday() {
        viewYear = currentYear;
        viewMonth = currentMonth;
        viewWeekStart = getMonday(currentDate);
        viewDayDate = new Date(currentDate);
        loadData();
    }

    function navStep(delta) {
        if (viewMode === 'month') {
            viewMonth += delta;
            if (viewMonth > 11) { viewMonth = 0; viewYear++; }
            else if (viewMonth < 0) { viewMonth = 11; viewYear--; }
        } else if (viewMode === 'week') {
            viewWeekStart.setDate(viewWeekStart.getDate() + (delta * 7));
        } else {
            viewDayDate.setDate(viewDayDate.getDate() + delta);
        }
        loadData();
    }

    function updateNavButtons() {
        if (viewMode === 'month') {
            const currentViewDate = new Date(viewYear, viewMonth, 1);
            document.getElementById('btnPrev').disabled = currentViewDate <= minAllowedDate;
            document.getElementById('btnNext').disabled = currentViewDate >= maxAllowedDate;
            document.getElementById('viewTitle').textContent = `${monthsNames[viewMonth]} ${viewYear}`;
        } else if (viewMode === 'week') {
            // roughly check week bounds
            let viewWeekEnd = new Date(viewWeekStart);
            viewWeekEnd.setDate(viewWeekEnd.getDate() + 6);
            
            document.getElementById('btnPrev').disabled = viewWeekEnd < minAllowedDate;
            document.getElementById('btnNext').disabled = viewWeekStart > maxAllowedDate;

            let titleStr = `${viewWeekStart.getDate()} ${monthsNames[viewWeekStart.getMonth()].substring(0,3)} — ${viewWeekEnd.getDate()} ${monthsNames[viewWeekEnd.getMonth()].substring(0,3)} ${viewWeekEnd.getFullYear()}`;
            document.getElementById('viewTitle').textContent = titleStr;
        } else if (viewMode === 'day') {
            document.getElementById('btnPrev').disabled = viewDayDate <= minAllowedDate;
            document.getElementById('btnNext').disabled = viewDayDate >= maxAllowedDate;
            let dayName = shortDays[viewDayDate.getDay()];
            document.getElementById('viewTitle').textContent = `${viewDayDate.getDate()} ${monthsNames[viewDayDate.getMonth()]} ${viewDayDate.getFullYear()}, ${dayName}`;
        }
    }

    async function loadData() {
        updateNavButtons();
        
        let startDateStr = '';
        let endDateStr = '';

        if (viewMode === 'month') {
            let start = new Date(viewYear, viewMonth, 1);
            let end = new Date(viewYear, viewMonth + 1, 0);
            startDateStr = formatDateYMD(start);
            endDateStr = formatDateYMD(end);
        } else if (viewMode === 'week') {
            let end = new Date(viewWeekStart);
            end.setDate(end.getDate() + 6);
            startDateStr = formatDateYMD(viewWeekStart);
            endDateStr = formatDateYMD(end);
        } else {
            startDateStr = formatDateYMD(viewDayDate);
            endDateStr = formatDateYMD(viewDayDate);
        }

        try {
            const res = await fetch(`get_tasks_by_range.php?start_date=${startDateStr}&end_date=${endDateStr}`);
            const data = await res.json();
            if (data.success) {
                let flatTasks = [];
                for (const date in data.tasksByDay) {
                    flatTasks.push(...data.tasksByDay[date]);
                }
                tasksCache = flatTasks;
                
                if (viewMode === 'month') renderMonth(data.tasksByDay);
                else if (viewMode === 'week') renderWeek(flatTasks);
                else renderDay(flatTasks);
            }
        } catch (e) {
            console.error('Ошибка загрузки задач', e);
        }
    }

    function renderMonth(tasksByDay) {
        const grid = document.getElementById('monthGrid');
        grid.querySelectorAll('.cal-day').forEach(d => d.remove());

        const firstDayOfMonth = new Date(viewYear, viewMonth, 1);
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
        
        let firstDayPos = firstDayOfMonth.getDay() - 1;
        if (firstDayPos === -1) firstDayPos = 6; 

        for (let i = 0; i < firstDayPos; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'cal-day empty';
            grid.appendChild(emptyCell);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const cell = document.createElement('div');
            cell.className = 'cal-day';
            
            const dateStr = formatDateYMD(new Date(viewYear, viewMonth, day));
            cell.dataset.date = dateStr;
            cell.addEventListener('dragover', handleDragOver);
            cell.addEventListener('dragleave', handleDragLeave);
            cell.addEventListener('drop', handleDrop);

            if (viewYear === currentYear && viewMonth === currentMonth && day === currentDate.getDate()) {
                cell.classList.add('today');
            }

            const tasksForDay = tasksByDay[dateStr] || [];
            const taskCount = tasksForDay.length;

            if (taskCount > 0) {
                cell.classList.add('has-tasks');
                cell.onclick = () => openModal(tasksForDay, `${day} ${monthsNames[viewMonth]}`);
            }

            const numDiv = document.createElement('div');
            numDiv.className = 'day-number';
            numDiv.textContent = day;
            cell.appendChild(numDiv);

            let maxTasks = 3;
            for (let k = 0; k < Math.min(taskCount, maxTasks); k++) {
                let t = tasksForDay[k];
                let tb = document.createElement('div');
                tb.className = 'month-task-item';
                tb.textContent = t.title;
                tb.draggable = true;
                tb.dataset.taskId = t.id;
                
                // Colorize based on priority like we do in week view
                const prioMap = {'low':'var(--green)','medium':'var(--yellow)','high':'var(--red)'};
                tb.style.borderLeftColor = prioMap[t.priority] || 'var(--accent)';

                tb.addEventListener('dragstart', handleDragStart);
                tb.addEventListener('dragend', handleDragEnd);
                tb.onclick = (e) => { e.stopPropagation(); window.location.href = 'view_task.php?id=' + t.id; };
                cell.appendChild(tb);
            }
            if (taskCount > maxTasks) {
                let more = document.createElement('div');
                more.className = 'month-more-badge';
                more.textContent = `ещё ${taskCount - maxTasks}`;
                cell.appendChild(more);
            }

            grid.appendChild(cell);
        }
    }

    function renderWeek(allTasks) {
        // clear old 
        for (let i = 0; i < 7; i++) {
            document.getElementById(`wad-${i}`).innerHTML = '';
            document.getElementById(`whs-${i}`).innerHTML = '';
        }

        // populate dates in headers
        for (let i = 0; i < 7; i++) {
            let targetD = new Date(viewWeekStart);
            targetD.setDate(targetD.getDate() + i);
            let dateStr = formatDateYMD(targetD);
            
            let headEl = document.getElementById(`wh-${i}`);
            let dayName = shortDays[targetD.getDay()];
            headEl.textContent = `${targetD.getDate()} ${dayName}`;
            
            if (targetD.getTime() === currentDate.getTime()) headEl.classList.add('today');
            else headEl.classList.remove('today');

            let colEl = document.getElementById(`wd-${i}`);
            colEl.dataset.date = dateStr;
            colEl.ondragover = handleDragOver;
            colEl.ondragleave = handleDragLeave;
            colEl.ondrop = handleDrop;
        }

        const statusMap = {'new':'Новая','working':'В работе','progress':'В процессе','done':'Завершена'};
        const prioMap = {'low':'var(--green)','medium':'var(--yellow)','high':'var(--red)'};

        allTasks.forEach(t => {
            let tDate = new Date(t.deadline.replace(' ', 'T'));
            let timeStr = t.deadline.substring(11, 16);
            let isAllDay = (timeStr === '00:00' || timeStr === '');
            
            tDate.setHours(0,0,0,0);
            let diffDays = Math.round((tDate - viewWeekStart) / (1000 * 60 * 60 * 24));
            if (diffDays >= 0 && diffDays < 7) {
                
                let taskDiv = document.createElement('div');
                taskDiv.className = isAllDay ? 'week-task all-day' : 'week-task';
                
                let titleDiv = document.createElement('div');
                titleDiv.className = 'week-task-title';
                titleDiv.textContent = t.title;

                let statDiv = document.createElement('div');
                statDiv.className = 'week-task-status';
                statDiv.textContent = (isAllDay ? '' : timeStr + ' ') + (statusMap[t.status]||t.status);

                taskDiv.appendChild(titleDiv);
                taskDiv.appendChild(statDiv);
                
                taskDiv.style.borderLeftColor = prioMap[t.priority] || 'var(--accent)';
                taskDiv.onclick = () => openModal([t], `деталях`);

                taskDiv.draggable = true;
                taskDiv.dataset.taskId = t.id;
                taskDiv.addEventListener('dragstart', handleDragStart);
                taskDiv.addEventListener('dragend', handleDragEnd);

                if (isAllDay) {
                    document.getElementById(`wad-${diffDays}`).appendChild(taskDiv);
                } else {
                    let originDate = new Date(t.deadline.replace(' ', 'T'));
                    let h = originDate.getHours();
                    let m = originDate.getMinutes();
                    let topPx = (h * 60) + m;
                    
                    taskDiv.style.top = topPx + 'px';
                    taskDiv.style.height = '40px'; 
                    
                    document.getElementById(`whs-${diffDays}`).appendChild(taskDiv);
                }
            }
        });

        // Simple cascading for overlapping hours tasks
        for (let i = 0; i < 7; i++) {
            let container = document.getElementById(`whs-${i}`);
            let tasks = Array.from(container.children);
            tasks.sort((a,b) => parseInt(a.style.top) - parseInt(b.style.top));
            
            for (let j = 0; j < tasks.length; j++) {
                let overlapCount = 0;
                for (let k = 0; k < j; k++) {
                    let aTop = parseInt(tasks[k].style.top);
                    let bTop = parseInt(tasks[j].style.top);
                    if (bTop < aTop + 40) overlapCount++; // height is 40
                }
                if (overlapCount > 0) {
                    // indent
                    let maxIndex = Math.min(overlapCount, 3);
                    tasks[j].style.left = (2 + maxIndex * 10) + 'px';
                }
            }
        }
    }

    function renderDay(dayTasks) {
        const grid = document.getElementById('dayGrid');
        grid.innerHTML = '';
        
        if (!dayTasks || dayTasks.length === 0) {
            grid.innerHTML = '<div class="day-empty-state">Нет задач на выбранный день</div>';
            return;
        }

        // Sort by time
        dayTasks.sort((a,b) => {
            let tA = a.deadline.substring(11, 16);
            let tB = b.deadline.substring(11, 16);
            return tA.localeCompare(tB);
        });

        const statusMap = {'new':'Новая','working':'В работе','progress':'В процессе','done':'Завершена'};
        const prioMap = {'low': {label:'Низкий', color:'var(--green)'},'medium': {label:'Средний', color:'var(--yellow)'},'high': {label:'Высокий', color:'var(--red)'}};

        dayTasks.forEach(t => {
            let timeStr = t.deadline ? t.deadline.substring(11, 16) : '';
            if (timeStr === '00:00' || timeStr === '') timeStr = 'Весь день';
            
            const card = document.createElement('div');
            card.className = 'day-task-card';
            
            let prio = prioMap[t.priority] || { label: t.priority, color: 'var(--accent)' };
            card.style.borderLeftColor = prio.color;

            let header = document.createElement('div');
            header.className = 'day-task-header';
            
            let timeBadge = document.createElement('div');
            timeBadge.className = 'day-task-time';
            timeBadge.innerHTML = `🕒 ${timeStr}`;
            
            let projBadge = document.createElement('div');
            projBadge.className = 'day-badge badge-project';
            projBadge.textContent = t.project_name || 'Без проекта';

            header.appendChild(timeBadge);
            header.appendChild(projBadge);
            card.appendChild(header);

            let title = document.createElement('div');
            title.className = 'day-task-title';
            title.textContent = t.title;
            card.appendChild(title);

            if (t.description && t.description.trim() !== '') {
                let desc = document.createElement('div');
                desc.className = 'day-task-desc';
                desc.textContent = t.description;
                card.appendChild(desc);
            }

            let meta = document.createElement('div');
            meta.className = 'day-task-meta';

            let pBadge = document.createElement('div');
            pBadge.className = 'day-badge';
            pBadge.style.color = prio.color;
            pBadge.style.borderColor = prio.color;
            pBadge.style.backgroundColor = 'transparent';
            pBadge.textContent = 'Приоритет: ' + prio.label;

            let sBadge = document.createElement('div');
            sBadge.className = 'day-badge';
            sBadge.style.color = 'var(--text-dim)';
            sBadge.textContent = 'Статус: ' + (statusMap[t.status] || t.status);

            meta.appendChild(pBadge);
            meta.appendChild(sBadge);
            card.appendChild(meta);
            
            // Navigate to task on click
            card.onclick = () => { window.location.href = 'view_task.php?id=' + t.id; };
            
            grid.appendChild(card);
        });
    }

    function openModal(tasks, scopeName) {
        document.getElementById('tasksModalTitle').textContent = `Задачи (${scopeName})`;
        const body = document.getElementById('tasksModalBody');
        body.innerHTML = '';
        
        const priorityMap = {'low': {label:'Низкий', color:'var(--green)'},'medium': {label:'Средний', color:'var(--yellow)'},'high': {label:'Высокий', color:'var(--red)'}};
        const statusMap = {'new':'Новая','working':'В работе','progress':'В процессе','done':'Завершена'};

        const list = document.createElement('div');
        list.style.display = 'flex';
        list.style.flexDirection = 'column';
        list.style.gap = '8px';
        
        tasks.forEach(t => {
            const item = document.createElement('div');
            item.style.border = '1px solid var(--border)';
            item.style.padding = '10px 14px';
            item.style.borderRadius = '6px';
            item.style.display = 'flex';
            item.style.flexDirection = 'column';
            item.style.gap = '6px';

            let headerContainer = document.createElement('div');
            headerContainer.style.display = 'flex';
            headerContainer.style.justifyContent = 'space-between';
            headerContainer.style.alignItems = 'center';

            let p = document.createElement('div');
            p.style.fontSize = '10px';
            p.style.fontFamily = 'monospace';
            p.style.color = 'var(--text-dim)';
            p.textContent = t.project_name || 'Без проекта';

            let timeStr = t.deadline ? t.deadline.substring(11, 16) : '';
            let timeBadge = document.createElement('div');
            timeBadge.style.fontSize = '10px';
            timeBadge.style.fontWeight = '600';
            timeBadge.style.color = 'var(--text-head)';
            timeBadge.style.background = 'var(--surface2)';
            timeBadge.style.padding = '2px 6px';
            timeBadge.style.borderRadius = '4px';
            timeBadge.innerHTML = '🕒 ' + (timeStr !== '00:00' && timeStr !== '' ? timeStr : 'Весь день');

            headerContainer.appendChild(p);
            headerContainer.appendChild(timeBadge);

            let title = document.createElement('div');
            title.style.fontWeight = '600';
            title.style.fontSize = '14px';
            title.style.color = 'var(--text-head)';
            title.textContent = t.title;

            let metaContainer = document.createElement('div');
            metaContainer.style.display = 'flex';
            metaContainer.style.gap = '8px';
            metaContainer.style.alignItems = 'center';

            let prio = priorityMap[t.priority] || { label: t.priority, color: 'var(--text-dim)' };
            let prioBadge = document.createElement('div');
            prioBadge.style.fontSize = '10px';
            prioBadge.style.color = prio.color;
            prioBadge.style.border = `1px solid ${prio.color}`;
            prioBadge.style.padding = '1px 5px';
            prioBadge.style.borderRadius = '3px';
            prioBadge.textContent = 'Приоритет: ' + prio.label;

            let statBadge = document.createElement('div');
            statBadge.style.fontSize = '10px';
            statBadge.style.color = 'var(--text-dim)';
            statBadge.style.border = '1px solid var(--border)';
            statBadge.style.padding = '1px 5px';
            statBadge.style.borderRadius = '3px';
            statBadge.textContent = 'Статус: ' + (statusMap[t.status] || t.status);

            metaContainer.appendChild(prioBadge);
            metaContainer.appendChild(statBadge);

            let link = document.createElement('a');
            link.href = 'view_task.php?id=' + t.id;
            link.style.fontSize = '12px';
            link.style.color = 'var(--accent)';
            link.style.textDecoration = 'none';
            link.style.fontWeight = '500';
            link.style.marginTop = '4px';
            link.textContent = 'Открыть задачу →';

            item.appendChild(headerContainer);
            item.appendChild(title);
            item.appendChild(metaContainer);
            item.appendChild(link);
            list.appendChild(item);
        });
        body.appendChild(list);
        
        new bootstrap.Modal(document.getElementById('tasksModal')).show();
    }

    function getPlural(n, one, two, five) {
        n %= 100; if (n>=5&&n<=20) return five; n %= 10;
        if (n===1) return one; if (n>=2&&n<=4) return two; return five;
    }

    document.addEventListener('DOMContentLoaded', loadData);
</script>
</body>
</html>
