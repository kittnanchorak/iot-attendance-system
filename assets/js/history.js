/* =====================================================
   History Logic
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const roomFilter = document.getElementById('roomFilter');
    const btnSearch = document.getElementById('btnSearch');
    const tbody = document.getElementById('historyTableBody');
    
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const pageInfo = document.getElementById('pageInfo');

    let currentPage = 1;
    let totalPages = 1;
    window.currentHistoryData = []; // Expose for export.js

    const fetchHistory = async (page = 1) => {
        try {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4">กำลังโหลดข้อมูล...</td></tr>`;
            
            const from = encodeURIComponent(dateFrom.value);
            const to = encodeURIComponent(dateTo.value);
            const room = encodeURIComponent(roomFilter.value);
            
            const res = await fetch(`../api/get_history.php?date_from=${from}&date_to=${to}&room=${room}&page=${page}`);
            const json = await res.json();

            if (!json.success) return;

            window.currentHistoryData = json.data;
            currentPage = json.pagination.page;
            totalPages = json.pagination.total_pages;
            
            // Render table
            if (json.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 empty-state"><i data-lucide="inbox" class="icon mx-auto mb-2"></i> ไม่พบข้อมูลในช่างเวลาที่เลือก</td></tr>`;
                lucide.createIcons({ root: tbody });
            } else {
                let html = '';
                json.data.forEach(row => {
                    // Format Date
                    const d = new Date(row.attendance_date);
                    const dateStr = d.toLocaleDateString('th-TH');

                    html += `
                        <tr>
                            <td style="color: var(--text-secondary);">${dateStr}</td>
                            <td>${getRoomBadge(row.room)}</td>
                            <td style="font-variant-numeric: tabular-nums;">${row.student_id_full}</td>
                            <td>${row.title}${row.first_name} ${row.last_name}</td>
                            <td>${formatTime(row.check_in_time)}</td>
                            <td>${getStatusBadge(row.status)}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
                lucide.createIcons({ root: tbody });
            }

            // Update Pagination
            const startRecord = json.data.length > 0 ? ((currentPage - 1) * json.pagination.per_page) + 1 : 0;
            const endRecord = startRecord + json.data.length - (json.data.length > 0 ? 1 : 0);
            
            pageInfo.textContent = `แสดง ${startRecord} ถึง ${endRecord} จาก ${json.pagination.total_rows} รายการ (หน้า ${currentPage}/${totalPages})`;
            
            btnPrev.disabled = currentPage <= 1;
            btnNext.disabled = currentPage >= totalPages;
            
            btnPrev.style.opacity = btnPrev.disabled ? 0.5 : 1;
            btnNext.style.opacity = btnNext.disabled ? 0.5 : 1;

        } catch (err) {
            console.error('Failed to fetch history', err);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
        }
    };

    // Events
    btnSearch.addEventListener('click', () => fetchHistory(1));
    btnPrev.addEventListener('click', () => { if (currentPage > 1) fetchHistory(currentPage - 1); });
    btnNext.addEventListener('click', () => { if (currentPage < totalPages) fetchHistory(currentPage + 1); });

    // Initial load
    fetchHistory(1);
});
