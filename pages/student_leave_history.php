<?php
/**
 * Student Leave History Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$pageTitle = 'ประวัติคำร้องขอลาหยุด';

$db = Database::connect();
$studentId = (int)$_SESSION['student_id'];

// ดึงประวัติคำร้องของนักเรียนคนนี้
$query = '
    SELECT r.*, 
           a.id AS attachment_id, a.file_name, a.file_size,
           ap.action AS approval_action, ap.comment AS approval_comment, ap.action_at,
           u.full_name AS approver_name
    FROM leave_requests r
    LEFT JOIN leave_attachments a ON r.id = a.leave_request_id
    LEFT JOIN leave_approvals ap ON r.id = ap.leave_request_id
    LEFT JOIN users u ON ap.action_by = u.id
    WHERE r.student_id = ?
    ORDER BY r.id DESC
';
$stmt = $db->prepare($query);
$stmt->execute([$studentId]);
$requests = $stmt->fetchAll();

require_once __DIR__ . '/../includes/student_header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="text-white font-weight-bold mb-1">
                <i data-lucide="history" style="color: var(--accent); vertical-align: middle;"></i>
                ประวัติคำร้องขอลาหยุดของฉัน
            </h4>
            <p class="text-secondary small mb-0">ติดตามสถานะและตรวจสอบผลการพิจารณาคำร้องขอลาหยุด</p>
        </div>
        <a href="student_leave_apply.php" class="btn-primary-custom d-inline-flex align-items-center gap-2">
            <i data-lucide="file-plus-2" style="width: 18px; height: 18px;"></i>
            <span>ยื่นคำร้องใหม่</span>
        </a>
    </div>

    <!-- สรุปสถานะ -->
    <?php
    $cntPending = 0;
    $cntApproved = 0;
    $cntRejected = 0;
    foreach ($requests as $r) {
        if ($r['status'] === 'pending') $cntPending++;
        elseif ($r['status'] === 'approved') $cntApproved++;
        elseif ($r['status'] === 'rejected') $cntRejected++;
    }
    ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="panel p-3" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small">รออนุมัติ</span>
                        <h3 class="mb-0 text-warning font-weight-bold mt-1"><?= $cntPending ?></h3>
                    </div>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--warning-bg); display: flex; align-items: center; justify-content: center; color: var(--warning);">
                        <i data-lucide="clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="panel p-3" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small">อนุมัติแล้ว</span>
                        <h3 class="mb-0 text-success font-weight-bold mt-1"><?= $cntApproved ?></h3>
                    </div>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--success-bg); display: flex; align-items: center; justify-content: center; color: var(--success);">
                        <i data-lucide="check-circle-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="panel p-3" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small">ไม่อนุมัติ</span>
                        <h3 class="mb-0 text-danger font-weight-bold mt-1"><?= $cntRejected ?></h3>
                    </div>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--danger-bg); display: flex; align-items: center; justify-content: center; color: var(--danger);">
                        <i data-lucide="x-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ตารางรายการประวัติ -->
    <div class="panel" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0" style="background: transparent;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">
                        <th class="py-3 px-4">เลขที่คำร้อง</th>
                        <th class="py-3 px-3">ประเภทการลา</th>
                        <th class="py-3 px-3">ช่วงวันที่ลา</th>
                        <th class="py-3 px-3">จำนวน</th>
                        <th class="py-3 px-3">เอกสารแนบ</th>
                        <th class="py-3 px-3 text-center">สถานะ</th>
                        <th class="py-3 px-4 text-end">รายละเอียด</th>
                    </tr>
                </thead>
                <tbody style="border-top: none;">
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i data-lucide="inbox" style="width: 40px; height: 40px; opacity: 0.4; margin-bottom: 8px;"></i>
                                <div>ยังไม่มีประวัติการยื่นคำร้องขอลา</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); vertical-align: middle;">
                                <td class="py-3 px-4">
                                    <span class="fw-bold text-white"><?= htmlspecialchars($r['request_no']) ?></span>
                                    <div class="text-muted small"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></div>
                                </td>
                                <td class="py-3 px-3">
                                    <?php if ($r['leave_type'] === 'sick'): ?>
                                        <span class="badge" style="background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3);">
                                            <i data-lucide="stethoscope" style="width: 12px; height: 12px; display:inline;"></i> ลาป่วย
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3);">
                                            <i data-lucide="briefcase" style="width: 12px; height: 12px; display:inline;"></i> ลากิจ
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="text-white">
                                        <?= date('d/m/Y', strtotime($r['start_date'])) ?> — <?= date('d/m/Y', strtotime($r['end_date'])) ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="badge bg-secondary"><?= $r['total_days'] ?> วัน</span>
                                </td>
                                <td class="py-3 px-3">
                                    <?php if (!empty($r['attachment_id'])): ?>
                                        <a href="../api/leave_document.php?id=<?= $r['attachment_id'] ?>" target="_blank" class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1" title="เปิดดูเอกสาร">
                                            <i data-lucide="file-text" style="width: 14px; height: 14px;"></i>
                                            <span style="font-size: 0.8rem;">เปิดดู</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <span class="status-badge pending">
                                            <i data-lucide="clock" style="width: 13px; height: 13px;"></i> รออนุมัติ
                                        </span>
                                    <?php elseif ($r['status'] === 'approved'): ?>
                                        <span class="status-badge approved">
                                            <i data-lucide="check-circle" style="width: 13px; height: 13px;"></i> อนุมัติแล้ว
                                        </span>
                                    <?php elseif ($r['status'] === 'rejected'): ?>
                                        <span class="status-badge rejected">
                                            <i data-lucide="x-circle" style="width: 13px; height: 13px;"></i> ไม่อนุมัติ
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-end">
                                    <button class="btn btn-sm btn-outline-secondary" onclick='viewDetail(<?= json_encode($r) ?>)'>
                                        <i data-lucide="eye" style="width: 15px; height: 15px;"></i>
                                        รายละเอียด
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal รายละเอียดคำร้อง -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--text-primary); border-radius: var(--radius-lg);">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title font-weight-bold" id="mRequestNo">รายละเอียดคำร้อง</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="text-muted small d-block">ประเภทและระยะเวลา</span>
                    <div id="mTypeAndDates" class="fw-bold text-white mt-1"></div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">เหตุผลการขอลา</span>
                    <div id="mReason" class="p-3 rounded mt-1" style="background: var(--bg-root); border: 1px solid var(--border);"></div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">เอกสารแนบ</span>
                    <div id="mAttachment" class="mt-1"></div>
                </div>
                <div class="mb-3" id="approvalSection" style="display: none;">
                    <span class="text-muted small d-block">ผลการพิจารณาจากอาจารย์/ผู้ดูแล</span>
                    <div class="p-3 rounded mt-1" id="mApprovalBox" style="border: 1px solid var(--border);">
                        <div id="mApproverInfo" class="small mb-1 font-weight-bold"></div>
                        <div id="mApproverComment" class="small"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-25">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewDetail(item) {
        document.getElementById('mRequestNo').innerText = 'คำร้อง: ' + item.request_no;
        const typeStr = item.leave_type === 'sick' ? 'ลาป่วย (Sick Leave)' : 'ลากิจ (Personal Leave)';
        document.getElementById('mTypeAndDates').innerText = `${typeStr} | ${item.start_date} ถึง ${item.end_date} (${item.total_days} วัน)`;
        document.getElementById('mReason').innerText = item.reason;

        const attDiv = document.getElementById('mAttachment');
        if (item.attachment_id) {
            attDiv.innerHTML = `<a href="../api/leave_document.php?id=${item.attachment_id}" target="_blank" class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1">
                <i data-lucide="file-text" style="width: 14px; height: 14px;"></i>
                เปิดดูไฟล์: ${item.file_name}
            </a>`;
        } else {
            attDiv.innerHTML = '<span class="text-muted small">ไม่มีเอกสารแนบ</span>';
        }

        const appSec = document.getElementById('approvalSection');
        if (item.status !== 'pending' && item.approver_name) {
            appSec.style.display = 'block';
            const appBox = document.getElementById('mApprovalBox');
            if (item.status === 'approved') {
                appBox.style.background = 'var(--success-bg)';
                appBox.style.borderColor = 'rgba(52,211,153,0.3)';
                appBox.style.color = 'var(--success)';
            } else {
                appBox.style.background = 'var(--danger-bg)';
                appBox.style.borderColor = 'rgba(248,113,113,0.3)';
                appBox.style.color = 'var(--danger)';
            }
            document.getElementById('mApproverInfo').innerText = `พิจารณาโดย: ${item.approver_name} (${item.action_at || '-'})`;
            document.getElementById('mApproverComment').innerText = item.approval_comment ? `ความเห็น: ${item.approval_comment}` : 'ไม่มีความเห็นเพิ่มเติม';
        } else {
            appSec.style.display = 'none';
        }

        const myModal = new bootstrap.Modal(document.getElementById('detailModal'));
        myModal.show();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
</script>

<?php require_once __DIR__ . '/../includes/student_footer.php'; ?>
