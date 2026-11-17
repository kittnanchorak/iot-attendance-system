<?php
/**
 * API: เสิร์ฟไฟล์เอกสารแนบการลาอย่างปลอดภัย (Secure Attachment Proxy)
 * เฉพาะนักเรียนเจ้าของคำร้อง หรือครู/แอดมินเท่านั้นที่มีสิทธิ์ดาวน์โหลด/เปิดดู
 */
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die('Unauthorized: กรุณาเข้าสู่ระบบก่อนเข้าถึงไฟล์');
}

$attachmentId = intval($_GET['id'] ?? 0);
if ($attachmentId <= 0) {
    http_response_code(400);
    die('Bad Request: ไม่พบรหัสไฟล์ที่ระบุ');
}

$db = Database::connect();
$stmt = $db->prepare('
    SELECT a.*, r.student_id 
    FROM leave_attachments a
    JOIN leave_requests r ON a.leave_request_id = r.id
    WHERE a.id = ?
    LIMIT 1
');
$stmt->execute([$attachmentId]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    die('Not Found: ไม่พบไฟล์เอกสารในระบบ');
}

// ตรวจสอบสิทธิ์ (Authorization Check)
if (isStudent()) {
    $currentStudentId = (int)($_SESSION['student_id'] ?? 0);
    if ((int)$file['student_id'] !== $currentStudentId) {
        http_response_code(403);
        die('Forbidden: คุณไม่มีสิทธิ์เข้าถึงเอกสารนี้');
    }
} elseif (!isTeacher() && !isAdmin()) {
    http_response_code(403);
    die('Forbidden: ไม่มีสิทธิ์เข้าถึง');
}

$filePath = __DIR__ . '/../uploads/leave_documents/' . $file['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File Missing: ไฟล์ต้นฉบับสูญหายหรือไม่พบบนดิสก์');
}

// กำหนด MIME type
$ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png'
];
$contentType = $mimeMap[$ext] ?? 'application/octet-stream';

// Header ส่งไฟล์ให้ Browser
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($filePath));
$disposition = (isset($_GET['download']) && $_GET['download'] === '1') ? 'attachment' : 'inline';
header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($file['file_name']) . '"');
header('Cache-Control: private, max-age=3600, must-revalidate');

readfile($filePath);
exit;
