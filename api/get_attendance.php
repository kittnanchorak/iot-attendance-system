<?php
/**
 * API: ดึงข้อมูล Attendance วันนี้
 * GET ?search=&status=&room=&last_id=
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
session_write_close();

$db = Database::connect();
$today  = date('Y-m-d');
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$room   = $_GET['room'] ?? '';
$lastId = intval($_GET['last_id'] ?? 0);

// Ensure today's records exist for all active students (only if count is 0)
$checkStmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = ?");
$checkStmt->execute([$today]);
if ((int)$checkStmt->fetchColumn() === 0) {
    $db->exec(
        "INSERT IGNORE INTO attendance (student_id, attendance_date, status)
         SELECT id, '{$today}', 'pending' FROM students WHERE is_active = 1"
    );
}

// Build query
$where  = ['a.attendance_date = ?'];
$params = [$today];

if ($search) {
    $where[]  = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_code LIKE ? OR s.student_id_full LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($status && in_array($status, ['present', 'absent', 'pending'])) {
    $where[]  = 'a.status = ?';
    $params[] = $status;
}
if ($room) {
    $where[]  = 's.room = ?';
    $params[] = $room;
}

$sql = "SELECT a.id, a.status, a.check_in_time,
               s.id AS sid, s.student_id_full, s.student_code,
               s.title, s.first_name, s.last_name, s.room
        FROM attendance a
        JOIN students s ON s.id = a.student_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.room ASC, s.student_code ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data'    => $rows,
    'date'    => $today
]);
