<?php
/**
 * Login Page
 */
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = login($username, $password);
    if ($result['success']) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IoT Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card fade-in">
        <div class="login-logo">
            <div class="brand-icon" style="background: transparent; width: 64px; height: 64px;">
                <img src="../img/logo.png" alt="Logo" style="width: 64px; height: 64px; object-fit: contain; border-radius: var(--radius-lg);">
            </div>
        </div>
        <h1 class="login-title">ยินดีต้อนรับกลับมา</h1>
        <p class="login-subtitle">Password Authentication System for Student Attendance</p>

        <?php if ($error): ?>
            <div class="login-error" style="display:block;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="username">ชื่อผู้ใช้ (Username)</label>
                <input type="text" id="username" name="username" class="form-control-custom" placeholder="admin หรือ teacher" required autofocus>
            </div>
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" for="password">รหัสผ่าน (Password)</label>
                <input type="password" id="password" name="password" class="form-control-custom" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
        </form>

        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); font-size: 0.9rem;">
            <span style="color: var(--text-muted);">สำหรับนักเรียน / นักศึกษา:</span>
            <a href="student_login.php" style="margin-left: 6px; font-weight: 500;">
                เข้าสู่ระบบนักเรียน &rarr;
            </a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
