/* =====================================================
   Dashboard Logic & AJAX Polling
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    // --- Live Clock ---
    const updateClock = () => {
        const now = new Date();
        document.getElementById('liveClock').textContent = now.toLocaleTimeString('th-TH');
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('liveDate').textContent = now.toLocaleDateString('th-TH', options);
    };
    setInterval(updateClock, 1000);
    updateClock();

    // --- Charts Init ---
    Chart.defaults.color = '#a1a1aa';
    Chart.defaults.font.family = 'Inter, "Noto Sans Thai", sans-serif';

    let doughnutChart = null;
    let trendChart = null;

    const initCharts = () => {
        const ctxD = document.getElementById('doughnutChart').getContext('2d');
        doughnutChart = new Chart(ctxD, {
            type: 'doughnut',
            data: {
                labels: ['เข้าเรียน', 'ขาดเรียน', 'ยังไม่เข้าเรียน'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: ['#34d399', '#f87171', '#fbbf24'],
                    borderWidth: 0,
                    cutout: '80%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: true } }
            }
        });

        const ctxT = document.getElementById('trendChart').getContext('2d');
        trendChart = new Chart(ctxT, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'จำนวนนักศึกษาเข้าเรียน',
                    data: [],
                    borderColor: '#818cf8',
                    backgroundColor: 'rgba(129,140,248,0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#818cf8',
                    pointBorderColor: '#0f0f12',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    };

    initCharts();

    // --- Fetch Data ---
    let previousPresent = -1;

    const fetchStats = async () => {
        try {
            const res = await fetch('../api/get_stats.php');
            const data = await res.json();
            
            if (!data.success) return;

            // Update Stats Cards
            document.getElementById('statTotal').textContent = data.stats.total;
            document.getElementById('statPresent').textContent = data.stats.present;
            document.getElementById('statAbsent').textContent = data.stats.absent;
            document.getElementById('statPending').textContent = data.stats.pending;
            document.getElementById('statPct').textContent = data.stats.pct + '%';

            // Check if someone new checked in
            if (previousPresent !== -1 && data.stats.present > previousPresent) {
                // If we want a global sound effect, add it here
            }
            previousPresent = data.stats.present;

            // Update Doughnut Chart
            doughnutChart.data.datasets[0].data = [
                data.stats.present,
                data.stats.absent,
                data.stats.pending
            ];
            doughnutChart.update();

            // Update Trend Chart
            if (data.weekly.length > 0) {
                trendChart.data.labels = data.weekly.map(d => {
                    const date = new Date(d.attendance_date);
                    return `${date.getDate()}/${date.getMonth()+1}`;
                });
                trendChart.data.datasets[0].data = data.weekly.map(d => d.present_count);
                trendChart.update();
            }

            // Update Recent Activity
            const recentContainer = document.getElementById('recentActivityList');
            if (data.recent.length === 0) {
                recentContainer.innerHTML = `
                    <div class="empty-state">
                        <i data-lucide="inbox" class="icon"></i>
                        <p>ยังไม่มีข้อมูลการเข้าเรียนวันนี้</p>
                    </div>`;
                lucide.createIcons({ root: recentContainer });
            } else {
                let html = '';
                data.recent.forEach(r => {
                    html += `
                        <div class="activity-item slide-up">
                            <div class="activity-dot"></div>
                            <div>
                                <div class="activity-name">${r.title}${r.first_name} ${r.last_name}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">${getRoomBadge(r.room)}</div>
                            </div>
                            <div class="activity-time">${formatTime(r.check_in_time)}</div>
                        </div>
                    `;
                });
                recentContainer.innerHTML = html;
            }

            // Update Room Stats
            const roomContainer = document.getElementById('roomStatsList');
            if (data.rooms.length > 0) {
                let html = '';
                data.rooms.forEach(r => {
                    const pct = r.total > 0 ? Math.round((r.present_count / r.total) * 100) : 0;
                    html += `
                        <tr>
                            <td>${getRoomBadge(r.room)}</td>
                            <td>${r.total}</td>
                            <td style="color: var(--success); font-weight: 500;">${r.present_count}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex:1; height:4px; background:var(--bg-active); border-radius:2px; overflow:hidden;">
                                        <div style="width:${pct}%; height:100%; background:var(--success);"></div>
                                    </div>
                                    <span style="font-size:0.75rem; width:30px; text-align:right;">${pct}%</span>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                roomContainer.innerHTML = html;
            }
            
        } catch (err) {
            console.error('Failed to fetch stats', err);
        }
    };

    // Initial fetch
    fetchStats();
    
    // Polling every 5 seconds to optimize server load & stability
    setInterval(fetchStats, 5000);
});
