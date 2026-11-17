<?php
/**
 * API: ดึงสถิติ Dashboard
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}
session_write_close();

$db    = Database::connect();
$today = date('Y-m-d');

// Ensure records exist only if not present yet today
$checkStmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = ?");
$checkStmt->execute([$today]);
if ((int)$checkStmt->fetchColumn() === 0) {
    $db->exec(
        "INSERT IGNORE INTO attendance (student_id, attendance_date, status)
         SELECT id, '{$today}', 'pending' FROM students WHERE is_active = 1"
    );
}

// Stats
$stmt = $db->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'absent'  THEN 1 ELSE 0 END) AS absent_count,
        SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) AS pending_count
     FROM attendance a
     JOIN students s ON s.id = a.student_id
     WHERE a.attendance_date = ? AND s.is_active = 1"
);
$stmt->execute([$today]);
$stats = $stmt->fetch();

$total   = (int)$stats['total'];
$present = (int)$stats['present_count'];
$absent  = (int)$stats['absent_count'];
$pending = (int)$stats['pending_count'];
$pct     = $total > 0 ? round(($present / $total) * 100, 1) : 0;

// Recent check-ins
$stmt = $db->prepare(
    "SELECT s.title, s.first_name, s.last_name, s.room, a.check_in_time
     FROM attendance a
     JOIN students s ON s.id = a.student_id
     WHERE a.attendance_date = ? AND a.status = 'present'
     ORDER BY a.check_in_time DESC
     LIMIT 10"
);
$stmt->execute([$today]);
$recent = $stmt->fetchAll();

// Weekly trend (last 7 days)
$stmt = $db->prepare(
    "SELECT a.attendance_date,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
            COUNT(*) AS total
     FROM attendance a
     JOIN students s ON s.id = a.student_id AND s.is_active = 1
     WHERE a.attendance_date >= DATE_SUB(?, INTERVAL 6 DAY)
     GROUP BY a.attendance_date
     ORDER BY a.attendance_date ASC"
);
$stmt->execute([$today]);
$weekly = $stmt->fetchAll();

// Room stats
$stmt = $db->prepare(
    "SELECT s.room,
            COUNT(*) AS total,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count
     FROM attendance a
     JOIN students s ON s.id = a.student_id
     WHERE a.attendance_date = ? AND s.is_active = 1
     GROUP BY s.room ORDER BY s.room"
);
$stmt->execute([$today]);
$rooms = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'stats'   => compact('total', 'present', 'absent', 'pending', 'pct'),
    'recent'  => $recent,
    'weekly'  => $weekly,
    'rooms'   => $rooms
]);
