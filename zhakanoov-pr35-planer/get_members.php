<?php
session_start();
require 'config/database.php';
require 'includes/permissions.php'; // Тот файл, что мы создали в предыдущем шаге

if (!isset($_SESSION['user_id']) || !isset($_GET['project_id'])) {
    echo "Доступ запрещен.";
    exit;
}

$db = (new Database())->getConnection();
$project_id = (int)$_GET['project_id'];
$current_user_id = $_SESSION['user_id'];

// Проверяем, есть ли вообще доступ к проекту
$my_role = getUserProjectRole($db, $project_id, $current_user_id);
if (!$my_role) {
    echo "<div class='alert alert-danger'>У вас нет доступа к этому проекту.</div>";
    exit;
}

// Получаем данные Владельца
$stmt = $db->prepare("
    SELECT u.id, u.email 
    FROM projects p 
    JOIN users u ON p.owner_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$project_id]);
$owner = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем данные Участников
$stmt = $db->prepare("
    SELECT u.id, u.email, pm.role 
    FROM project_members pm 
    JOIN users u ON pm.user_id = u.id 
    WHERE pm.project_id = ?
");
$stmt->execute([$project_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- ВЫВОД HTML ---

echo "<h6 class='border-bottom pb-2'>Владелец</h6>";
echo "<div class='d-flex justify-content-between align-items-center mb-3 p-2 border rounded bg-light'>";
echo "<span>" . htmlspecialchars($owner['email']) . "</span>";
echo "<span class='badge bg-danger'>Owner</span>";
echo "</div>";

echo "<h6 class='border-bottom pb-2 mt-4'>Приглашенные участники</h6>";

if (!$members) {
    echo "<p class='text-muted small'>В проекте пока нет приглашенных участников.</p>";
} else {
foreach ($members as $m) {
    echo "<div class='d-flex justify-content-between align-items-center mb-2 p-2 border rounded'>";
    echo "<span>" . htmlspecialchars($m['email']) . "</span>";

    echo "<div class='d-flex align-items-center'>"; // Контейнер для кнопок
    
    if ($my_role === 'owner') {
        // Выбор роли
        $selManager = ($m['role'] === 'manager') ? 'selected' : '';
        $selMember = ($m['role'] === 'member') ? 'selected' : '';
        
        echo "<select class='form-select form-select-sm w-auto me-2' onchange='changeUserRole({$project_id}, {$m['id']}, this.value)'>
                <option value='manager' {$selManager}>Менеджер</option>
                <option value='member' {$selMember}>Участник</option>
              </select>";
        
        // Кнопка удаления (крестик)
        echo "<button class='btn btn-sm btn-outline-danger' onclick='removeUserFromProject({$project_id}, {$m['id']}, \"".htmlspecialchars($m['email'])."\")'>
                &times;
              </button>";
    } else {
        $roleName = ($m['role'] === 'manager') ? 'Менеджер' : 'Участник';
        $badgeColor = ($m['role'] === 'manager') ? 'bg-primary' : 'bg-secondary';
        echo "<span class='badge {$badgeColor}'>{$roleName}</span>";
    }
    
    echo "</div>"; // Закрываем контейнер
    echo "</div>";
}
}
?>