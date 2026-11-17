<?php
/**
 * Leave Statistics & Reports Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireStaff();

$pageTitle = 'สถิติการลาของนักเรียน';

$db = Database::connect();

// ดึงภาพรวมสถิติ
// 1. สรุปแยกประเภท (ป่วย vs กิจ)
$typeStatsStmt = $db->query("
    SELECT leave_type, COUNT(*) as cnt, SUM(total_days) as sum_days 
    FROM leave_requests 
    WHERE status = 'approved' 
    GROUP BY leave_type
");
$typeStats = $typeStatsStmt->fetchAll();

// 2. สรุปรายเดือนของปีปัจจุบัน (ใช้ EXTRACT สำหรับ PostgreSQL / รองรับทั้งคู่)
$currentYear = (int)date('Y');
$monthlyStmt = $db->prepare("
    SELECT 
        CAST(EXTRACT(MONTH FROM start_date) AS INTEGER) as m,
        SUM(CASE WHEN leave_type = 'sick' THEN 1 ELSE 0 END) as sick_count,
        SUM(CASE WHEN leave_type = 'personal' THEN 1 ELSE 0 END) as personal_count,
        COUNT(*) as total_count
    FROM leave_requests
    WHERE CAST(EXTRACT(YEAR FROM start_date) AS INTEGER) = ? AND status = 'approved'
    GROUP BY EXTRACT(MONTH FROM start_date)
    ORDER BY m ASC
");
$monthlyStmt->execute([$currentYear]);
$monthlyData = $monthlyStmt->fetchAll();

// จัดเตรียมข้อมูลสำหรับ Chart 12 เดือน
$monthsLabels = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
$sickSeries = array_fill(0, 12, 0);
$personalSeries = array_fill(0, 12, 0);

foreach ($monthlyData as $row) {
    $idx = (int)$row['m'] - 1;
    if ($idx >= 0 && $idx < 12) {
        $sickSeries[$idx] = (int)$row['sick_count'];
        $personalSeries[$idx] = (int)$row['personal_count'];
    }
}

// 3. ท็อปห้องเรียนที่มีการลามากที่สุด
$roomStatsStmt = $db->query("
    SELECT s.room, COUNT(r.id) as total_leaves, SUM(r.total_days) as total_days
    FROM leave_requests r
    JOIN students s ON r.student_id = s.id
    WHERE r.status = 'approved'
    GROUP BY s.room
    ORDER BY total_leaves DESC
    LIMIT 5
");
$roomStats = $roomStatsStmt->fetchAll();

// 4. นักเรียนที่มีจำนวนวันลาสะสมสูงสุด
$topStudentsStmt = $db->query("
    SELECT s.student_code, s.student_id_full, CONCAT(s.title, s.first_name, ' ', s.last_name) as name, s.room,
           COUNT(r.id) as request_count,
           SUM(CASE WHEN r.leave_type = 'sick' THEN r.total_days ELSE 0 END) as sick_days,
           SUM(CASE WHEN r.leave_type = 'personal' THEN r.total_days ELSE 0 END) as personal_days,
           SUM(r.total_days) as total_days
    FROM leave_requests r
    JOIN students s ON r.student_id = s.id
    WHERE r.status = 'approved'
    GROUP BY s.id, s.student_code, s.student_id_full, s.title, s.first_name, s.last_name, s.room
    ORDER BY total_days DESC
    LIMIT 10
");
$topStudents = $topStudentsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content fade-in">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="page-title">
                <i data-lucide="bar-chart-2" style="color: var(--accent); vertical-align: middle; margin-right: 8px;"></i>
                สถิติและรายงานการลา
            </h1>
            <p class="page-subtitle">สรุปภาพรวมการลาป่วย ลากิจ รายเดือน รายภาคเรียน และรายห้องเรียน</p>
        </div>
        <div>
            <a href="leave_manage.php" class="btn btn-primary-custom d-flex align-items-center gap-2">
                <i data-lucide="clipboard-check" style="width: 16px; height: 16px;"></i>
                <span>จัดการคำร้องขอลา</span>
            </a>
        </div>
    </div>

    <!-- กราฟแนวโน้มการลารายเดือน -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="panel p-4" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="text-white font-weight-bold mb-0">แนวโน้มการลาประจำปี <?= $currentYear ?> (แยกตามประเภท)</h5>
                    <span class="badge bg-secondary">อนุมัติแล้ว</span>
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="leaveMonthlyChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel p-4" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg); height: 100%;">
                <h5 class="text-white font-weight-bold mb-3">สัดส่วนประเภทการลา</h5>
                <div style="height: 230px; position: relative;">
                    <canvas id="leavePieChart"></canvas>
                </div>
                <div class="mt-3 pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-around text-center">
                    <div>
                        <span class="text-danger small font-weight-bold">ลาป่วย (Sick)</span>
                        <h4 class="text-white mb-0 mt-1"><?= array_sum($sickSeries) ?> ครั้ง</h4>
                    </div>
                    <div>
                        <span class="text-warning small font-weight-bold">ลากิจ (Personal)</span>
                        <h4 class="text-white mb-0 mt-1"><?= array_sum($personalSeries) ?> ครั้ง</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- สถิติห้องเรียนและรายบุคคล -->
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="panel p-4" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
                <h5 class="text-white font-weight-bold mb-3">5 ห้องเรียนที่มีการลาสูงสุด</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-sm mb-0" style="background: transparent;">
                        <thead>
                            <tr class="text-muted small">
                                <th>ห้องเรียน</th>
                                <th class="text-center">จำนวนครั้ง</th>
                                <th class="text-end">รวมวันลา</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($roomStats)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">ยังไม่มีข้อมูลการลาที่อนุมัติ</td></tr>
                            <?php else: ?>
                                <?php foreach ($roomStats as $rs): ?>
                                    <tr>
                                        <td class="text-white fw-bold"><?= htmlspecialchars($rs['room']) ?></td>
                                        <td class="text-center"><?= $rs['total_leaves'] ?> ครั้ง</td>
                                        <td class="text-end text-warning font-weight-bold"><?= $rs['total_days'] ?> วัน</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="panel p-4" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);">
                <h5 class="text-white font-weight-bold mb-3">สรุปวันลาสะสมของนักเรียน (สูงสุด 10 อันดับ)</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-sm mb-0" style="background: transparent;">
                        <thead>
                            <tr class="text-muted small">
                                <th>รหัส</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ห้อง</th>
                                <th class="text-center text-danger">ป่วย</th>
                                <th class="text-center text-warning">กิจ</th>
                                <th class="text-end">รวมวันลา</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topStudents)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">ยังไม่มีข้อมูลการลาที่อนุมัติ</td></tr>
                            <?php else: ?>
                                <?php foreach ($topStudents as $st): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($st['student_code']) ?></td>
                                        <td class="text-white fw-semibold"><?= htmlspecialchars($st['name']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($st['room']) ?></span></td>
                                        <td class="text-center"><?= $st['sick_days'] ?> วัน</td>
                                        <td class="text-center"><?= $st['personal_days'] ?> วัน</td>
                                        <td class="text-end text-accent fw-bold"><?= $st['total_days'] ?> วัน</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Chart 1: Bar Chart รายเดือน
        const ctxBar = document.getElementById('leaveMonthlyChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthsLabels) ?>,
                datasets: [
                    {
                        label: 'ลาป่วย (Sick)',
                        data: <?= json_encode($sickSeries) ?>,
                        backgroundColor: 'rgba(248, 113, 113, 0.7)',
                        borderColor: '#f87171',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'ลากิจ (Personal)',
                        data: <?= json_encode($personalSeries) ?>,
                        backgroundColor: 'rgba(251, 191, 36, 0.7)',
                        borderColor: '#fbbf24',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#cbd5e1' }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    y: {
                        ticks: { color: '#94a3b8', stepSize: 1 },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        beginAtZero: true
                    }
                }
            }
        });

        // Chart 2: Doughnut Chart
        const totalSick = <?= array_sum($sickSeries) ?>;
        const totalPersonal = <?= array_sum($personalSeries) ?>;
        const ctxPie = document.getElementById('leavePieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['ลาป่วย', 'ลากิจ'],
                datasets: [{
                    data: [totalSick, totalPersonal],
                    backgroundColor: ['#f87171', '#fbbf24'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#cbd5e1' }
                    }
                }
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
