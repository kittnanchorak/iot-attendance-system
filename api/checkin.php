<?php
/**
 * API: Check-in จากเครื่อง IoT
 * Method: POST
 * Header: Authorization: Bearer <API_KEY>
 * Body: { "student_code": "032789" }
 */

header('Content-Type: application/json; charset=utf-8');

// ✅ จำกัด CORS เฉพาะ domain ของระบบ
$allowedOrigin = getenv('APP_ORIGIN') ?: 'https://linguini-subscript-unsubtle.ngrok-free.dev';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (empty($origin) || $origin === $allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . ($origin ?: $allowedOrigin));
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';

// --- Validate method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// --- Parse input (รองรับทั้ง JSON Body และ Form POST) ---
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$studentCode = $input['student_code'] ?? $_POST['student_code'] ?? '';

// --- Validate API Key (รับจาก Header หรือ Body ก็ได้) ---
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$apiKeyFromHeader = str_replace('Bearer ', '', $authHeader);
$apiKeyFromBody   = $input['api_key'] ?? $_POST['api_key'] ?? '';
$apiKey = !empty($apiKeyFromHeader) ? $apiKeyFromHeader : $apiKeyFromBody;

if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'API key required']);
    exit;
}

$db = Database::connect();
$stmt = $db->prepare('SELECT id FROM api_keys WHERE api_key = ? AND is_active = 1');
$stmt->execute([$apiKey]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid API key']);
    exit;
}

if (empty($studentCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'student_code is required']);
    exit;
}

// --- Find student ---
$stmt = $db->prepare('SELECT id, title, first_name, last_name, room FROM students WHERE student_code = ? AND is_active = 1');
$stmt->execute([$studentCode]);
$student = $stmt->fetch();

if (!$student) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// --- Record attendance ---
$today = date('Y-m-d');
$now   = date('H:i:s');

$stmt = $db->prepare(
    'INSERT INTO attendance (student_id, attendance_date, check_in_time, status)
     VALUES (?, ?, ?, "present")
     ON DUPLICATE KEY UPDATE check_in_time = VALUES(check_in_time), status = "present"'
);
$stmt->execute([$student['id'], $today, $now]);

$fullName = $student['title'] . $student['first_name'] . ' ' . $student['last_name'];

echo json_encode([
    'success'      => true,
    'student_name' => $fullName,
    'room'         => $student['room'],
    'time'         => date('H:i'),
    'message'      => $fullName . ' เข้าเรียนแล้ว เวลา ' . date('H:i') . ' น.'
]);
