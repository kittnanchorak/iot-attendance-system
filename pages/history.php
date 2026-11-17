<?php
/**
 * History Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'ประวัติย้อนหลัง';
$pageScripts = ['export.js', 'history.js'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content fade-in">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="page-title">ประวัติย้อนหลัง</h1>
            <p class="page-subtitle">ค้นหาและส่งออกรายงานการเข้าเรียน</p>
        </div>
        <div class="d-flex gap-2">
            <button id="btnExportPdf" class="btn-secondary-custom">
                <i data-lucide="file-text" class="icon"></i> Export PDF
            </button>
            <button id="btnExportExcel" class="btn-success-custom btn-primary-custom" style="background: var(--success); color: #fff;">
                <i data-lucide="file-spreadsheet" class="icon"></i> Export Excel
            </button>
        </div>
    </div>

    <div class="panel mb-4">
        <div class="panel-header">
            <div class="filter-bar w-100">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 0.85rem;">ตั้งแต่วันที่:</span>
                    <input type="date" id="dateFrom" class="filter-select" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 0.85rem;">ถึงวันที่:</span>
                    <input type="date" id="dateTo" class="filter-select" value="<?= date('Y-m-d') ?>">
                </div>
                <select id="roomFilter" class="filter-select">
                    <option value="">ทุกห้องเรียน</option>
                    <option value="ปวส.1/11">ปวส.1/11</option>
                    <option value="ปวส.1/12">ปวส.1/12</option>
                </select>
                <button id="btnSearch" class="btn-primary-custom">
                    <i data-lucide="search" class="icon"></i> ค้นหา
                </button>
            </div>
        </div>
        
        <div class="panel-body no-padding" style="overflow-x: auto;">
            <table class="data-table" id="historyTable">
                <thead>
                    <tr>
                        <th style="width: 100px;">วันที่</th>
                        <th style="width: 80px;">ห้องเรียน</th>
                        <th style="width: 140px;">รหัสนักศึกษา</th>
                        <th>ชื่อ - นามสกุล</th>
                        <th style="width: 120px;">เวลาเข้าเรียน</th>
                        <th style="width: 140px;">สถานะ</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
        <div class="pagination-bar">
            <span id="pageInfo">แสดง 0 ถึง 0 จาก 0 รายการ</span>
            <div class="pagination-btns">
                <button class="btn-icon" id="btnPrev"><i data-lucide="chevron-left" class="icon"></i></button>
                <button class="btn-icon" id="btnNext"><i data-lucide="chevron-right" class="icon"></i></button>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
