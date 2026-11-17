/* =====================================================
   Export PDF & Excel
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    const btnExportPdf = document.getElementById('btnExportPdf');
    const btnExportExcel = document.getElementById('btnExportExcel');

    const formatStatusToText = (status) => {
        if (status === 'present') return 'เข้าเรียน';
        if (status === 'absent') return 'ขาดเรียน';
        return 'ยังไม่เข้าเรียน';
    };

    const getExportData = () => {
        if (!window.currentHistoryData || window.currentHistoryData.length === 0) {
            showToast('error', 'ไม่สามารถ Export ได้', 'ไม่มีข้อมูลให้ Export');
            return null;
        }

        const data = window.currentHistoryData.map((row, index) => {
            const d = new Date(row.attendance_date);
            const dateStr = d.toLocaleDateString('th-TH');
            
            return [
                index + 1,
                dateStr,
                row.room,
                row.student_id_full,
                `${row.title}${row.first_name} ${row.last_name}`,
                row.check_in_time ? row.check_in_time : '-',
                formatStatusToText(row.status)
            ];
        });

        return {
            head: [['ลำดับ', 'วันที่', 'ห้องเรียน', 'รหัสนักศึกษา', 'ชื่อ-นามสกุล', 'เวลาเข้าเรียน', 'สถานะ']],
            body: data
        };
    };

    // --- Export PDF ---
    if (btnExportPdf) {
        btnExportPdf.addEventListener('click', () => {
            const data = getExportData();
            if (!data) return;

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add a simple font (Note: For real Thai support, you'd need to load a custom font via VFS)
            // As a fallback in standard jsPDF without custom font, Thai might not render perfectly.
            // Using standard config for demonstration:
            
            doc.text("Attendance Report", 14, 15);
            doc.setFontSize(10);
            doc.text(`Exported on: ${new Date().toLocaleString('th-TH')}`, 14, 22);

            doc.autoTable({
                startY: 30,
                head: data.head,
                body: data.body,
                theme: 'grid',
                styles: { font: 'helvetica', fontSize: 8 },
                headStyles: { fillColor: [129, 140, 248] }
            });

            doc.save('Attendance_Report.pdf');
            showToast('success', 'Export สำเร็จ', 'ดาวน์โหลดไฟล์ PDF เรียบร้อยแล้ว');
        });
    }

    // --- Export Excel ---
    if (btnExportExcel) {
        btnExportExcel.addEventListener('click', () => {
            const data = getExportData();
            if (!data) return;

            // Combine head and body for worksheet
            const wsData = [...data.head, ...data.body];
            
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            
            // Adjust column widths
            ws['!cols'] = [
                {wch: 8}, {wch: 12}, {wch: 12}, {wch: 20}, {wch: 30}, {wch: 15}, {wch: 15}
            ];

            XLSX.utils.book_append_sheet(wb, ws, "Attendance");
            XLSX.writeFile(wb, "Attendance_Report.xlsx");
            
            showToast('success', 'Export สำเร็จ', 'ดาวน์โหลดไฟล์ Excel เรียบร้อยแล้ว');
        });
    }
});
