<?php
/**
 * Dashboard Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';
$pageScripts = ['dashboard.js'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content fade-in">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="page-title">แดชบอร์ด</h1>
        </div>
        <div class="live-clock">
            <i data-lucide="clock" class="icon"></i>
            <span class="clock-time" id="liveClock">--:--:--</span>
            <span id="liveDate" class="text-muted ms-2"></span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="card-grid">
        <div class="stat-card">
            <div class="card-icon"><i data-lucide="users" class="icon"></i></div>
            <div class="card-label">นักศึกษาทั้งหมด</div>
            <div class="card-value" id="statTotal">-</div>
        </div>
        <div class="stat-card">
            <div class="card-icon green"><i data-lucide="user-check" class="icon"></i></div>
            <div class="card-label">เข้าเรียน</div>
            <div class="card-value" id="statPresent" style="color: var(--success);">-</div>
        </div>
        <div class="stat-card">
            <div class="card-icon red"><i data-lucide="user-x" class="icon"></i></div>
            <div class="card-label">ขาดเรียน</div>
            <div class="card-value" id="statAbsent" style="color: var(--danger);">-</div>
        </div>
        <div class="stat-card">
            <div class="card-icon yellow"><i data-lucide="clock-4" class="icon"></i></div>
            <div class="card-label">ยังไม่เข้าเรียน</div>
            <div class="card-value" id="statPending" style="color: var(--warning);">-</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Charts -->
        <div class="panel h-100">
            <div class="panel-header">
                <h2 class="panel-title">สรุปการเข้าเรียน</h2>
            </div>
            <div class="panel-body d-flex align-items-center justify-content-center" style="height: 300px;">
                <div class="chart-container" style="width: 240px; height: 240px;">
                    <canvas id="doughnutChart"></canvas>
                    <div class="percentage-ring" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;">
                        <span class="ring-text" id="statPct">0%</span>
                        <span class="ring-label">เข้าเรียน</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel h-100">
            <div class="panel-header">
                <h2 class="panel-title">แนวโน้ม 7 วันล่าสุด</h2>
            </div>
            <div class="panel-body" style="height: 300px;">
                <div class="chart-container h-100">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Rooms -->
    <div class="grid-2">
        <div class="panel h-100">
            <div class="panel-header">
                <h2 class="panel-title">เข้าเรียนล่าสุด</h2>
                <a href="attendance.php" class="btn-secondary-custom btn-sm">ดูทั้งหมด</a>
            </div>
            <div class="panel-body" id="recentActivityList">
                <div class="empty-state">
                    <i data-lucide="inbox" class="icon"></i>
                    <p>ยังไม่มีข้อมูลการเข้าเรียนวันนี้</p>
                </div>
            </div>
        </div>

        <div class="panel h-100">
            <div class="panel-header">
                <h2 class="panel-title">แยกตามห้อง</h2>
            </div>
            <div class="panel-body no-padding">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ห้องเรียน</th>
                            <th>จำนวน</th>
                            <th>เข้าเรียน</th>
                            <th>เปอร์เซ็นต์</th>
                        </tr>
                    </thead>
                    <tbody id="roomStatsList">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>