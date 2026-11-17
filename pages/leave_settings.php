<?php
/**
 * Leave System Settings Page (Admin Only)
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'ตั้งค่าระเบียบการลา';

$db = Database::connect();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noticeDays = max(1, intval($_POST['personal_leave_notice_days'] ?? 3));
    $maxFileSize = max(1, intval($_POST['max_leave_file_size_mb'] ?? 5));
    $allowedTypes = trim($_POST['allowed_file_types'] ?? 'pdf,jpg,jpeg,png');

    try {
        $stmt = $db->prepare('UPDATE system_settings SET setting_value = ? WHERE setting_key = ?');
        
        $stmt->execute([$noticeDays, 'personal_leave_notice_days']);
        $stmt->execute([$maxFileSize, 'max_leave_file_size_mb']);
        $stmt->execute([$allowedTypes, 'allowed_file_types']);

        $success = 'บันทึกการตั้งค่าระเบียบการลาเรียบร้อยแล้ว';
    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
    }
}

// โหลดค่าปัจจุบัน
$curNoticeDays = getSystemSetting('personal_leave_notice_days', '3');
$curMaxFileSize = getSystemSetting('max_leave_file_size_mb', '5');
$curAllowedTypes = getSystemSetting('allowed_file_types', 'pdf,jpg,jpeg,png');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content fade-in">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="page-title">
                <i data-lucide="settings" style="color: var(--accent); vertical-align: middle; margin-right: 8px;"></i>
                ตั้งค่าระเบียบการลาหยุด (Admin)
            </h1>
            <p class="page-subtitle">กำหนดเงื่อนไขการยื่นลากิจล่วงหน้า และข้อกำหนดไฟล์เอกสารประกอบการลา</p>
        </div>
        <div>
            <a href="leave_manage.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                <span>กลับหน้ารายการคำร้อง</span>
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="panel p-4" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="background: var(--success-bg); border: 1px solid rgba(52,211,153,0.3); color: var(--success); border-radius: var(--radius-md);">
                        <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="background: var(--danger-bg); border: 1px solid rgba(248,113,113,0.3); color: var(--danger); border-radius: var(--radius-md);">
                        <i data-lucide="alert-octagon" style="width: 18px; height: 18px;"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="leave_settings.php">
                    <!-- ลากิจล่วงหน้า -->
                    <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                        <label for="personal_leave_notice_days" class="form-label text-white fw-bold mb-1">
                            จำนวนวันล่วงหน้าขั้นต่ำในการยื่นขอลากิจ (Personal Leave Notice)
                        </label>
                        <p class="text-muted small mb-2">
                            ระบบจะตรวจสอบวันที่นักเรียนเริ่มลากับวันที่ปัจจุบัน หากยื่นล่วงหน้าน้อยกว่าวันที่กำหนด ระบบจะแจ้งเตือนและไม่อนุญาตให้ส่งคำร้อง
                        </p>
                        <div class="input-group" style="max-width: 250px;">
                            <input type="number" class="form-control bg-dark text-white border-secondary" 
                                   id="personal_leave_notice_days" name="personal_leave_notice_days" 
                                   min="1" max="30" value="<?= htmlspecialchars($curNoticeDays) ?>" required>
                            <span class="input-group-text bg-secondary border-secondary text-white">วัน</span>
                        </div>
                        <div class="text-secondary small mt-2">
                            (เช่น ระเบียบ 3 วัน หรือ 7 วัน)
                        </div>
                    </div>

                    <!-- ขนาดไฟล์อัปโหลด -->
                    <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                        <label for="max_leave_file_size_mb" class="form-label text-white fw-bold mb-1">
                            ขนาดไฟล์เอกสารแนบสูงสุด (Max File Size)
                        </label>
                        <p class="text-muted small mb-2">
                            จำกัดขนาดไฟล์เอกสาร เช่น ใบรับรองแพทย์ หรือหนังสือรับรอง
                        </p>
                        <div class="input-group" style="max-width: 250px;">
                            <input type="number" class="form-control bg-dark text-white border-secondary" 
                                   id="max_leave_file_size_mb" name="max_leave_file_size_mb" 
                                   min="1" max="25" value="<?= htmlspecialchars($curMaxFileSize) ?>" required>
                            <span class="input-group-text bg-secondary border-secondary text-white">MB</span>
                        </div>
                    </div>

                    <!-- ประเภทไฟล์ที่อนุญาต -->
                    <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                        <label for="allowed_file_types" class="form-label text-white fw-bold mb-1">
                            ประเภทไฟล์ที่อนุญาต (Allowed Extensions)
                        </label>
                        <p class="text-muted small mb-2">
                            ระบุนามสกุลไฟล์ที่อนุญาต คั่นด้วยเครื่องหมายจุลภาค (,)
                        </p>
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               id="allowed_file_types" name="allowed_file_types" 
                               value="<?= htmlspecialchars($curAllowedTypes) ?>" required>
                        <div class="text-muted text-xs mt-1">ค่ามาตรฐาน: pdf,jpg,jpeg,png</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn-primary-custom px-4 py-2">
                            <i data-lucide="save" class="icon"></i>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
