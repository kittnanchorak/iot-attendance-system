<?php
/**
 * Debug: ทดสอบ checkin โดยตรง (ไม่ต้องใช้ API Key)
 * เปิด: http://localhost/projectiot/api/debug_checkin.php
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$db = Database::connect();

// 1. เช็ค API Key ในฐานข้อมูล
$stmt = $db->query("SELECT * FROM api_keys WHERE is_active = 1");
$keys = $stmt->fetchAll();

// 2. ทดสอบค้นหานักเรียนรหัส 032893
$testCode = '032893';
$stmt2 = $db->prepare("SELECT * FROM students WHERE student_code = ? AND is_active = 1");
$stmt2->execute([$testCode]);
$student = $stmt2->fetch();

// 3. ทดสอบ checkin จริง
if ($student) {
    $today = date('Y-m-d');
    $now   = date('H:i:s');
    $stmt3 = $db->prepare(
        'INSERT INTO attendance (student_id, attendance_date, check_in_time, status)
         VALUES (?, ?, ?, "present")
         ON DUPLICATE KEY UPDATE check_in_time = VALUES(check_in_time), status = "present"'
    );
    $stmt3->execute([$student['id'], $today, $now]);
    $checkinResult = "สำเร็จ! บันทึกเวลา $now";
} else {
    $checkinResult = "ไม่พบนักเรียนรหัส $testCode";
}

echo json_encode([
    'api_keys_in_db'  => $keys,
    'student_found'   => $student ?: null,
    'checkin_result'  => $checkinResult,
    'auth_header'     => $_SERVER['HTTP_AUTHORIZATION'] ?? 'ไม่มี (Apache บล็อก!)',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
