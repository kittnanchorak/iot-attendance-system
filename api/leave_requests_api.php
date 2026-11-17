<?php
/**
 * API: Leave Requests Management (For Teachers & Admins)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || (!isTeacher() && !isAdmin())) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::connect();
$action = $_GET['action'] ?? '';

// 1. ดึงรายการคำร้องพร้อมตัวกรองและสถิติภาพรวม
if ($action === 'list') {
    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $type   = trim($_GET['type'] ?? '');
    $room   = trim($_GET['room'] ?? '');

    $where = ['1=1'];
    $params = [];

    if (!empty($search)) {
        $where[] = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_code LIKE ? OR s.student_id_full LIKE ? OR r.request_no LIKE ?)';
        $searchWild = "%{$search}%";
        array_push($params, $searchWild, $searchWild, $searchWild, $searchWild, $searchWild);
    }

    if (!empty($status) && in_array($status, ['pending', 'approved', 'rejected'])) {
        $where[] = 'r.status = ?';
        $params[] = $status;
    }

    if (!empty($type) && in_array($type, ['sick', 'personal'])) {
        $where[] = 'r.leave_type = ?';
        $params[] = $type;
    }

    if (!empty($room)) {
        $where[] = 's.room = ?';
        $params[] = $room;
    }

    $whereSql = implode(' AND ', $where);

    $sql = "
        SELECT r.*,
               CONCAT(s.title, s.first_name, ' ', s.last_name) AS student_name,
               s.student_code, s.student_id_full, s.room,
               a.id AS attachment_id, a.file_name, a.file_size,
               ap.action AS approval_action, ap.comment AS approval_comment, ap.action_at,
               u.full_name AS approver_name
        FROM leave_requests r
        JOIN students s ON r.student_id = s.id
        LEFT JOIN leave_attachments a ON r.id = a.leave_request_id
        LEFT JOIN leave_approvals ap ON r.id = ap.leave_request_id
        LEFT JOIN users u ON ap.action_by = u.id
        WHERE {$whereSql}
        ORDER BY 
            CASE WHEN r.status = 'pending' THEN 1 ELSE 2 END,
            r.id DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // จัดรูปแบบข้อมูลแสดงผล
    $data = [];
    foreach ($rows as $row) {
        $data[] = [
            'id'              => (int)$row['id'],
            'request_no'      => $row['request_no'],
            'student_id'      => (int)$row['student_id'],
            'student_name'    => $row['student_name'],
            'student_code'    => $row['student_code'],
            'student_id_full' => $row['student_id_full'],
            'room'            => $row['room'],
            'leave_type'      => $row['leave_type'],
            'start_date'      => $row['start_date'],
            'end_date'        => $row['end_date'],
            'start_date_fmt'  => date('d/m/Y', strtotime($row['start_date'])),
            'end_date_fmt'    => date('d/m/Y', strtotime($row['end_date'])),
            'total_days'      => (int)$row['total_days'],
            'reason'          => $row['reason'],
            'status'          => $row['status'],
            'created_at'      => $row['created_at'],
            'created_at_fmt'  => date('d/m/Y H:i', strtotime($row['created_at'])),
            'attachment_id'   => $row['attachment_id'] ? (int)$row['attachment_id'] : null,
            'file_name'       => $row['file_name'],
            'file_size'       => $row['file_size'] ? (int)$row['file_size'] : 0,
            'approval_action' => $row['approval_action'],
            'approval_comment'=> $row['approval_comment'],
            'action_at'       => $row['action_at'] ? date('d/m/Y H:i', strtotime($row['action_at'])) : null,
            'approver_name'   => $row['approver_name']
        ];
    }

    // คำนวณสรุปยอดคำร้องทั้งหมด
    $statStmt = $db->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected
        FROM leave_requests
    ");
    $stats = $statStmt->fetch() ?: ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];

    echo json_encode([
        'success' => true,
        'data'    => $data,
        'stats'   => $stats
    ]);
    exit;
}

// 2. พิจารณาอนุมัติ / ไม่อนุมัติคำร้อง
if ($action === 'decide') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];

    $requestId = (int)($input['leave_request_id'] ?? 0);
    $decision  = trim($input['action'] ?? '');
    $comment   = trim($input['comment'] ?? '');

    if ($requestId <= 0 || !in_array($decision, ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
        exit;
    }

    // ตรวจสอบว่ามีคำร้องนี้อยู่หรือไม่
    $chkStmt = $db->prepare('SELECT * FROM leave_requests WHERE id = ?');
    $chkStmt->execute([$requestId]);
    $leaveReq = $chkStmt->fetch();

    if (!$leaveReq) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบคำร้องที่ระบุ']);
        exit;
    }

    try {
        $db->beginTransaction();

        // อัปเดตสถานะใน leave_requests
        $updStmt = $db->prepare('UPDATE leave_requests SET status = ?, updated_at = NOW() WHERE id = ?');
        $updStmt->execute([$decision, $requestId]);

        // บันทึกหรืออัปเดตประวัติการพิจารณาใน leave_approvals
        $appStmt = $db->prepare('
            INSERT INTO leave_approvals (leave_request_id, action_by, action, comment, action_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                action_by = VALUES(action_by),
                action = VALUES(action),
                comment = VALUES(comment),
                action_at = NOW()
        ');
        $appStmt->execute([$requestId, $_SESSION['user_id'], $decision, $comment]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => ($decision === 'approved' ? 'อนุมัติคำร้องเรียบร้อยแล้ว' : 'บันทึกการไม่อนุมัติคำร้องเรียบร้อยแล้ว')
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
