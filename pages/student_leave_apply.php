<?php
/**
 * Student Apply Leave Request Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$pageTitle = 'ยื่นคำร้องขอลาหยุด';

$db = Database::connect();
$studentId = (int)$_SESSION['student_id'];

// ดึงข้อมูลนักศึกษาล่าสุด
$sStmt = $db->prepare('SELECT * FROM students WHERE id = ?');
$sStmt->execute([$studentId]);
$student = $sStmt->fetch();

// ดึงข้อกำหนดการยื่นลากิจล่วงหน้าจากระบบ
$noticeDays = (int)getSystemSetting('personal_leave_notice_days', '3');
$maxFileSizeMb = (int)getSystemSetting('max_leave_file_size_mb', '5');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveType = trim($_POST['leave_type'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate   = trim($_POST['end_date'] ?? '');
    $reason    = trim($_POST['reason'] ?? '');
    $file      = $_FILES['attachment'] ?? null;

    // 1. ตรวจสอบข้อมูลทั่วไป
    if (!in_array($leaveType, ['sick', 'personal'])) {
        $error = 'กรุณาเลือกประเภทการลาที่ถูกต้อง (ลาป่วย หรือ ลากิจ)';
    } elseif (empty($startDate) || empty($endDate)) {
        $error = 'กรุณาระบุวันที่เริ่มลาและวันที่สิ้นสุดการลา';
    } elseif ($startDate > $endDate) {
        $error = 'วันที่เริ่มลาต้องไม่เกินวันที่สิ้นสุดการลา';
    } elseif (empty($reason)) {
        $error = 'กรุณาระบุเหตุผลในการลา';
    } else {
        $today = date('Y-m-d');
        
        // 2. ตรวจสอบเงื่อนไขการลากิจ (Personal Leave)
        if ($leaveType === 'personal') {
            $minNoticeDate = date('Y-m-d', strtotime("+{$noticeDays} days"));
            if ($startDate < $minNoticeDate) {
                $error = "ตามระเบียบสถานศึกษา การลากิจต้องยื่นคำร้องล่วงหน้าอย่างน้อย {$noticeDays} วัน (สามารถลาได้ตั้งแต่วันที่ " . date('d/m/Y', strtotime($minNoticeDate)) . " เป็นต้นไป)";
            }
        }

        // 3. ตรวจสอบเงื่อนไขการลาป่วย (Sick Leave) - บังคับแนบเอกสาร
        if (empty($error) && $leaveType === 'sick') {
            if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
                $error = 'การลาป่วยจำเป็นต้องแนบเอกสารรับรองการรักษาหรือใบรับรองแพทย์ทุกครั้ง';
            }
        }

        // 4. ตรวจสอบไฟล์แนบ (ถ้ามีส่งมา)
        $uploadedFileTarget = null;
        $originalFileName = '';
        $fileMimeType = '';
        $fileSizeBytes = 0;

        if (empty($error) && $file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (Error code: ' . $file['error'] . ')';
            } else {
                $originalFileName = basename($file['name']);
                $fileSizeBytes = $file['size'];
                $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

                if (!in_array($ext, $allowedExtensions)) {
                    $error = 'รองรับเฉพาะไฟล์เอกสารประเภท PDF, JPG, JPEG และ PNG เท่านั้น';
                } elseif ($fileSizeBytes > ($maxFileSizeMb * 1024 * 1024)) {
                    $error = "ขนาดไฟล์ต้องไม่เกิน {$maxFileSizeMb} MB";
                } else {
                    $newFileName = 'leave_' . $studentId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $uploadDir = __DIR__ . '/../uploads/leave_documents/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $destination = $uploadDir . $newFileName;
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $uploadedFileTarget = $newFileName;
                        $fileMimeType = mime_content_type($destination) ?: 'application/octet-stream';
                    } else {
                        $error = 'ไม่สามารถบันทึกไฟล์ลงในระบบได้ กรุณาลองใหม่อีกครั้ง';
                    }
                }
            }
        }

        // 5. บันทึกคำร้องลงฐานข้อมูล
        if (empty($error)) {
            try {
                $db->beginTransaction();

                // คำนวณจำนวนวัน
                $diff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
                $totalDays = max(1, (int)$diff);

                // สร้างหมายเลขคำร้อง Request No.
                $requestNo = 'LV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

                // ใน PostgreSQL ใช้ Single Quote 'pending' แทน "pending"
                $insStmt = $db->prepare("
                    INSERT INTO leave_requests 
                    (request_no, student_id, leave_type, start_date, end_date, total_days, reason, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $insStmt->execute([
                    $requestNo,
                    $studentId,
                    $leaveType,
                    $startDate,
                    $endDate,
                    $totalDays,
                    $reason
                ]);
                $leaveRequestId = (int)$db->lastInsertId();

                // บันทึกไฟล์แนบถ้ามี
                if ($uploadedFileTarget) {
                    $attStmt = $db->prepare('
                        INSERT INTO leave_attachments (leave_request_id, file_path, file_name, file_type, file_size)
                        VALUES (?, ?, ?, ?, ?)
                    ');
                    $attStmt->execute([
                        $leaveRequestId,
                        $uploadedFileTarget,
                        $originalFileName,
                        $fileMimeType,
                        $fileSizeBytes
                    ]);
                }

                $db->commit();
                $success = "ยื่นคำร้องขอลาหยุดสำเร็จ! หมายเลขคำร้องของคุณคือ <strong>{$requestNo}</strong> ท่านสามารถติดตามผลการอนุมัติได้ที่หน้าประวัติคำร้อง";
            } catch (Exception $e) {
                $db->rollBack();
                if ($uploadedFileTarget && file_exists(__DIR__ . '/../uploads/leave_documents/' . $uploadedFileTarget)) {
                    @unlink(__DIR__ . '/../uploads/leave_documents/' . $uploadedFileTarget);
                }
                $error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../includes/student_header.php';
?>

<div class="container py-4">
    <!-- ข้อมูลส่วนตัวนักเรียน -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="panel p-3" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--accent-bg); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold;">
                            <i data-lucide="user"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 text-white font-weight-bold">
                                <?= htmlspecialchars(($student['title'] ?? '') . ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')) ?>
                            </h5>
                            <div class="text-secondary small d-flex flex-wrap gap-3">
                                <span><i data-lucide="hash" style="width: 14px; height: 14px; display:inline;"></i> รหัสเต็ม: <strong><?= htmlspecialchars($student['student_id_full'] ?? '-') ?></strong></span>
                                <span><i data-lucide="key" style="width: 14px; height: 14px; display:inline;"></i> รหัสประจำตัว: <strong><?= htmlspecialchars($student['student_code'] ?? '-') ?></strong></span>
                                <span><i data-lucide="door-open" style="width: 14px; height: 14px; display:inline;"></i> ห้องเรียน: <strong><?= htmlspecialchars($student['room'] ?? '-') ?></strong></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="student_leave_history.php" class="btn btn-outline-light btn-sm d-flex align-items-center gap-2">
                            <i data-lucide="history" style="width: 16px; height: 16px;"></i>
                            <span>ดูประวัติการลา</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ฟอร์มยื่นคำร้องขอลา -->
    <div class="row justify-content-center">
        <div class="col-lg-9 col-md-11">
            <div class="panel p-4" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
                <div class="border-bottom border-secondary border-opacity-25 pb-3 mb-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="text-white mb-1 font-weight-bold">
                            <i data-lucide="clipboard-list" style="color: var(--accent); display: inline-block; vertical-align: middle; margin-right: 8px;"></i>
                            แบบฟอร์มยื่นคำร้องขอลาหยุด
                        </h4>
                        <p class="text-muted small mb-0">กรุณากรอกข้อมูลและเลือกประเภทการลาให้ถูกต้องตามระเบียบ</p>
                    </div>
                    <span class="badge" style="background: var(--accent-bg); color: var(--accent); padding: 8px 12px; border-radius: var(--radius-md);">
                        ระบบออนไลน์ 24 ชม.
                    </span>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" style="background: var(--danger-bg); border: 1px solid rgba(248,113,113,0.3); color: var(--danger); border-radius: var(--radius-md);">
                        <i data-lucide="alert-octagon" style="flex-shrink:0; margin-top: 3px;"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-start gap-2 mb-4" style="background: var(--success-bg); border: 1px solid rgba(52,211,153,0.3); color: var(--success); border-radius: var(--radius-md);">
                        <i data-lucide="check-circle" style="flex-shrink:0; margin-top: 3px;"></i>
                        <div>
                            <?= $success ?>
                            <div class="mt-2">
                                <a href="student_leave_history.php" class="btn btn-sm btn-success text-white">ไปที่หน้าประวัติคำร้อง &rarr;</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="student_leave_apply.php" enctype="multipart/form-data" id="leaveForm">
                    <!-- ประเภทการลา -->
                    <div class="mb-4">
                        <label class="form-label text-white fw-semibold mb-2">ประเภทการลา <span class="text-danger">*</span></label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="p-3 d-flex align-items-start gap-3 w-100 rounded-3 cursor-pointer" 
                                       style="border: 2px solid var(--border); background: var(--bg-elevated); cursor: pointer;" id="sickLabel">
                                    <input type="radio" name="leave_type" id="typeSick" value="sick" 
                                           class="form-check-input mt-1" <?= (!isset($_POST['leave_type']) || $_POST['leave_type'] === 'sick') ? 'checked' : '' ?> onchange="handleTypeChange()">
                                    <div>
                                        <div class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
                                            <i data-lucide="stethoscope" style="width: 18px; height: 18px; color: #f87171;"></i>
                                            ลาป่วย (Sick Leave)
                                        </div>
                                        <small class="text-muted d-block">ต้องแนบใบรับรองแพทย์หรือเอกสารรับรองการรักษาทุกครั้ง</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="p-3 d-flex align-items-start gap-3 w-100 rounded-3 cursor-pointer" 
                                       style="border: 2px solid var(--border); background: var(--bg-elevated); cursor: pointer;" id="personalLabel">
                                    <input type="radio" name="leave_type" id="typePersonal" value="personal" 
                                           class="form-check-input mt-1" <?= (isset($_POST['leave_type']) && $_POST['leave_type'] === 'personal') ? 'checked' : '' ?> onchange="handleTypeChange()">
                                    <div>
                                        <div class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
                                            <i data-lucide="briefcase" style="width: 18px; height: 18px; color: #fbbf24;"></i>
                                            ลากิจ (Personal Leave)
                                        </div>
                                        <small class="text-muted d-block">ต้องยื่นคำร้องล่วงหน้าอย่างน้อย <strong class="text-warning"><?= $noticeDays ?> วัน</strong> ตามระเบียบ</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ช่วงวันที่ลา -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label text-white fw-semibold">วันที่เริ่มลา <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required
                                   value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')) ?>"
                                   style="background: var(--bg-root); border: 1px solid var(--border); color: #fff; padding: 10px;" onchange="validateDates()">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label text-white fw-semibold">วันที่สิ้นสุดการลา <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required
                                   value="<?= htmlspecialchars($_POST['end_date'] ?? date('Y-m-d')) ?>"
                                   style="background: var(--bg-root); border: 1px solid var(--border); color: #fff; padding: 10px;" onchange="validateDates()">
                        </div>
                        <div class="col-12">
                            <div id="dateNotice" class="small p-2 rounded" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- เหตุผลการลา -->
                    <div class="mb-4">
                        <label for="reason" class="form-label text-white fw-semibold">เหตุผลการขอลาหยุด <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required
                                  placeholder="กรุณาระบุเหตุผลการลาอย่างชัดเจน เช่น เป็นไข้หวัดใหญ่พักรักษาตัวตามแพทย์สั่ง หรือ ติดธุระสำคัญกับครอบครัวต่างจังหวัด..."
                                  style="background: var(--bg-root); border: 1px solid var(--border); color: #fff; padding: 12px;"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                    </div>

                    <!-- แนบเอกสารประกอบการลา -->
                    <div class="mb-4" id="attachmentGroup">
                        <label for="attachment" class="form-label text-white fw-semibold d-flex justify-content-between">
                            <span>
                                แนบเอกสารประกอบ (ใบรับรองแพทย์ / เอกสารรับรอง) 
                                <span id="attachRequiredBadge" class="text-danger">*</span>
                            </span>
                            <span class="text-muted small">รองรับ PDF, JPG, PNG (สูงสุด <?= $maxFileSizeMb ?>MB)</span>
                        </label>
                        <div class="p-3 rounded-3" style="background: var(--bg-root); border: 1px dashed var(--border-strong);">
                            <input type="file" class="form-control" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                                   style="background: transparent; border: none; color: var(--text-secondary);">
                            <div class="text-muted small mt-2">
                                <i data-lucide="shield-check" style="width: 14px; height: 14px; display:inline; color: var(--success);"></i>
                                เอกสารถูกจัดเก็บในระบบที่ปลอดภัย เฉพาะอาจารย์ที่ปรึกษาและเจ้าหน้าที่ผู้เกี่ยวข้องเท่านั้นที่สามารถเปิดดูได้
                            </div>
                        </div>
                    </div>

                    <!-- ปุ่มส่งคำร้อง -->
                    <div class="d-flex justify-content-end gap-3 pt-3 border-top border-secondary border-opacity-25">
                        <a href="student_leave_history.php" class="btn btn-outline-secondary px-4">ยกเลิก</a>
                        <button type="submit" id="submitBtn" class="btn-primary-custom px-4 py-2">
                            <i data-lucide="send" class="icon"></i>
                            <span>ส่งคำร้องขอลา</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const noticeDays = <?= $noticeDays ?>;

    function handleTypeChange() {
        const isSick = document.getElementById('typeSick').checked;
        const attachInput = document.getElementById('attachment');
        const attachBadge = document.getElementById('attachRequiredBadge');
        const sickLabel = document.getElementById('sickLabel');
        const personalLabel = document.getElementById('personalLabel');

        if (isSick) {
            sickLabel.style.borderColor = 'var(--accent)';
            personalLabel.style.borderColor = 'var(--border)';
            attachInput.required = true;
            attachBadge.style.display = 'inline';
        } else {
            sickLabel.style.borderColor = 'var(--border)';
            personalLabel.style.borderColor = 'var(--accent)';
            attachInput.required = false;
            attachBadge.style.display = 'none';
        }
        validateDates();
    }

    function validateDates() {
        const isPersonal = document.getElementById('typePersonal').checked;
        const startDateVal = document.getElementById('start_date').value;
        const noticeDiv = document.getElementById('dateNotice');
        const submitBtn = document.getElementById('submitBtn');

        if (!startDateVal) return;

        const today = new Date();
        today.setHours(0,0,0,0);

        const startDate = new Date(startDateVal);
        startDate.setHours(0,0,0,0);

        const diffTime = startDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (isPersonal) {
            if (diffDays < noticeDays) {
                noticeDiv.style.display = 'block';
                noticeDiv.style.background = 'var(--danger-bg)';
                noticeDiv.style.color = 'var(--danger)';
                noticeDiv.innerHTML = `<i data-lucide="alert-triangle" style="width: 16px; height: 16px; display:inline;"></i> <strong>แจ้งเตือน:</strong> การลากิจต้องยื่นล่วงหน้าอย่างน้อย ${noticeDays} วัน (ยื่นล่วงหน้าได้ตั้งแต่วันที่ ${formatDateAdd(today, noticeDays)} เป็นต้นไป)`;
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                return;
            } else {
                noticeDiv.style.display = 'block';
                noticeDiv.style.background = 'var(--success-bg)';
                noticeDiv.style.color = 'var(--success)';
                noticeDiv.innerHTML = `<i data-lucide="check-circle" style="width: 16px; height: 16px; display:inline;"></i> ถูกต้องตามระเบียบ: วันที่เริ่มลายื่นล่วงหน้าตามเกณฑ์ที่กำหนด (${diffDays} วัน)`;
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                return;
            }
        }

        noticeDiv.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
    }

    function formatDateAdd(baseDate, days) {
        const d = new Date(baseDate);
        d.setDate(d.getDate() + days);
        return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
    }

    handleTypeChange();
</script>

<?php require_once __DIR__ . '/../includes/student_footer.php'; ?>
