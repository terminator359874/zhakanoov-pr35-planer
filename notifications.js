document.addEventListener('DOMContentLoaded', function() {
    let notifContainer = document.getElementById('notifContainer');
    if (!notifContainer) {
        notifContainer = document.createElement('div');
        notifContainer.id = 'notifContainer';
        notifContainer.className = 'toast-container position-fixed bottom-0 start-0 p-3'; 
        notifContainer.style.zIndex = '9999';
        document.body.appendChild(notifContainer);
    }

    function checkUpcomingTasks() {
        let d = new Date();
        let year = d.getFullYear();
        let month = String(d.getMonth() + 1).padStart(2, '0');
        let day = String(d.getDate()).padStart(2, '0');
        let hours = String(d.getHours()).padStart(2, '0');
        let mins = String(d.getMinutes()).padStart(2, '0');
        let secs = String(d.getSeconds()).padStart(2, '0');
        let localNow = `${year}-${month}-${day} ${hours}:${mins}:${secs}`;

        fetch('get_upcoming_tasks.php?now=' + encodeURIComponent(localNow))
            .then(r => r.json())
            .then(data => {
                if (data.success && data.tasks) {
                    let notifiedStr = localStorage.getItem('notified_tasks') || '[]';
                    let notified = [];
                    try { notified = JSON.parse(notifiedStr); } catch (e) {}
                    
                    let newNotified = [...notified];
                    let shouldSave = false;

                    data.tasks.forEach(task => {
                        if (!notified.includes(task.id)) {
                            showNotification(task);
                            newNotified.push(task.id);
                            shouldSave = true;
                        }
                    });

                    if (shouldSave) {
                        localStorage.setItem('notified_tasks', JSON.stringify(newNotified));
                    }
                }
            })
            .catch(e => console.error('Ошибка проверки уведомлений:', e));
    }

    function showNotification(task) {
        const toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-white border-0';
        toastEl.style.background = 'var(--accent, #2b6be6)';
        toastEl.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        let timeStr = task.deadline.substring(11, 16); 

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong style="display:block; margin-bottom:4px; font-size:14px;">⏳ Скоро срок!</strong>
                    Задача <b>"${task.title}"</b> истекает в <b>${timeStr}</b>
                    <div style="margin-top: 5px; font-size: 11px; opacity:0.9;">Проект: ${task.project_name || 'Без проекта'}</div>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.getElementById('notifContainer').appendChild(toastEl);
        
        if (typeof bootstrap !== 'undefined') {
            const toast = new bootstrap.Toast(toastEl, { autohide: false });
            toast.show();
        } else {
            // Фолбэк, если bootstrap.js почему-то не загружен
            toastEl.classList.add('show');
            setTimeout(() => toastEl.classList.remove('show'), 15000);
        }
    }

    checkUpcomingTasks();
    setInterval(checkUpcomingTasks, 60000);
});
