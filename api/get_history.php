<?php
/**
 * API: ดึงประวัติย้อนหลัง
 * GET ?date_from=&date_to=&room=&page=&per_page=
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}
session_write_close();

$db       = Database::connect();
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$room     = $_GET['room']      ?? '';
$page     = max(1, intval($_GET['page'] ?? 1));
$perPage  = 50;
$offset   = ($page - 1) * $perPage;

$where  = ['a.attendance_date BETWEEN ? AND ?'];
$params = [$dateFrom, $dateTo];

if ($room) {
    $where[]  = 's.room = ?';
    $params[] = $room;
}

$whereStr = implode(' AND ', $where);

// Count
$stmt = $db->prepare("SELECT COUNT(*) FROM attendance a JOIN students s ON s.id = a.student_id WHERE {$whereStr}");
$stmt->execute($params);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Data
$sql = "SELECT a.id, a.attendance_date, a.check_in_time, a.status,
               s.student_id_full, s.student_code, s.title, s.first_name, s.last_name, s.room
        FROM attendance a
        JOIN students s ON s.id = a.student_id
        WHERE {$whereStr}
        ORDER BY a.attendance_date DESC, s.room ASC, s.student_code ASC
        LIMIT {$perPage} OFFSET {$offset}";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Daily summary
$stmt = $db->prepare(
    "SELECT a.attendance_date,
            COUNT(*) AS total,
            SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present_count,
            SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) AS absent_count
     FROM attendance a JOIN students s ON s.id = a.student_id
     WHERE {$whereStr}
     GROUP BY a.attendance_date ORDER BY a.attendance_date DESC"
);
$stmt->execute($params);
$summary = $stmt->fetchAll();

echo json_encode([
    'success'     => true,
    'data'        => $rows,
    'summary'     => $summary,
    'pagination'  => [
        'page'        => $page,
        'per_page'    => $perPage,
        'total_rows'  => $totalRows,
        'total_pages' => $totalPages
    ]
]);
