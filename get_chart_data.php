<?php
// get_chart_data.php
error_reporting(0); // Не показываем тех. ошибки
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$period = isset($_GET['period']) && $_GET['period'] === 'month' ? 'month' : 'week';
$days = $period === 'month' ? 30 : 7;

$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}

$cacheFile = $cacheDir . "/chart_{$period}_{$user_id}.json";
$cacheTTL = 10; // 10 секунд на кеширование чтобы разгрузить БД

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTTL)) {
    echo file_get_contents($cacheFile);
    exit;
}

require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();

    $stmt = $db->prepare("
        SELECT DATE(pa.created_at) as event_date, COUNT(DISTINCT pa.task_id) as count
        FROM project_activity pa
        WHERE pa.project_id IN (
            SELECT p.id 
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE p.owner_id = :user_id OR pm.user_id = :user_id
        )
        AND pa.details LIKE '%на «Завершен%'
        AND pa.created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        GROUP BY DATE(pa.created_at)
    ");
    
    // Мы вынуждены привязывать параметр days вручную, так как PDO не всегда любит INTERVAL :days DAY как параметр
    // Для надежности пропишем прямо в запрос:
    $intervalSql = (int)$days;
    $stmt = $db->prepare("
        SELECT DATE(pa.created_at) as event_date, COUNT(DISTINCT pa.task_id) as tasks_count
        FROM project_activity pa
        WHERE pa.project_id IN (
            SELECT p.id 
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE p.owner_id = :user_id OR pm.user_id = :user_id
        )
        AND pa.details LIKE '%на «Завершен%'
        AND pa.created_at >= DATE_SUB(CURDATE(), INTERVAL {$intervalSql} DAY)
        GROUP BY DATE(pa.created_at)
    ");

    $stmt->execute(['user_id' => $user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countsByDate = [];
    foreach ($results as $row) {
        $countsByDate[$row['event_date']] = (int)$row['tasks_count'];
    }

    $labels = [];
    $data = [];
    $totalCompleted = 0;

    for ($i = $days - 1; $i >= 0; $i--) {
        $dateStr = date('Y-m-d', strtotime("-$i days"));
        // Для месяца показываем каждую дату, для недели покажем день недели + дату
        $format = $period === 'month' ? 'd M' : 'D, d.m'; // D - день недели
        
        $labels[] = date($format, strtotime("-$i days"));
        
        $val = isset($countsByDate[$dateStr]) ? $countsByDate[$dateStr] : 0;
        $data[] = $val;
        $totalCompleted += $val;
    }

    // Переведем дни недели на русский если надо (в данном упрощенном варианте оставим встроенный,
    // но лучше сделать замену)
    $rusDays = ['Mon'=>'Пн', 'Tue'=>'Вт', 'Wed'=>'Ср', 'Thu'=>'Чт', 'Fri'=>'Пт', 'Sat'=>'Сб', 'Sun'=>'Вс'];
    $rusMonths = ['Jan'=>'Янв', 'Feb'=>'Фев', 'Mar'=>'Мар', 'Apr'=>'Апр', 'May'=>'Май', 'Jun'=>'Июн', 'Jul'=>'Июл', 'Aug'=>'Авг', 'Sep'=>'Сен', 'Oct'=>'Окт', 'Nov'=>'Ноя', 'Dec'=>'Дек'];
    
    foreach ($labels as &$label) {
        $label = strtr($label, $rusDays);
        $label = strtr($label, $rusMonths);
    }

    $response = [
        'labels' => $labels,
        'data' => $data,
        'hasData' => $totalCompleted > 0
    ];

    $json = json_encode($response, JSON_UNESCAPED_UNICODE);
    @file_put_contents($cacheFile, $json);
    echo $json;

} catch (Exception $e) {
    // Не показываем ошибки
    echo json_encode([
        'labels' => [],
        'data' => [],
        'hasData' => false
    ]);
}
?>
