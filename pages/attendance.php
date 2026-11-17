<?php
/**
 * Attendance Today Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'เข้าเรียนวันนี้';
$pageScripts = ['attendance.js'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content fade-in">
    <div class="page-header">
        <h1 class="page-title">เข้าเรียนวันนี้</h1>
        <p class="page-subtitle">แสดงข้อมูลการเข้าเรียนแบบ Real-time ประจำวันที่ <span id="currentDateTxt"></span></p>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="filter-bar w-100">
                <div class="position-relative">
                    <input type="text" id="searchInput" class="search-input" placeholder="ค้นหารหัส หรือ ชื่อ-สกุล..." autocomplete="off">
                    <i data-lucide="search" class="icon" style="position: absolute; right: 12px; top: 10px; width: 16px; height: 16px; color: var(--text-muted);"></i>
                </div>
                
                <select id="statusFilter" class="filter-select">
                    <option value="">สถานะทั้งหมด</option>
                    <option value="present">เข้าเรียนแล้ว</option>
                    <option value="pending">ยังไม่เข้าเรียน</option>
                    <option value="absent">ขาดเรียน</option>
                </select>

                <select id="roomFilter" class="filter-select">
                    <option value="">ทุกห้องเรียน</option>
                    <option value="ปวส.1/11">ปวส.1/11</option>
                    <option value="ปวส.1/12">ปวส.1/12</option>
                </select>

                <div class="ms-auto" id="lastUpdateTxt" style="font-size: 0.75rem; color: var(--text-muted);">
                    อัปเดตล่าสุด: กำลังโหลด...
                </div>
            </div>
        </div>
        <div class="panel-body no-padding" style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ห้องเรียน</th>
                        <th style="width: 140px;">รหัสนักศึกษา (11หลัก)</th>
                        <th style="width: 100px;">รหัสกด (6หลัก)</th>
                        <th>ชื่อ - นามสกุล</th>
                        <th style="width: 120px;">เวลาเข้าเรียน</th>
                        <th style="width: 140px;">สถานะ</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <tr><td colspan="6" class="text-center py-4">กำลังโหลดข้อมูล...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('currentDateTxt').textContent = new Date().toLocaleDateString('th-TH', options);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
