<?php
/**
 * API: จัดการข้อมูลนักศึกษา (CRUD)
 * POST   body: {action:"add",    ...fields}
 * POST   body: {action:"edit",   id:N, ...fields}
 * POST   body: {action:"delete", id:N}
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::connect();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ---------- ADD ----------
if ($action === 'add') {
    $title         = trim($input['title'] ?? '');
    $first_name    = trim($input['first_name'] ?? '');
    $last_name     = trim($input['last_name'] ?? '');
    $room          = trim($input['room'] ?? '');
    $id_full       = trim($input['student_id_full'] ?? '');
    $code          = trim($input['student_code'] ?? '');

    if (!$title || !$first_name || !$last_name || !$room || !$id_full || !$code) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
        exit;
    }
    if (strlen($code) !== 6 || !ctype_digit($code)) {
        echo json_encode(['success' => false, 'message' => 'รหัส 6 หลักต้องเป็นตัวเลขเท่านั้น']);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO students (student_id_full, student_code, title, first_name, last_name, room) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$id_full, $code, $title, $first_name, $last_name, $room]);
        echo json_encode(['success' => true, 'message' => 'เพิ่มนักศึกษาสำเร็จ']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'รหัส 6 หลักซ้ำในระบบ กรุณาตรวจสอบ']);
    }
    exit;
}

// ---------- EDIT ----------
if ($action === 'edit') {
    $id         = intval($input['id'] ?? 0);
    $title      = trim($input['title'] ?? '');
    $first_name = trim($input['first_name'] ?? '');
    $last_name  = trim($input['last_name'] ?? '');
    $room       = trim($input['room'] ?? '');
    $id_full    = trim($input['student_id_full'] ?? '');
    $code       = trim($input['student_code'] ?? '');
    $is_active  = intval($input['is_active'] ?? 1);

    if (!$id || !$title || !$first_name || !$last_name || !$room || !$id_full || !$code) {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE students SET student_id_full=?, student_code=?, title=?, first_name=?, last_name=?, room=?, is_active=? WHERE id=?");
        $stmt->execute([$id_full, $code, $title, $first_name, $last_name, $room, $is_active, $id]);
        echo json_encode(['success' => true, 'message' => 'แก้ไขข้อมูลสำเร็จ']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'รหัส 6 หลักซ้ำในระบบ']);
    }
    exit;
}

// ---------- DELETE ----------
if ($action === 'delete') {
    $id = intval($input['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบ ID']);
        exit;
    }
    $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'ลบนักศึกษาสำเร็จ']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'action ไม่ถูกต้อง']);
