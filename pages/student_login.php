<?php
/**
 * Student Login Page - Modern UI Design
 */
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    if (isStudent()) {
        header('Location: student_leave_apply.php');
        exit;
    } else {
        header('Location: dashboard.php');
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentIdentifier = trim($_POST['student_code'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($studentIdentifier) || empty($password)) {
        $error = 'กรุณากรอกรหัสนักศึกษาและรหัสผ่าน';
    } else {
        $res = loginStudent($studentIdentifier, $password);
        if ($res['success']) {
            header('Location: student_leave_apply.php');
            exit;
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบสำหรับนักเรียน - ระบบการลาหยุดออนไลน์</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --std-accent: #6366f1;
            --std-accent-hover: #4f46e5;
            --std-accent-glow: rgba(99, 102, 241, 0.25);
            --std-card-bg: rgba(30, 41, 59, 0.85);
            --std-border: rgba(255, 255, 255, 0.12);
        }

        body.student-login-body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans Thai', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background: radial-gradient(circle at 50% 15%, #1e1b4b 0%, #0f172a 50%, #090d16 100%);
            position: relative;
            overflow-x: hidden;
            padding: 24px;
        }

        /* Ambient Glow Background Orbs */
        .ambient-glow-1 {
            position: absolute;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.18) 0%, rgba(99, 102, 241, 0) 70%);
            filter: blur(50px);
            pointer-events: none;
            z-index: 0;
        }

        .ambient-glow-2 {
            position: absolute;
            bottom: 10%;
            right: 25%;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, rgba(168, 85, 247, 0) 70%);
            filter: blur(60px);
            pointer-events: none;
            z-index: 0;
        }

        /* Modern Glass Card */
        .modern-login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            background: var(--std-card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--std-border);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.55),
                        0 0 0 1px rgba(255, 255, 255, 0.05);
            animation: cardEntrance 0.5s ease-out;
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Header / Logo section */
        .card-header-section {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo-circle-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .logo-circle-wrapper img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .logo-badge-icon {
            position: absolute;
            bottom: -6px;
            right: -6px;
            background: var(--std-accent);
            color: #fff;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
        }

        .system-badge-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(99, 102, 241, 0.14);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 10px;
            letter-spacing: 0.02em;
        }

        .card-title-main {
            font-size: 1.6rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
            line-height: 1.25;
        }

        .card-subtitle-main {
            font-size: 0.92rem;
            color: #94a3b8;
            margin: 0;
        }

        /* Tips Notice Box */
        .tips-notice-card {
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.22);
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        .tips-notice-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(99, 102, 241, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a5b4fc;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .tips-notice-text {
            font-size: 0.82rem;
            color: #cbd5e1;
            line-height: 1.5;
        }

        .tips-notice-text code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            color: #e0e7ff;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Form Controls */
        .form-floating-group {
            margin-bottom: 18px;
        }

        .input-label-styled {
            display: block;
            font-size: 0.88rem;
            font-weight: 500;
            color: #e2e8f0;
            margin-bottom: 8px;
        }

        .input-box-wrapper {
            position: relative;
        }

        .input-icon-left {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .input-control-styled {
            width: 100%;
            background: rgba(15, 23, 42, 0.7);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 12px 16px 12px 44px;
            font-size: 0.95rem;
            color: #ffffff;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            box-sizing: border-box;
        }

        .input-control-styled::placeholder {
            color: #64748b;
        }

        .input-control-styled:focus {
            border-color: var(--std-accent);
            background: rgba(15, 23, 42, 0.95);
            box-shadow: 0 0 0 3px var(--std-accent-glow);
        }

        .input-control-styled:focus + .input-icon-left,
        .input-box-wrapper:focus-within .input-icon-left {
            color: var(--std-accent);
        }

        /* Submit Button */
        .btn-student-submit {
            width: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 13px 20px;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px -4px rgba(99, 102, 241, 0.45);
            margin-top: 10px;
        }

        .btn-student-submit:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -4px rgba(99, 102, 241, 0.55);
        }

        .btn-student-submit:active {
            transform: translateY(0);
        }

        /* Footer Link */
        .card-footer-switch {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.88rem;
            color: #94a3b8;
        }

        .card-footer-switch a {
            color: #a5b4fc;
            text-decoration: none;
            font-weight: 600;
            margin-left: 6px;
            transition: color 0.15s ease;
        }

        .card-footer-switch a:hover {
            color: #c7d2fe;
            text-decoration: underline;
        }
    </style>
</head>
<body class="student-login-body">
    <!-- Background Glow Orbs -->
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <div class="modern-login-card">
        <!-- Logo & Header -->
        <div class="card-header-section">
            <div class="logo-circle-wrapper">
                <img src="../img/logo.png" alt="Logo">
                <div class="logo-badge-icon">
                    <i data-lucide="graduation-cap" style="width: 14px; height: 14px;"></i>
                </div>
            </div>
            <div>
                <span class="system-badge-tag">
                    <i data-lucide="sparkles" style="width: 12px; height: 12px;"></i>
                    Student Leave Portal
                </span>
            </div>
            <h1 class="card-title-main">ระบบยื่นคำร้องลาหยุด</h1>
            <p class="card-subtitle-main">เข้าสู่ระบบสำหรับนักเรียน / นักศึกษา</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" 
                 style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); padding: 12px 14px; border-radius: 12px; font-size: 0.88rem;">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Tips / Notice -->
        <div class="tips-notice-card">
            <div class="tips-notice-icon">
                <i data-lucide="info" style="width: 16px; height: 16px;"></i>
            </div>
            <div class="tips-notice-text">
                <strong>คำแนะนำการเข้าสู่ระบบครั้งแรก:</strong><br>
                ใช้รหัสนักศึกษา 6 หลัก (เช่น <code>032791</code>) เป็นทั้งชื่อผู้ใช้และรหัสผ่านเริ่มต้น
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="student_login.php">
            <div class="form-floating-group">
                <label for="student_code" class="input-label-styled">
                    รหัสนักศึกษา (6 หลัก หรือ 11 หลัก)
                </label>
                <div class="input-box-wrapper">
                    <input type="text" id="student_code" name="student_code" 
                           class="input-control-styled" 
                           placeholder="เช่น 032791" 
                           required autofocus
                           value="<?= htmlspecialchars($_POST['student_code'] ?? '') ?>">
                    <div class="input-icon-left">
                        <i data-lucide="user" style="width: 18px; height: 18px;"></i>
                    </div>
                </div>
            </div>

            <div class="form-floating-group" style="margin-bottom: 24px;">
                <label for="password" class="input-label-styled">
                    รหัสผ่าน
                </label>
                <div class="input-box-wrapper">
                    <input type="password" id="password" name="password" 
                           class="input-control-styled" 
                           placeholder="••••••••" 
                           required>
                    <div class="input-icon-left">
                        <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-student-submit">
                <i data-lucide="log-in" style="width: 18px; height: 18px;"></i>
                <span>เข้าสู่ระบบ</span>
            </button>
        </form>

        <div class="card-footer-switch">
            <span>สำหรับอาจารย์หรือผู้ดูแลระบบ:</span>
            <a href="login.php">เข้าสู่ระบบอาจารย์ &rarr;</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
