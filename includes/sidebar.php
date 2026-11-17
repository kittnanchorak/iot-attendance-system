<?php
/**
 * Sidebar Navigation
 */
$base = getBaseUrl();
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$userAvatar = $_SESSION['avatar'] ?? '';
$hasAvatar = !empty($userAvatar) && file_exists(__DIR__ . '/../uploads/avatars/' . $userAvatar);
$avatarSrc = $hasAvatar ? $base . '/uploads/avatars/' . htmlspecialchars($userAvatar) : '';
?>
<!-- Mobile Header -->
<header class="mobile-header">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
        <i data-lucide="menu" class="icon"></i>
    </button>
    <span class="mobile-title">IoT Attendance</span>
    
    <div class="d-flex align-items-center gap-2">
        <!-- Mobile User Profile Popover -->
        <div class="dropdown">
            <button class="user-avatar border-0 p-0 overflow-hidden d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="ตั้งค่าโปรไฟล์">
                <?php if ($hasAvatar): ?>
                    <img src="<?= $avatarSrc ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <?= mb_substr($_SESSION['full_name'] ?? 'U', 0, 1) ?>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-secondary mt-2">
                <li class="px-3 py-2 border-bottom border-secondary opacity-75">
                    <div class="fw-bold text-white small"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
                    <div class="text-muted text-xs">
                        <?= $_SESSION['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'อาจารย์' ?>
                    </div>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $base ?>/pages/profile.php">
                        <i data-lucide="user-cog" style="width: 16px; height: 16px; color: var(--accent);"></i>
                        <span>ตั้งค่าโปรไฟล์</span>
                    </a>
                </li>
                <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="<?= $base ?>/pages/logout.php">
                        <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand mb-3">
            <div class="brand-icon" style="background: transparent;">
                <img src="<?= $base ?>/img/logo.png" alt="Logo"
                    style="width: 36px; height: 36px; object-fit: contain; border-radius: var(--radius-md);">
            </div>
            <div class="brand-text">
                <span class="brand-name">IoT Attendance</span>
                <span class="brand-sub">Password Authentication System for Student Attendance</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">เมนูหลัก</span>
            <a href="<?= $base ?>/pages/dashboard.php"
                class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard" class="nav-icon"></i>
                <span>แดชบอร์ด</span>
            </a>
            <a href="<?= $base ?>/pages/attendance.php"
                class="nav-item <?= $currentPage === 'attendance' ? 'active' : '' ?>">
                <i data-lucide="clipboard-check" class="nav-icon"></i>
                <span>เข้าเรียนวันนี้</span>
            </a>
            <a href="<?= $base ?>/pages/history.php" class="nav-item <?= $currentPage === 'history' ? 'active' : '' ?>">
                <i data-lucide="calendar-days" class="nav-icon"></i>
                <span>ประวัติย้อนหลัง</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">ระบบการลา</span>
            <a href="<?= $base ?>/pages/leave_manage.php"
                class="nav-item <?= in_array($currentPage, ['leave_manage', 'leave_settings']) ? 'active' : '' ?>">
                <i data-lucide="clipboard-list" class="nav-icon"></i>
                <span>พิจารณาคำร้องลา</span>
            </a>
            <a href="<?= $base ?>/pages/leave_stats.php"
                class="nav-item <?= $currentPage === 'leave_stats' ? 'active' : '' ?>">
                <i data-lucide="bar-chart-2" class="nav-icon"></i>
                <span>สถิติการลา</span>
            </a>
        </div>

        <?php if (isAdmin()): ?>
        <div class="nav-section">
            <span class="nav-section-title">จัดการระบบ</span>
            <a href="<?= $base ?>/pages/students.php"
                class="nav-item <?= $currentPage === 'students' ? 'active' : '' ?>">
                <i data-lucide="users" class="nav-icon"></i>
                <span>จัดการนักศึกษา</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <!-- 👤 User Profile Footer -->
    <div class="sidebar-footer">
        <div class="dropdown dropup w-100">
            <button class="user-profile-btn border-0 bg-transparent text-start w-100 d-flex align-items-center justify-content-between p-1" 
                    type="button" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="โปรไฟล์และตั้งค่า">
                <div class="user-info">
                    <span class="user-avatar overflow-hidden d-flex align-items-center justify-content-center">
                        <?php if ($hasAvatar): ?>
                            <img src="<?= $avatarSrc ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <?= mb_substr($_SESSION['full_name'] ?? 'U', 0, 1) ?>
                        <?php endif; ?>
                    </span>
                    <div class="user-details">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
                        <span class="user-role">
                            <?= $_SESSION['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'อาจารย์' ?>
                        </span>
                    </div>
                </div>
                <i data-lucide="more-vertical" class="icon text-muted ms-2" style="width: 16px; height: 16px;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-dark shadow-lg border-secondary mb-2 w-100" aria-labelledby="userProfileDropdown">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $base ?>/pages/profile.php">
                        <i data-lucide="user-cog" style="width: 16px; height: 16px; color: var(--accent);"></i>
                        <span>ตั้งค่าโปรไฟล์</span>
                    </a>
                </li>
                <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="<?= $base ?>/pages/logout.php">
                        <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>