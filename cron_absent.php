<?php
/**
 * Cron Job: ตัดขาดเรียนอัตโนมัติ (รันเวลา 08:30)
 * Command: php /path/to/cron_absent.php
 */
require_once __DIR__ . '/config/database.php';
$db = Database::connect();
$today = date('Y-m-d');

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

echo "[" . date('Y-m-d H:i:s') . "] Marked $affected students as absent.\n";
