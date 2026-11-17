/* =====================================================
   Attendance Logic & AJAX Polling
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('attendanceTableBody');
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const roomFilter = document.getElementById('roomFilter');
    const lastUpdateTxt = document.getElementById('lastUpdateTxt');

    let currentData = [];
    let previousPresentIds = new Set();
    let firstLoad = true;

    const renderTable = () => {
        if (currentData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 empty-state"><i data-lucide="inbox" class="icon mx-auto mb-2"></i> ไม่พบข้อมูลนักศึกษา</td></tr>`;
            lucide.createIcons({ root: tbody });
            return;
        }

        let html = '';
        currentData.forEach(row => {
            let rowClass = '';
            if (row.status === 'present') rowClass = 'row-present';
            else if (row.status === 'absent') rowClass = 'row-absent';

            html += `
                <tr class="${rowClass}" id="row-${row.sid}">
                    <td>${getRoomBadge(row.room)}</td>
                    <td style="font-variant-numeric: tabular-nums;">${row.student_id_full}</td>
                    <td style="font-weight: 600; color: var(--accent);">${row.student_code}</td>
                    <td>${row.title}${row.first_name} ${row.last_name}</td>
                    <td>${formatTime(row.check_in_time)}</td>
                    <td>${getStatusBadge(row.status)}</td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
        lucide.createIcons({ root: tbody });
    };

    const fetchAttendance = async () => {
        try {
            const search = encodeURIComponent(searchInput.value.trim());
            const status = encodeURIComponent(statusFilter.value);
            const room = encodeURIComponent(roomFilter.value);
            
            const res = await fetch(`../api/get_attendance.php?search=${search}&status=${status}&room=${room}`);
            const json = await res.json();

            if (!json.success) return;

            // Check for new check-ins to show toast
            if (!firstLoad && search === '' && status === '' && room === '') {
                const currentPresentIds = new Set();
                
                json.data.forEach(row => {
                    if (row.status === 'present') {
                        currentPresentIds.add(row.sid);
                        if (!previousPresentIds.has(row.sid)) {
                            // New check-in!
                            showToast(
                                'success',
                                'เช็คชื่อสำเร็จ',
                                `${row.title}${row.first_name} ${row.last_name} เข้าเรียนแล้ว เวลา ${formatTime(row.check_in_time)}`
                            );
                        }
                    }
                });
                
                previousPresentIds = currentPresentIds;
            } else if (firstLoad) {
                // Initial load, just populate the set
                json.data.forEach(row => {
                    if (row.status === 'present') previousPresentIds.add(row.sid);
                });
                firstLoad = false;
            }

            currentData = json.data;
            renderTable();

            // Update timestamp
            const now = new Date();
            lastUpdateTxt.textContent = `อัปเดตล่าสุด: ${now.toLocaleTimeString('th-TH')}`;

        } catch (err) {
            console.error('Failed to fetch attendance', err);
        }
    };

    // Events
    searchInput.addEventListener('input', () => {
        // Debounce search slightly
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(fetchAttendance, 400);
    });

    statusFilter.addEventListener('change', fetchAttendance);
    roomFilter.addEventListener('change', fetchAttendance);

    // Initial fetch
    fetchAttendance();
    
    // Poll every 5 seconds to optimize server load & stability
    setInterval(fetchAttendance, 5000);
});
