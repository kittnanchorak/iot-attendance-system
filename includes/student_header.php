<?php
/**
 * Student Header Template
 */
$base = getBaseUrl();
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$std = $_SESSION['student_data'] ?? null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'ระบบยื่นคำร้องลาหยุด' ?> — ระบบลาหยุดนักเรียน</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    
    <style>
        .student-navbar {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .student-nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: var(--text-primary);
            text-decoration: none;
        }
        .student-nav-brand:hover {
            color: var(--text-primary);
        }
        .student-nav-link {
            color: var(--text-secondary);
            padding: 8px 14px;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all var(--transition);
            text-decoration: none;
        }
        .student-nav-link:hover, .student-nav-link.active {
            color: var(--accent);
            background: var(--accent-bg);
        }
        .student-user-badge {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 4px 14px 4px 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .student-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .status-badge.pending {
            background: var(--warning-bg);
            color: var(--warning);
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        .status-badge.approved {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(52, 211, 153, 0.3);
        }
        .status-badge.rejected {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(248, 113, 113, 0.3);
        }
    </style>
</head>
<body style="min-height: 100vh; background: var(--bg-root);">
    <header class="student-navbar">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
            <a href="<?= $base ?>/pages/student_leave_apply.php" class="student-nav-brand">
                <img src="<?= $base ?>/img/logo.png" alt="Logo" style="width: 36px; height: 36px; object-fit: contain;">
                <div>
                    <div style="line-height: 1.2;">ระบบการลาหยุดนักเรียน</div>
                    <small style="font-size: 0.75rem; color: var(--text-muted);">IoT Student Attendance System</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                <a href="<?= $base ?>/pages/student_leave_apply.php" class="student-nav-link <?= $currentPage === 'student_leave_apply' ? 'active' : '' ?>">
                    <i data-lucide="file-plus-2" style="width: 18px; height: 18px;"></i>
                    <span>ยื่นคำร้องลา</span>
                </a>
                <a href="<?= $base ?>/pages/student_leave_history.php" class="student-nav-link <?= $currentPage === 'student_leave_history' ? 'active' : '' ?>">
                    <i data-lucide="history" style="width: 18px; height: 18px;"></i>
                    <span>ประวัติคำร้อง</span>
                </a>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="student-user-badge">
                    <div class="student-avatar">
                        <?= mb_substr($_SESSION['full_name'] ?? 'S', 0, 1) ?>
                    </div>
                    <div class="d-none d-sm-block text-start">
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary); line-height: 1.2;">
                            <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            รหัส: <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                        </div>
                    </div>
                </div>

                <a href="<?= $base ?>/pages/logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" title="ออกจากระบบ" style="border-radius: var(--radius-md); padding: 6px 12px;">
                    <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                    <span class="d-none d-md-inline">ออก</span>
                </a>
            </div>
        </div>
    </header>
