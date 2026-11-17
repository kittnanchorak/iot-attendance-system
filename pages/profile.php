<?php
/**
 * Teacher Profile & System Settings Page
 * IoT Attendance System - Modern IG/FB Style Profile
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = Database::connect();
$userId = $_SESSION['user_id'];
$pageTitle = 'ตั้งค่าโปรไฟล์';

$successMsg = '';
$errorMsg = '';

if (!empty($_SESSION['flash_success'])) {
    $successMsg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (!empty($_SESSION['flash_error'])) {
    $errorMsg = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

// จัดการการส่งฟอร์ม (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 0. ลบรูปโปรไฟล์อย่างเดียว
    if ($action === 'remove_avatar') {
        $avatarFilename = $user['avatar'] ?? '';
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!empty($avatarFilename) && file_exists($uploadDir . $avatarFilename)) {
            @unlink($uploadDir . $avatarFilename);
        }
        $updateStmt = $db->prepare('UPDATE users SET avatar = NULL WHERE id = ?');
        $updateStmt->execute([$userId]);
        $_SESSION['avatar'] = '';

        $_SESSION['flash_success'] = 'ลบรูปโปรไฟล์เรียบร้อยแล้ว!';
        header('Location: ' . getBaseUrl() . '/pages/profile.php');
        exit;
    }

    // 1. อัปเดตข้อมูลโปรไฟล์และรูปภาพ
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');

        if (empty($fullName)) {
            $errorMsg = 'กรุณากรอกชื่อ-นามสกุลอาจารย์';
        } else {
            $avatarFilename = $user['avatar'] ?? null;
            $uploadDir = __DIR__ . '/../uploads/avatars/';

            // ลบรูปภาพเดิมถ้าถูกเลือกให้ลบ
            $isRemoveAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';
            if ($isRemoveAvatar) {
                if (!empty($avatarFilename) && file_exists($uploadDir . $avatarFilename)) {
                    @unlink($uploadDir . $avatarFilename);
                }
                $avatarFilename = null;
            }

            // จัดการไฟล์รูปภาพใหม่ (ถ้ามี)
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['avatar']['tmp_name'];
                $fileName = $_FILES['avatar']['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (!in_array($fileExt, $allowed)) {
                    $errorMsg = 'รองรับเฉพาะไฟล์รูปภาพประเภท JPG, PNG, WEBP หรือ GIF เท่านั้น';
                } elseif ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
                    $errorMsg = 'ขนาดไฟล์รูปภาพต้องไม่เกิน 5MB';
                } else {
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // ลบรูปภาพเก่า
                    if (!empty($avatarFilename) && file_exists($uploadDir . $avatarFilename)) {
                        @unlink($uploadDir . $avatarFilename);
                    }

                    $newFilename = 'avatar_' . $userId . '_' . time() . '.' . $fileExt;
                    if (move_uploaded_file($fileTmp, $uploadDir . $newFilename)) {
                        $avatarFilename = $newFilename;
                    } else {
                        $errorMsg = 'เกิดข้อผิดพลาดในการบันทึกไฟล์รูปภาพ';
                    }
                }
            }

            if (empty($errorMsg)) {
                $updateStmt = $db->prepare('UPDATE users SET full_name = ?, avatar = ? WHERE id = ?');
                if ($updateStmt->execute([$fullName, $avatarFilename, $userId])) {
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['avatar'] = $avatarFilename ?? '';
                    $_SESSION['flash_success'] = $isRemoveAvatar ? 'ลบรูปโปรไฟล์เรียบร้อยแล้ว!' : 'บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว!';
                    header('Location: ' . getBaseUrl() . '/pages/profile.php');
                    exit;
                } else {
                    $errorMsg = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง';
                }
            }
        }
    }

    // 2. เปลี่ยนรหัสผ่าน
    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMsg = 'กรุณากรอกข้อมูลรหัสผ่านให้ครบทุกช่อง';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errorMsg = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = 'รหัสผ่านใหม่และรหัสผ่านยืนยันไม่ตรงกัน';
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $passStmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            if ($passStmt->execute([$hashedPassword, $userId])) {
                $_SESSION['flash_success'] = 'เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว!';
                header('Location: ' . getBaseUrl() . '/pages/profile.php');
                exit;
            } else {
                $errorMsg = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน';
            }
        }
    }
}

$userAvatar = $user['avatar'] ?? '';
$hasAvatar = !empty($userAvatar) && file_exists(__DIR__ . '/../uploads/avatars/' . $userAvatar);
$avatarSrc = $hasAvatar ? getBaseUrl() . '/uploads/avatars/' . htmlspecialchars($userAvatar) : '';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header fade-in">
        <h1 class="page-title">ตั้งค่าโปรไฟล์ผู้ใช้งาน</h1>
        <p class="page-subtitle">จัดการข้อมูลส่วนตัว รูปโปรไฟล์ และเปลี่ยนรหัสผ่านผู้ใช้งานระบบ</p>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div class="toast-item mb-4" style="border-color: var(--success); background: var(--success-bg);">
            <i data-lucide="check-circle-2" class="toast-icon success"></i>
            <div class="toast-body">
                <div class="toast-title" style="color: var(--success);">สำเร็จ</div>
                <div class="toast-message"><?= htmlspecialchars($successMsg) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="toast-item mb-4" style="border-color: var(--danger); background: var(--danger-bg);">
            <i data-lucide="alert-circle" class="toast-icon" style="color: var(--danger);"></i>
            <div class="toast-body">
                <div class="toast-title" style="color: var(--danger);">แจ้งเตือน</div>
                <div class="toast-message"><?= htmlspecialchars($errorMsg) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 🌟 Instagram / Facebook Style Header Card -->
    <div class="profile-header-card fade-in">
        <div class="profile-cover-banner">
            <div class="profile-cover-overlay"></div>
        </div>
        <div class="profile-info-bar">
            <!-- Avatar with IG Ring -->
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar-inner" id="avatarPreviewContainer">
                    <?php if ($hasAvatar): ?>
                        <img src="<?= $avatarSrc ?>" alt="Profile Picture" id="avatarPreviewImg">
                    <?php else: ?>
                        <span id="avatarPreviewInitial"><?= mb_substr($user['full_name'] ?? 'U', 0, 1) ?></span>
                        <img src="" alt="Profile Picture" id="avatarPreviewImg"
                            style="display:none; width:100%; height:100%; object-fit:cover;">
                    <?php endif; ?>
                </div>
                <button type="button" class="profile-avatar-upload-btn"
                    onclick="openAvatarModal();" title="จัดการรูปโปรไฟล์">
                    <i data-lucide="camera" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <!-- Profile Details Header -->
            <div class="profile-user-details">
                <div class="profile-user-name">
                    <?= htmlspecialchars($user['full_name']) ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <span class="profile-user-badge"
                            style="background: #059669; color: #ffffff; border: 1px solid #10b981; box-shadow: 0 2px 10px rgba(16, 185, 129, 0.4); font-weight: 700;">
                            <i data-lucide="shield-check" style="width:16px; height:16px;"></i> ผู้ดูแลระบบ
                        </span>
                    <?php else: ?>
                        <span class="profile-user-badge"
                            style="background: #059669; color: #ffffff; border: 1px solid #10b981; box-shadow: 0 2px 10px rgba(16, 185, 129, 0.4); font-weight: 700;">
                            <i data-lucide="user-check" style="width:16px; height:16px;"></i> อาจารย์ผู้สอน
                        </span>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-3 my-2">
                    <span
                        style="background: rgba(129, 140, 248, 0.25); color: #c7d2fe; border: 1px solid rgba(129, 140, 248, 0.4); padding: 4px 12px; border-radius: 8px; font-weight: 600; font-size: .9rem; display: inline-flex; align-items: center; gap: 4px;">
                        <i data-lucide="at-sign"
                            style="width: 14px; height: 14px; color: #a5b4fc;"></i><?= htmlspecialchars($user['username']) ?>
                    </span>
                    <span style="color: #94a3b8;">•</span>
                    <span
                        style="color: #ffffff; font-weight: 600; font-size: .92rem; display: inline-flex; align-items: center; gap: 6px; background: var(--bg-elevated); padding: 4px 12px; border-radius: 8px; border: 1px solid var(--border-strong);">
                        <i data-lucide="calendar" style="width: 15px; height: 15px; color: #818cf8;"></i>
                        <span>เข้าร่วมเมื่อ <?= date('d/m/Y', strtotime($user['created_at'] ?? 'now')) ?></span>
                    </span>
                </div>
                <div class="profile-stats-bar">
                    <div class="profile-stat-item">
                        <span>สถานะระบบ:</span> <strong><span class="badge-dot"
                                style="background: var(--success); width: 8px; height: 8px; display: inline-block; border-radius: 50%; margin-right: 4px;"></span>ใช้งานได้ปกติ</strong>
                    </div>
                    <div class="profile-stat-item">
                        <span>สิทธิ์การใช้งาน:</span>
                        <strong><?= $user['role'] === 'admin' ? 'Administrator' : 'Teacher' ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms Layout -->
    <div class="row g-4 slide-up">
        <!-- 1. Edit Profile Form & Upload -->
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-header">
                    <div>
                        <div class="panel-title d-flex align-items-center gap-2">
                            <i data-lucide="user" class="text-accent" style="width: 20px; height: 20px;"></i>
                            ข้อมูลโปรไฟล์ผู้ใช้
                        </div>
                        <div class="text-muted text-sm mt-1">อัปเดตชื่อ-นามสกุล และเปลี่ยนรูปถ่ายโปรไฟล์</div>
                    </div>
                </div>
                <div class="panel-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="profileForm">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">

                        <!-- File Input (Hidden) -->
                        <input type="file" name="avatar" id="avatarFileInput"
                            accept="image/png, image/jpeg, image/webp, image/gif" style="display: none;"
                            onchange="handleFileSelect(this)">



                        <div class="form-group mb-3">
                            <label class="form-label">ชื่อ-นามสกุล อาจารย์</label>
                            <input type="text" name="full_name" class="form-control-custom"
                                value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">ชื่อผู้ใช้ (Username)</label>
                            <input type="text" class="form-control-custom"
                                value="<?= htmlspecialchars($user['username']) ?>" disabled
                                style="opacity: .6; cursor: not-allowed;">
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">สิทธิ์การใช้งาน (Role)</label>
                            <input type="text" class="form-control-custom"
                                value="<?= $user['role'] === 'admin' ? 'ผู้ดูแลระบบ (Admin)' : 'อาจารย์ (Teacher)' ?>"
                                disabled style="opacity: .6; cursor: not-allowed;">
                        </div>

                        <button type="submit" class="btn-primary-custom w-100 justify-content-center">
                            <i data-lucide="save" class="icon"></i>
                            <span>บันทึกการเปลี่ยนแปลง</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Change Password Form -->
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-header">
                    <div>
                        <div class="panel-title d-flex align-items-center gap-2">
                            <i data-lucide="key-round" class="text-accent" style="width: 20px; height: 20px;"></i>
                            เปลี่ยนรหัสผ่าน
                        </div>
                        <div class="text-muted text-sm mt-1">เพื่อความปลอดภัย แนะนำให้ตั้งรหัสผ่านที่รัดกุม</div>
                    </div>
                </div>
                <div class="panel-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_password">

                        <div class="form-group mb-3 position-relative">
                            <label class="form-label">รหัสผ่านปัจจุบัน</label>
                            <div class="position-relative">
                                <input type="password" name="current_password" id="currPassInput"
                                    class="form-control-custom pe-5" required>
                                <button type="button"
                                    class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted p-2"
                                    onclick="togglePassVisibility('currPassInput', this)">
                                    <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">รหัสผ่านใหม่</label>
                            <div class="position-relative">
                                <input type="password" name="new_password" id="newPassInput"
                                    class="form-control-custom pe-5" placeholder="ขั้นต่ำ 6 ตัวอักษร" required
                                    oninput="checkPasswordStrength(this.value)">
                                <button type="button"
                                    class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted p-2"
                                    onclick="togglePassVisibility('newPassInput', this)">
                                    <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                                </button>
                            </div>
                            <div class="progress mt-2" style="height: 4px; background: var(--bg-elevated);">
                                <div id="passStrengthBar" class="progress-bar" style="width: 0%; transition: all .3s;">
                                </div>
                            </div>
                            <span id="passStrengthText" class="text-xs text-muted mt-1 d-block"></span>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                            <div class="position-relative">
                                <input type="password" name="confirm_password" id="confirmPassInput"
                                    class="form-control-custom pe-5" required>
                                <button type="button"
                                    class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted p-2"
                                    onclick="togglePassVisibility('confirmPassInput', this)">
                                    <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-secondary-custom w-100 justify-content-center">
                            <i data-lucide="shield-check" class="icon"></i>
                            <span>อัปเดตรหัสผ่านใหม่</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 🌟 Popup Modal for Profile Picture Actions -->
    <div id="avatarActionModal" class="custom-modal-overlay" style="display: none;" onclick="if(event.target === this) closeAvatarModal();">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <h5 class="custom-modal-title">
                    <i data-lucide="image" style="width: 20px; height: 20px; color: var(--accent);"></i>
                    จัดการรูปโปรไฟล์
                </h5>
                <button type="button" class="btn-close-modal" onclick="closeAvatarModal()">&times;</button>
            </div>
            <div class="custom-modal-body">
                <button type="button" class="avatar-menu-item" onclick="triggerFileInput();">
                    <div class="menu-icon-box purple">
                        <i data-lucide="camera" style="width: 22px; height: 22px;"></i>
                    </div>
                    <div class="menu-text-group">
                        <span class="menu-title">เปลี่ยนรูปโปรไฟล์</span>
                        <span class="menu-sub">เลือกรูปใหม่จากอุปกรณ์ของคุณ</span>
                    </div>
                </button>

                <?php if ($hasAvatar): ?>
                <button type="button" class="avatar-menu-item danger" onclick="showDeleteConfirm();">
                    <div class="menu-icon-box red">
                        <i data-lucide="trash-2" style="width: 22px; height: 22px;"></i>
                    </div>
                    <div class="menu-text-group">
                        <span class="menu-title text-danger">ลบรูปโปรไฟล์</span>
                        <span class="menu-sub text-danger-sub">กลับไปใช้รูปอวตารเริ่มต้น</span>
                    </div>
                </button>
                <?php endif; ?>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="btn-secondary-custom w-100 justify-content-center" onclick="closeAvatarModal();">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>

    <!-- ⚠️ Confirmation Popup Modal for Deleting Profile Picture -->
    <div id="confirmDeleteModal" class="custom-modal-overlay" style="display: none;" onclick="if(event.target === this) closeConfirmDeleteModal();">
        <div class="custom-modal-content" style="max-width: 400px; text-align: center;">
            <div class="custom-modal-body" style="padding: 28px 24px 22px;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(248, 113, 113, 0.15); color: #fca5a5; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; border: 1px solid rgba(248, 113, 113, 0.3);">
                    <i data-lucide="alert-triangle" style="width: 28px; height: 28px;"></i>
                </div>
                <h5 class="custom-modal-title" style="justify-content: center; font-size: 1.25rem; margin-bottom: 8px;">
                    ยืนยันการลบรูปโปรไฟล์
                </h5>
                <p class="text-secondary text-sm" style="margin-bottom: 24px; line-height: 1.5;">
                    คุณแน่ใจหรือไม่ว่าต้องการลบรูปโปรไฟล์ปัจจุบัน? <br>
                    <span class="text-muted text-xs">ระบบจะเปลี่ยนกลับไปใช้รูปอวตารชื่อเริ่มต้นของคุณ</span>
                </p>

                <!-- Hidden form to execute deletion -->
                <form method="POST" action="" id="deleteAvatarForm">
                    <input type="hidden" name="action" value="remove_avatar">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn-secondary-custom w-50 justify-content-center" onclick="closeConfirmDeleteModal();">
                            ยกเลิก
                        </button>
                        <button type="submit" class="btn-danger-custom w-50 justify-content-center py-2" style="background: #dc2626; color: #ffffff; border: none; font-weight: 700; border-radius: var(--radius-md); box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4); cursor: pointer;">
                            <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i> ยืนยัน ลบรูป
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    function openAvatarModal() {
        const modal = document.getElementById('avatarActionModal');
        if (modal) {
            modal.style.display = 'flex';
            if (window.lucide) lucide.createIcons();
        }
    }

    function closeAvatarModal() {
        const modal = document.getElementById('avatarActionModal');
        if (modal) modal.style.display = 'none';
    }

    function showDeleteConfirm() {
        closeAvatarModal();
        const modal = document.getElementById('confirmDeleteModal');
        if (modal) {
            modal.style.display = 'flex';
            if (window.lucide) lucide.createIcons();
        }
    }

    function closeConfirmDeleteModal() {
        const modal = document.getElementById('confirmDeleteModal');
        if (modal) modal.style.display = 'none';
    }

    function triggerFileInput() {
        closeAvatarModal();
        const fileInput = document.getElementById('avatarFileInput');
        if (fileInput) fileInput.click();
    }

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            document.getElementById('removeAvatarInput').value = '0';
            document.getElementById('profileForm').submit();
        }
    }

    function togglePassVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = '<i data-lucide="eye-off" style="width:16px; height:16px;"></i>';
        } else {
            input.type = 'password';
            btn.innerHTML = '<i data-lucide="eye" style="width:16px; height:16px;"></i>';
        }
        if (window.lucide) lucide.createIcons();
    }

    function checkPasswordStrength(val) {
        const bar = document.getElementById('passStrengthBar');
        const txt = document.getElementById('passStrengthText');
        if (!val) {
            bar.style.width = '0%';
            txt.textContent = '';
            return;
        }
        let strength = 0;
        if (val.length >= 6) strength += 30;
        if (val.length >= 10) strength += 20;
        if (/[A-Z]/.test(val)) strength += 25;
        if (/[0-9]/.test(val)) strength += 25;

        bar.style.width = strength + '%';
        if (strength < 40) {
            bar.style.background = 'var(--danger)';
            txt.textContent = 'ความปลอดภัยระดับต่ำ (ควรเพิ่มตัวเลขและสัญลักษณ์)';
            txt.style.color = 'var(--danger)';
        } else if (strength < 75) {
            bar.style.background = 'var(--warning)';
            txt.textContent = 'ความปลอดภัยปานกลาง';
            txt.style.color = 'var(--warning)';
        } else {
            bar.style.background = 'var(--success)';
            txt.textContent = 'ความปลอดภัยสูง!';
            txt.style.color = 'var(--success)';
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>