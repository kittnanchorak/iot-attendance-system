<?php
/**
 * Cron Job: รีเซ็ตข้อมูลรายวัน (รันเวลา 16:00)
 * สร้าง record ของวันพรุ่งนี้เตรียมไว้
 */
require_once __DIR__ . '/config/database.php';
$db = Database::connect();
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// สร้าง record สำหรับวันพรุ่งนี้ให้เป็น pending ล่วงหน้า
$stmt = $db->prepare(
    "INSERT IGNORE INTO attendance (student_id, attendance_date, status)
     SELECT id, ?, 'pending' FROM students WHERE is_active = 1"
);
$stmt->execute([$tomorrow]);
$affected = $stmt->rowCount();

echo "[" . date('Y-m-d H:i:s') . "] Prepared $affected records for tomorrow ($tomorrow).\n";
