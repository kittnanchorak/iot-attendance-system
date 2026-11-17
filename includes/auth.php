<?php
/**
 * Authentication Helper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * ตรวจสอบว่า Login แล้วหรือยัง
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * ตรวจสอบสิทธิ์ Admin
 */
function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * ตรวจสอบสิทธิ์ Teacher
 */
function isTeacher(): bool
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['teacher', 'admin']);
}

/**
 * ตรวจสอบว่าเป็นนักเรียนหรือไม่
 */
function isStudent(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

/**
 * บังคับ Login — redirect ไป login page ถ้ายังไม่ login
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . '/pages/login.php');
        exit;
    }
}

/**
 * บังคับ Login สำหรับนักเรียน
 */
function requireStudent(): void
{
    if (!isLoggedIn() || !isStudent()) {
        header('Location: ' . getBaseUrl() . '/pages/student_login.php');
        exit;
    }
}

/**
 * บังคับ Login สำหรับอาจารย์หรือผู้ดูแลระบบ
 */
function requireStaff(): void
{
    requireLogin();
    if (!isTeacher()) {
        header('Location: ' . getBaseUrl() . '/pages/student_leave_apply.php');
        exit;
    }
}

/**
 * บังคับ Admin — redirect ถ้าไม่ใช่ admin
 */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . getBaseUrl() . '/pages/dashboard.php');
        exit;
    }
}

/**
 * Login สำหรับอาจารย์ / ผู้ดูแลระบบ
 */
function login(string $username, string $password): array
{
    $db = Database::connect();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? AND role IN ("admin", "teacher")');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['student_id'] = $user['student_id'] ?? null;
        $_SESSION['avatar']     = $user['avatar'] ?? '';
        return ['success' => true];
    }

    return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
}

/**
 * Login สำหรับนักเรียน (ใช้รหัสนักศึกษา 6 หลัก หรือรหัสเต็ม 11 หลัก)
 */
function loginStudent(string $studentIdentifier, string $password): array
{
    $db = Database::connect();
    
    // ค้นหานักศึกษาจาก student_code หรือ student_id_full
    $cleanId = trim($studentIdentifier);
    $stmt = $db->prepare('SELECT * FROM students WHERE (student_code = ? OR student_id_full = ? OR REPLACE(student_id_full, " ", "") = ?) AND is_active = 1 LIMIT 1');
    $stmt->execute([$cleanId, $cleanId, str_replace(' ', '', $cleanId)]);
    $student = $stmt->fetch();

    if (!$student) {
        return ['success' => false, 'message' => 'ไม่พบข้อมูลนักศึกษานี้ในระบบ'];
    }

    // ตรวจสอบว่ามีบัญชีใน users หรือไม่
    $uStmt = $db->prepare('SELECT * FROM users WHERE student_id = ? OR username = ? LIMIT 1');
    $uStmt->execute([$student['id'], $student['student_code']]);
    $user = $uStmt->fetch();

    if ($user) {
        // มีบัญชีแล้ว ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['full_name']  = $student['title'] . $student['first_name'] . ' ' . $student['last_name'];
            $_SESSION['role']       = 'student';
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['student_data'] = $student;
            $_SESSION['avatar']     = $user['avatar'] ?? '';
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง'];
        }
    } else {
        // หากยังไม่มีบัญชีใน users: อนุญาตให้ login ครั้งแรกด้วย password = student_code หรือ student_id_full
        // แล้วทำการสร้างบัญชีให้ทันที
        $validDefaults = [
            $student['student_code'],
            $student['student_id_full'],
            str_replace(' ', '', $student['student_id_full'])
        ];

        if (in_array(trim($password), $validDefaults)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $fullName = $student['title'] . $student['first_name'] . ' ' . $student['last_name'];
            
            $insStmt = $db->prepare('INSERT INTO users (username, password, full_name, role, student_id) VALUES (?, ?, ?, "student", ?)');
            $insStmt->execute([$student['student_code'], $hashedPassword, $fullName, $student['id']]);
            $newUserId = (int)$db->lastInsertId();

            $_SESSION['user_id']    = $newUserId;
            $_SESSION['username']   = $student['student_code'];
            $_SESSION['full_name']  = $fullName;
            $_SESSION['role']       = 'student';
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['student_data'] = $student;
            $_SESSION['avatar']     = '';
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'รหัสผ่านเริ่มต้นไม่ถูกต้อง (ใช้รหัสนักเรียน 6 หลักหรือรหัสเต็ม)'];
        }
    }
}

/**
 * Logout
 */
function logout(): void
{
    $isStd = isStudent();
    session_destroy();
    if ($isStd) {
        header('Location: ' . getBaseUrl() . '/pages/student_login.php');
    } else {
        header('Location: ' . getBaseUrl() . '/pages/login.php');
    }
    exit;
}

/**
 * Base URL ของโปรเจกต์
 */
function getBaseUrl(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $base = preg_replace('#/(pages|api|includes|config)$#', '', $scriptDir);
    return rtrim($base, '/');
}

/**
 * ดึงค่า Setting จากตาราง settings เดิม
 */
function getSetting(string $key, string $default = ''): string
{
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];

    try {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['setting_value'] : $default;
        return $cache[$key];
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * ดึงค่า System Setting สำหรับระบบลา
 */
function getSystemSetting(string $key, string $default = ''): string
{
    static $sysCache = [];
    if (isset($sysCache[$key])) return $sysCache[$key];

    try {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $sysCache[$key] = $row ? $row['setting_value'] : $default;
        return $sysCache[$key];
    } catch (Exception $e) {
        return $default;
    }
}
