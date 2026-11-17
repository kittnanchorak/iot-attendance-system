<?php
/**
 * Students Management Page (Admin Only)
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'จัดการข้อมูลนักศึกษา';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = Database::connect();
$stmt = $db->query("SELECT * FROM students ORDER BY room ASC, student_code ASC");
$students = $stmt->fetchAll();
?>

<main class="main-content fade-in">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="page-title">จัดการข้อมูลนักศึกษา</h1>
            <p class="page-subtitle">เพิ่ม ลบ แก้ไข ข้อมูลนักศึกษาในระบบ (เฉพาะผู้ดูแลระบบ)</p>
        </div>
        <button class="btn-primary-custom" onclick="openAddModal()">
            <i data-lucide="user-plus" class="icon"></i> เพิ่มนักศึกษา
        </button>
    </div>

    <div class="panel">
        <div class="panel-body no-padding" style="overflow-x: auto;">
            <table class="data-table" id="studentTable">
                <thead>
                    <tr>
                        <th style="width: 90px;">ห้องเรียน</th>
                        <th style="width: 140px;">รหัส 11 หลัก</th>
                        <th style="width: 100px;">รหัส 6 หลัก</th>
                        <th>ชื่อ - นามสกุล</th>
                        <th style="width: 100px;">สถานะ</th>
                        <th style="width: 120px; text-align: center;">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php foreach($students as $s): ?>
                        <tr id="row-<?= $s['id'] ?>">
                            <td>
                                <?php if (strpos($s['room'], '11') !== false): ?>
                                    <span class="room-badge room-11"><?= htmlspecialchars($s['room']) ?></span>
                                <?php else: ?>
                                    <span class="room-badge room-12"><?= htmlspecialchars($s['room']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-variant-numeric: tabular-nums;"><?= htmlspecialchars($s['student_id_full']) ?></td>
                            <td style="font-weight: 600; color: var(--accent);"><?= htmlspecialchars($s['student_code']) ?></td>
                            <td><?= htmlspecialchars($s['title'] . $s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td>
                                <?php if($s['is_active']): ?>
                                    <span class="badge-status badge-present">ใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge-status badge-absent">ยกเลิก</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <button class="btn-icon" title="แก้ไข"
                                    onclick="openEditModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['student_id_full']) ?>', '<?= htmlspecialchars($s['student_code']) ?>', '<?= htmlspecialchars($s['title']) ?>', '<?= htmlspecialchars($s['first_name']) ?>', '<?= htmlspecialchars($s['last_name']) ?>', '<?= htmlspecialchars($s['room']) ?>', <?= $s['is_active'] ?>)">
                                    <i data-lucide="edit" class="icon"></i>
                                </button>
                                <button class="btn-icon" title="ลบ" onclick="confirmDelete(<?= $s['id'] ?>, '<?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>')">
                                    <i data-lucide="trash-2" class="icon" style="color: var(--danger);"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ==================== MODAL: เพิ่มนักศึกษา ==================== -->
<div id="addModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i data-lucide="user-plus" class="icon"></i> เพิ่มนักศึกษา</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group-sm">
                    <label>คำนำหน้า</label>
                    <select id="add_title" class="form-ctrl">
                        <option value="นาย">นาย</option>
                        <option value="นางสาว">นางสาว</option>
                        <option value="นาง">นาง</option>
                    </select>
                </div>
                <div class="form-group-sm" style="flex:2;">
                    <label>ชื่อ <span class="req">*</span></label>
                    <input type="text" id="add_first_name" class="form-ctrl" placeholder="ชื่อ">
                </div>
                <div class="form-group-sm" style="flex:2;">
                    <label>นามสกุล <span class="req">*</span></label>
                    <input type="text" id="add_last_name" class="form-ctrl" placeholder="นามสกุล">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group-sm" style="flex:1.5;">
                    <label>ห้องเรียน <span class="req">*</span></label>
                    <select id="add_room" class="form-ctrl">
                        <option value="ปวส.1/11">ปวส.1/11</option>
                        <option value="ปวส.1/12">ปวส.1/12</option>
                    </select>
                </div>
                <div class="form-group-sm" style="flex:2;">
                    <label>รหัส 11 หลัก <span class="req">*</span></label>
                    <input type="text" id="add_id_full" class="form-ctrl" placeholder="เช่น 69 31908 0030" maxlength="15">
                </div>
                <div class="form-group-sm">
                    <label>รหัส 6 หลัก <span class="req">*</span></label>
                    <input type="text" id="add_code" class="form-ctrl" placeholder="032789" maxlength="6">
                </div>
            </div>
            <p id="addError" class="form-error" style="display:none;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary-custom" onclick="closeModal('addModal')">ยกเลิก</button>
            <button class="btn-primary-custom" onclick="submitAdd()">
                <i data-lucide="save" class="icon"></i> บันทึก
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: แก้ไขนักศึกษา ==================== -->
<div id="editModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i data-lucide="edit" class="icon"></i> แก้ไขข้อมูลนักศึกษา</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_id">
            <div class="form-row">
                <div class="form-group-sm">
                    <label>คำนำหน้า</label>
                    <select id="edit_title" class="form-ctrl">
                        <option value="นาย">นาย</option>
                        <option value="นางสาว">นางสาว</option>
                        <option value="นาง">นาง</option>
                    </select>
                </div>
                <div class="form-group-sm" style="flex:2;">
                    <label>ชื่อ <span class="req">*</span></label>
                    <input type="text" id="edit_first_name" class="form-ctrl">
                </div>
                <div class="form-group-sm" style="flex:2;">
                    <label>นามสกุล <span class="req">*</span></label>
                    <input type="text" id="edit_last_name" class="form-ctrl">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group-sm" style="flex:1.5;">
                    <label>ห้องเรียน <span class="req">*</span></label>
                    <select id="edit_room" class="form-ctrl">
                        <option value="ปวส.1/11">ปวส.1/11</option>
                        <option value="ปวส.1/12">ปวส.1/12</option>
                    </select>
                </div>
                <div class="form-group-sm" style="flex:2;">
                    <label>รหัส 11 หลัก <span class="req">*</span></label>
                    <input type="text" id="edit_id_full" class="form-ctrl" maxlength="15">
                </div>
                <div class="form-group-sm">
                    <label>รหัส 6 หลัก <span class="req">*</span></label>
                    <input type="text" id="edit_code" class="form-ctrl" maxlength="6">
                </div>
            </div>
            <div class="form-group-sm">
                <label>สถานะ</label>
                <select id="edit_is_active" class="form-ctrl">
                    <option value="1">ใช้งาน</option>
                    <option value="0">ยกเลิก</option>
                </select>
            </div>
            <p id="editError" class="form-error" style="display:none;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary-custom" onclick="closeModal('editModal')">ยกเลิก</button>
            <button class="btn-primary-custom" onclick="submitEdit()">
                <i data-lucide="save" class="icon"></i> บันทึกการแก้ไข
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: ยืนยันการลบ ==================== -->
<div id="deleteModal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:400px;">
        <div class="modal-header">
            <h3 style="color:var(--danger);">⚠️ ยืนยันการลบ</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body" style="text-align:center; padding: 20px 0;">
            <p style="font-size:1rem;">คุณต้องการลบนักศึกษา</p>
            <p id="deleteTargetName" style="font-size:1.2rem; font-weight:700; color:var(--danger); margin: 8px 0;"></p>
            <p style="font-size:0.85rem; color:var(--muted);">การลบจะไม่สามารถกู้คืนได้</p>
            <input type="hidden" id="deleteTargetId">
        </div>
        <div class="modal-footer">
            <button class="btn-secondary-custom" onclick="closeModal('deleteModal')">ยกเลิก</button>
            <button style="background:var(--danger); color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:600;" onclick="submitDelete()">
                <i data-lucide="trash-2" class="icon"></i> ลบเลย
            </button>
        </div>
    </div>
</div>

<!-- ==================== STYLES ==================== -->
<style>
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.7);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999; padding: 20px;
}
.modal-box {
    background: var(--surface, #18181b);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px; width: 100%; max-width: 640px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    animation: slideUp .2s ease;
}
@keyframes slideUp { from { transform: translateY(30px); opacity:0; } to { transform: translateY(0); opacity:1; } }
.modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.08);
}
.modal-header h3 { margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px; }
.modal-close { background: none; border: none; color: var(--muted); font-size: 1.5rem; cursor: pointer; line-height: 1; }
.modal-body { padding: 20px 24px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.08); }
.form-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
.form-group-sm { flex: 1; min-width: 100px; display: flex; flex-direction: column; gap: 6px; }
.form-group-sm label { font-size: 0.82rem; color: var(--muted); }
.form-ctrl {
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px; color: #fff; padding: 9px 12px; font-size: 0.9rem;
    outline: none; transition: border-color .2s; width: 100%; box-sizing: border-box;
    font-family: inherit;
}
.form-ctrl:focus { border-color: var(--accent, #818cf8); }
.req { color: #f87171; }
.form-error { color: #f87171; font-size: 0.85rem; margin-top: 8px; }
.btn-secondary-custom {
    background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
    color: #fff; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;
}
</style>

<!-- ==================== SCRIPTS ==================== -->
<script>
function openAddModal() {
    document.getElementById('add_title').value = 'นาย';
    document.getElementById('add_first_name').value = '';
    document.getElementById('add_last_name').value = '';
    document.getElementById('add_room').value = 'ปวส.1/11';
    document.getElementById('add_id_full').value = '';
    document.getElementById('add_code').value = '';
    document.getElementById('addError').style.display = 'none';
    document.getElementById('addModal').style.display = 'flex';
    if (window.lucide) lucide.createIcons();
}

function openEditModal(id, id_full, code, title, first, last, room, is_active) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_id_full').value = id_full;
    document.getElementById('edit_code').value = code;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_first_name').value = first;
    document.getElementById('edit_last_name').value = last;
    document.getElementById('edit_room').value = room;
    document.getElementById('edit_is_active').value = is_active;
    document.getElementById('editError').style.display = 'none';
    document.getElementById('editModal').style.display = 'flex';
    if (window.lucide) lucide.createIcons();
}

function confirmDelete(id, name) {
    document.getElementById('deleteTargetId').value = id;
    document.getElementById('deleteTargetName').textContent = name;
    document.getElementById('deleteModal').style.display = 'flex';
    if (window.lucide) lucide.createIcons();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

async function submitAdd() {
    const payload = {
        action: 'add',
        title: document.getElementById('add_title').value,
        first_name: document.getElementById('add_first_name').value.trim(),
        last_name: document.getElementById('add_last_name').value.trim(),
        room: document.getElementById('add_room').value,
        student_id_full: document.getElementById('add_id_full').value.trim(),
        student_code: document.getElementById('add_code').value.trim(),
    };
    const res = await fetch('/projectiot/api/students.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) { closeModal('addModal'); location.reload(); }
    else { const el = document.getElementById('addError'); el.textContent = data.message; el.style.display = 'block'; }
}

async function submitEdit() {
    const payload = {
        action: 'edit',
        id: parseInt(document.getElementById('edit_id').value),
        title: document.getElementById('edit_title').value,
        first_name: document.getElementById('edit_first_name').value.trim(),
        last_name: document.getElementById('edit_last_name').value.trim(),
        room: document.getElementById('edit_room').value,
        student_id_full: document.getElementById('edit_id_full').value.trim(),
        student_code: document.getElementById('edit_code').value.trim(),
        is_active: parseInt(document.getElementById('edit_is_active').value),
    };
    const res = await fetch('/projectiot/api/students.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) { closeModal('editModal'); location.reload(); }
    else { const el = document.getElementById('editError'); el.textContent = data.message; el.style.display = 'block'; }
}

async function submitDelete() {
    const id = parseInt(document.getElementById('deleteTargetId').value);
    const res = await fetch('/projectiot/api/students.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete', id: id})
    });
    const data = await res.json();
    if (data.success) { closeModal('deleteModal'); document.getElementById('row-' + id)?.remove(); }
    else { alert('เกิดข้อผิดพลาด: ' + data.message); }
}

// ปิด modal เมื่อคลิกพื้นหลัง
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
