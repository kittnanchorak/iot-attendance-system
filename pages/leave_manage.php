<?php
/**
 * Teacher/Admin Leave Management Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireStaff();

$pageTitle = 'จัดการคำร้องขอลาหยุด';

$db = Database::connect();

// ดึงรายการห้องเรียนทั้งหมดสำหรับตัวกรอง
$roomsStmt = $db->query('SELECT DISTINCT room FROM students ORDER BY room ASC');
$rooms = $roomsStmt->fetchAll(PDO::FETCH_COLUMN);

// ดึงค่าการตั้งค่าวันล่วงหน้า
$noticeDays = (int)getSystemSetting('personal_leave_notice_days', '3');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content fade-in">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="page-title">
                <i data-lucide="clipboard-check" style="color: var(--accent); display:inline-block; vertical-align:middle; margin-right:8px;"></i>
                ตรวจสอบและอนุมัติการลา
            </h1>
            <p class="page-subtitle">พิจารณาคำร้องขอลาป่วยและลากิจของนักเรียน พร้อมตรวจสอบเอกสารประกอบ</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="leave_stats.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i data-lucide="bar-chart-3" style="width: 16px; height: 16px;"></i>
                <span>สถิติการลา</span>
            </a>
            <?php if (isAdmin()): ?>
                <a href="leave_settings.php" class="btn btn-outline-warning d-flex align-items-center gap-2">
                    <i data-lucide="settings" style="width: 16px; height: 16px;"></i>
                    <span>ตั้งค่าระเบียบการลา</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- สรุปตัวเลขคำร้องภาพรวม -->
    <div class="row g-3 mb-4" id="leaveStatCards">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card" style="cursor: pointer;" onclick="filterByStatus('pending')">
                <div class="stat-header">
                    <span class="stat-label">รอการพิจารณา</span>
                    <div class="stat-icon-wrapper" style="background: var(--warning-bg); color: var(--warning);">
                        <i data-lucide="clock"></i>
                    </div>
                </div>
                <div class="stat-value" id="cardPendingCount" style="color: var(--warning);">-</div>
                <div class="stat-change text-muted small">คำร้องที่ต้องการการตรวจสอบ</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card" style="cursor: pointer;" onclick="filterByStatus('approved')">
                <div class="stat-header">
                    <span class="stat-label">อนุมัติแล้ว</span>
                    <div class="stat-icon-wrapper" style="background: var(--success-bg); color: var(--success);">
                        <i data-lucide="check-circle-2"></i>
                    </div>
                </div>
                <div class="stat-value" id="cardApprovedCount" style="color: var(--success);">-</div>
                <div class="stat-change text-muted small">คำร้องที่ผ่านการอนุมัติ</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card" style="cursor: pointer;" onclick="filterByStatus('rejected')">
                <div class="stat-header">
                    <span class="stat-label">ไม่อนุมัติ</span>
                    <div class="stat-icon-wrapper" style="background: var(--danger-bg); color: var(--danger);">
                        <i data-lucide="x-circle"></i>
                    </div>
                </div>
                <div class="stat-value" id="cardRejectedCount" style="color: var(--danger);">-</div>
                <div class="stat-change text-muted small">คำร้องที่ถูกปฏิเสธ</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card" style="cursor: pointer;" onclick="filterByStatus('')">
                <div class="stat-header">
                    <span class="stat-label">คำร้องทั้งหมด</span>
                    <div class="stat-icon-wrapper" style="background: var(--accent-bg); color: var(--accent);">
                        <i data-lucide="layers"></i>
                    </div>
                </div>
                <div class="stat-value" id="cardTotalCount">-</div>
                <div class="stat-change text-muted small">รวมทุกสถานะ</div>
            </div>
        </div>
    </div>

    <!-- ฟิลเตอร์ค้นหา -->
    <div class="panel mb-4 p-3" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="search-input-wrapper">
                    <i data-lucide="search" class="search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="ค้นหาชื่อ, รหัสนักเรียน..." oninput="debounceSearch()">
                </div>
            </div>
            <div class="col-md-2">
                <select id="statusFilter" class="form-select bg-dark text-white border-secondary" onchange="fetchLeaveRequests()">
                    <option value="">ทุกสถานะ</option>
                    <option value="pending" selected>รออนุมัติ (Pending)</option>
                    <option value="approved">อนุมัติแล้ว (Approved)</option>
                    <option value="rejected">ไม่อนุมัติ (Rejected)</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="typeFilter" class="form-select bg-dark text-white border-secondary" onchange="fetchLeaveRequests()">
                    <option value="">ทุกประเภทการลา</option>
                    <option value="sick">ลาป่วย (Sick)</option>
                    <option value="personal">ลากิจ (Personal)</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="roomFilter" class="form-select bg-dark text-white border-secondary" onchange="fetchLeaveRequests()">
                    <option value="">ทุกห้องเรียน</option>
                    <?php foreach ($rooms as $rm): ?>
                        <option value="<?= htmlspecialchars($rm) ?>"><?= htmlspecialchars($rm) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-outline-secondary btn-sm" onclick="fetchLeaveRequests()">
                    <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> รีเฟรชข้อมูล
                </button>
            </div>
        </div>
    </div>

    <!-- ตารางคำร้อง -->
    <div class="panel" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0" id="leaveTable" style="background: transparent;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.85rem;">
                        <th class="py-3 px-3">เลขที่คำร้อง</th>
                        <th class="py-3 px-3">นักศึกษา</th>
                        <th class="py-3 px-3">ห้อง</th>
                        <th class="py-3 px-3">ประเภท</th>
                        <th class="py-3 px-3">ระยะเวลาที่ลา</th>
                        <th class="py-3 px-3">เอกสารแนบ</th>
                        <th class="py-3 px-3 text-center">สถานะ</th>
                        <th class="py-3 px-3 text-end">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody id="leaveTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">กำลังโหลดข้อมูล...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal พิจารณาและดูรายละเอียดคำร้อง -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--text-primary); border-radius: var(--radius-lg);">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title font-weight-bold" id="mTitle">พิจารณาคำร้องขอลาหยุด</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalRequestId">
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded" style="background: var(--bg-root); border: 1px solid var(--border);">
                            <span class="text-muted small d-block mb-1">ข้อมูลนักศึกษา</span>
                            <h5 id="mStudentName" class="text-white mb-1 font-weight-bold"></h5>
                            <div id="mStudentDetails" class="text-secondary small"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded" style="background: var(--bg-root); border: 1px solid var(--border);">
                            <span class="text-muted small d-block mb-1">รายละเอียดการลา</span>
                            <div id="mLeaveTypeBadge" class="mb-1"></div>
                            <div id="mDateRange" class="text-white fw-semibold small"></div>
                            <div id="mTotalDays" class="text-muted small"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small mb-1">เหตุผลการขอลา</label>
                    <div id="mReasonText" class="p-3 rounded text-white" style="background: var(--bg-root); border: 1px solid var(--border); font-size: 0.95rem;"></div>
                </div>

                <!-- เอกสารแนบ -->
                <div class="mb-4">
                    <label class="form-label text-muted small mb-1">เอกสารประกอบการลา (ใบรับรองแพทย์/หลักฐาน)</label>
                    <div id="mAttachmentBox" class="p-3 rounded d-flex align-items-center justify-content-between" style="background: var(--bg-root); border: 1px solid var(--border);">
                        <!-- Filled by JS -->
                    </div>
                </div>

                <hr class="border-secondary border-opacity-25 my-4">

                <!-- ฟอร์มพิจารณาผล -->
                <div id="actionFormSection">
                    <h6 class="text-white font-weight-bold mb-3 d-flex align-items-center gap-2">
                        <i data-lucide="user-check" style="width: 18px; height: 18px; color: var(--accent);"></i>
                        บันทึกผลการพิจารณาคำร้อง
                    </h6>

                    <div class="mb-3">
                        <label for="approvalComment" class="form-label text-muted small">ความเห็น / หมายเหตุ (ระบุเหตุผลกรณีไม่อนุมัติ หรือคำแนะนำเพิ่มเติม)</label>
                        <textarea id="approvalComment" class="form-control bg-dark text-white border-secondary" rows="2" placeholder="ระบุเหตุผลในการไม่อนุมัติ หรือหมายเหตุถึงนักเรียน..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn btn-outline-danger d-flex align-items-center gap-2 px-3" onclick="submitDecision('rejected')">
                            <i data-lucide="x-circle" style="width: 16px; height: 16px;"></i>
                            <span>ไม่อนุมัติคำร้อง</span>
                        </button>
                        <button type="button" class="btn btn-success d-flex align-items-center gap-2 px-4" onclick="submitDecision('approved')">
                            <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                            <span>อนุมัติคำร้อง</span>
                        </button>
                    </div>
                </div>

                <!-- ประวัติการพิจารณาที่เคยทำไปแล้ว -->
                <div id="existingApprovalSection" style="display: none;">
                    <div class="p-3 rounded" id="existingApprovalBox">
                        <div class="fw-bold mb-1" id="existingApproverTitle"></div>
                        <div class="small" id="existingApproverComment"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-25">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script>
    let searchTimeout = null;
    let actionModalInstance = null;
    let allRequests = [];

    document.addEventListener('DOMContentLoaded', () => {
        actionModalInstance = new bootstrap.Modal(document.getElementById('actionModal'));
        fetchLeaveRequests();
    });

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchLeaveRequests();
        }, 300);
    }

    function filterByStatus(st) {
        document.getElementById('statusFilter').value = st;
        fetchLeaveRequests();
    }

    async function fetchLeaveRequests() {
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const type   = document.getElementById('typeFilter').value;
        const room   = document.getElementById('roomFilter').value;

        const params = new URLSearchParams({ search, status, type, room });

        try {
            const res = await fetch(`../api/leave_requests_api.php?action=list&${params.toString()}`);
            const data = await res.json();

            if (data.success) {
                allRequests = data.data;
                renderStats(data.stats);
                renderTable(data.data);
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        } catch (e) {
            console.error(e);
        }
    }

    function renderStats(stats) {
        document.getElementById('cardPendingCount').innerText = stats.pending || 0;
        document.getElementById('cardApprovedCount').innerText = stats.approved || 0;
        document.getElementById('cardRejectedCount').innerText = stats.rejected || 0;
        document.getElementById('cardTotalCount').innerText = stats.total || 0;
    }

    function renderTable(list) {
        const tbody = document.getElementById('leaveTableBody');
        if (!list || list.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">ไม่พบข้อมูลคำร้องขอลาตามเงื่อนไขที่เลือก</td></tr>`;
            return;
        }

        let html = '';
        list.forEach(item => {
            const isSick = item.leave_type === 'sick';
            const typeBadge = isSick
                ? `<span class="badge" style="background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3);">ลาป่วย</span>`
                : `<span class="badge" style="background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3);">ลากิจ</span>`;

            let statusBadge = '';
            if (item.status === 'pending') {
                statusBadge = `<span class="badge bg-warning text-dark"><i data-lucide="clock" style="width:12px;height:12px;display:inline;"></i> รออนุมัติ</span>`;
            } else if (item.status === 'approved') {
                statusBadge = `<span class="badge bg-success"><i data-lucide="check" style="width:12px;height:12px;display:inline;"></i> อนุมัติแล้ว</span>`;
            } else if (item.status === 'rejected') {
                statusBadge = `<span class="badge bg-danger"><i data-lucide="x" style="width:12px;height:12px;display:inline;"></i> ไม่อนุมัติ</span>`;
            }

            const attachCol = item.attachment_id
                ? `<a href="../api/leave_document.php?id=${item.attachment_id}" target="_blank" class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1" title="เปิดดูเอกสาร">
                    <i data-lucide="paperclip" style="width: 14px; height: 14px;"></i>
                    <span style="font-size: 0.8rem;">เปิดดู</span>
                   </a>`
                : `<span class="text-muted small">-</span>`;

            html += `
                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); vertical-align: middle;">
                    <td class="py-3 px-3">
                        <div class="fw-bold text-white">${item.request_no}</div>
                        <div class="text-muted text-xs">${item.created_at_fmt}</div>
                    </td>
                    <td class="py-3 px-3">
                        <div class="text-white fw-semibold">${item.student_name}</div>
                        <div class="text-muted text-xs">รหัส: ${item.student_code}</div>
                    </td>
                    <td class="py-3 px-3">
                        <span class="badge bg-secondary">${item.room}</span>
                    </td>
                    <td class="py-3 px-3">${typeBadge}</td>
                    <td class="py-3 px-3">
                        <div class="text-white small">${item.start_date_fmt} - ${item.end_date_fmt}</div>
                        <div class="text-muted text-xs">รวม ${item.total_days} วัน</div>
                    </td>
                    <td class="py-3 px-3">${attachCol}</td>
                    <td class="py-3 px-3 text-center">${statusBadge}</td>
                    <td class="py-3 px-3 text-end">
                        <button class="btn btn-sm btn-primary-custom d-inline-flex align-items-center gap-1" onclick="openActionModal(${item.id})">
                            <i data-lucide="${item.status === 'pending' ? 'edit-3' : 'eye'}" style="width: 14px; height: 14px;"></i>
                            <span>${item.status === 'pending' ? 'พิจารณา' : 'ดูรายละเอียด'}</span>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openActionModal(id) {
        const item = allRequests.find(r => r.id == id);
        if (!item) return;

        document.getElementById('modalRequestId').value = item.id;
        document.getElementById('mTitle').innerText = 'พิจารณาคำร้อง: ' + item.request_no;
        document.getElementById('mStudentName').innerText = item.student_name;
        document.getElementById('mStudentDetails').innerText = `รหัสประจำตัว: ${item.student_code} | รหัสเต็ม: ${item.student_id_full} | ห้อง: ${item.room}`;

        const isSick = item.leave_type === 'sick';
        document.getElementById('mLeaveTypeBadge').innerHTML = isSick
            ? `<span class="badge" style="background: rgba(248, 113, 113, 0.2); color: #f87171;">ลาป่วย (Sick Leave)</span>`
            : `<span class="badge" style="background: rgba(251, 191, 36, 0.2); color: #fbbf24;">ลากิจ (Personal Leave)</span>`;

        document.getElementById('mDateRange').innerText = `${item.start_date_fmt} ถึง ${item.end_date_fmt}`;
        document.getElementById('mTotalDays').innerText = `ระยะเวลารวม: ${item.total_days} วัน (ยื่นคำร้องเมื่อ: ${item.created_at_fmt})`;
        document.getElementById('mReasonText').innerText = item.reason;

        const attBox = document.getElementById('mAttachmentBox');
        if (item.attachment_id) {
            attBox.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="file-text" style="color: var(--accent); width: 22px; height: 22px;"></i>
                    <div>
                        <div class="text-white small fw-bold">${item.file_name}</div>
                        <div class="text-muted text-xs">ขนาด: ${(item.file_size / 1024).toFixed(1)} KB</div>
                    </div>
                </div>
                <a href="../api/leave_document.php?id=${item.attachment_id}" target="_blank" class="btn btn-sm btn-info text-white d-flex align-items-center gap-1">
                    <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                    เปิดดูเอกสาร
                </a>
            `;
        } else {
            attBox.innerHTML = `<span class="text-muted small">ไม่มีเอกสารแนบ ${isSick ? '<span class="text-danger">(ผิดปกติ: ลาป่วยควรมีเอกสารแนบ)</span>' : ''}</span>`;
        }

        const actionSection = document.getElementById('actionFormSection');
        const existingSection = document.getElementById('existingApprovalSection');
        document.getElementById('approvalComment').value = '';

        if (item.status === 'pending') {
            actionSection.style.display = 'block';
            existingSection.style.display = 'none';
        } else {
            actionSection.style.display = 'none';
            existingSection.style.display = 'block';

            const appBox = document.getElementById('existingApprovalBox');
            if (item.status === 'approved') {
                appBox.style.background = 'var(--success-bg)';
                appBox.style.border = '1px solid rgba(52,211,153,0.3)';
                appBox.style.color = 'var(--success)';
            } else {
                appBox.style.background = 'var(--danger-bg)';
                appBox.style.border = '1px solid rgba(248,113,113,0.3)';
                appBox.style.color = 'var(--danger)';
            }
            document.getElementById('existingApproverTitle').innerText = `ผล: ${item.status === 'approved' ? 'อนุมัติแล้ว' : 'ไม่อนุมัติ'} โดย ${item.approver_name || '-'} (${item.action_at || '-'})`;
            document.getElementById('existingApproverComment').innerText = item.approval_comment ? `ความเห็น: ${item.approval_comment}` : 'ไม่มีความเห็นเพิ่มเติม';
        }

        actionModalInstance.show();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    async function submitDecision(decision) {
        const id = document.getElementById('modalRequestId').value;
        const comment = document.getElementById('approvalComment').value.trim();

        if (decision === 'rejected' && !comment) {
            alert('กรุณาระบุเหตุผลกรณีไม่อนุมัติคำร้อง เพื่อให้นักเรียนทราบสาเหตุ');
            return;
        }

        const confirmText = decision === 'approved' 
            ? 'ยืนยันการอนุมัติคำร้องขอลาหยุดนี้?' 
            : 'ยืนยันการไม่อนุมัติคำร้องขอลาหยุดนี้?';

        if (!confirm(confirmText)) return;

        try {
            const res = await fetch('../api/leave_requests_api.php?action=decide', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    leave_request_id: id,
                    action: decision,
                    comment: comment
                })
            });
            const data = await res.json();

            if (data.success) {
                actionModalInstance.hide();
                fetchLeaveRequests();
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
