<?php
/**
 * API: ตัดขาดเรียน (mark pending as absent)
 * GET ?secret=...
 */
header('Content-Type: application/json; charset=utf-8');

// For security, you might want to require a secret key if called via CRON
// or check if it's an admin if called via browser.
$secret = $_GET['secret'] ?? '';
$adminCall = false;

require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn() && isAdmin()) {
    $adminCall = true;
}

// ✅ อ่าน secret จาก Environment Variable
// ตั้งค่าใน AlwaysData: Web → Sites → Edit → Environment variables
// Key: CRON_SECRET  Value: [random string ของคุณ]
$cronSecret = getenv('CRON_SECRET') ?: 'CRON_SECRET_2024'; // fallback สำหรับ localhost

if ($secret !== $cronSecret && !$adminCall) {
     http_response_code(401);
     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
     exit;
}

$db = Database::connect();
$today = date('Y-m-d');
$time = date('H:i:s');
$absentTime = getSetting('absent_time', '08:30') . ':00'; // Default 08:30

// Only run if it's past absent time
if ($time >= $absentTime) {
    // 1. Ensure everyone has a record
    $db->exec(
        "INSERT IGNORE INTO attendance (student_id, attendance_date, status)
         SELECT id, '{$today}', 'pending' FROM students WHERE is_active = 1"
    );

    // 2. Update pending to absent
    $stmt = $db->prepare(
        "UPDATE attendance 
         SET status = 'absent' 
         WHERE attendance_date = ? AND status = 'pending'"
    );
    $stmt->execute([$today]);
    $affected = $stmt->rowCount();

    echo json_encode(['success' => true, 'marked_absent' => $affected, 'time' => $time]);
} else {
    echo json_encode(['success' => false, 'message' => 'Not yet time to mark absent', 'current_time' => $time, 'target_time' => $absentTime]);
}
