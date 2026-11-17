/* =====================================================
   App JavaScript (Global)
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    // Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
        const toggleSidebar = () => {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
        };

        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }
});

// Toast Notification System
const showToast = (type, title, message, duration = 5000) => {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast-item';
    
    let icon = '';
    if (type === 'success') {
        icon = '<i data-lucide="check-circle" class="toast-icon success"></i>';
    } else if (type === 'error') {
        icon = '<i data-lucide="x-circle" class="toast-icon" style="color: var(--danger)"></i>';
    } else {
        icon = '<i data-lucide="info" class="toast-icon" style="color: var(--accent)"></i>';
    }

    toast.innerHTML = `
        ${icon}
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" aria-label="Close">
            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
        </button>
    `;

    container.appendChild(toast);
    
    // Initialize icons for the new toast
    if (window.lucide) window.lucide.createIcons({ root: toast });

    const closeBtn = toast.querySelector('.toast-close');
    let timeoutId;

    const closeToast = () => {
        toast.classList.add('toast-out');
        toast.addEventListener('animationend', () => toast.remove());
    };

    closeBtn.addEventListener('click', () => {
        clearTimeout(timeoutId);
        closeToast();
    });

    timeoutId = setTimeout(closeToast, duration);
};

// Global helper for formatting date/time
const formatTime = (timeString) => {
    if (!timeString) return '-';
    // Remove seconds if present
    const parts = timeString.split(':');
    if (parts.length >= 2) return `${parts[0]}:${parts[1]} น.`;
    return timeString;
};

const getStatusBadge = (status) => {
    switch(status) {
        case 'present':
            return '<span class="badge-status badge-present"><span class="badge-dot"></span>เข้าเรียน</span>';
        case 'absent':
            return '<span class="badge-status badge-absent"><span class="badge-dot"></span>ขาดเรียน</span>';
        case 'pending':
        default:
            return '<span class="badge-status badge-pending"><span class="badge-dot"></span>ยังไม่เข้าเรียน</span>';
    }
};

const getRoomBadge = (room) => {
    if (room.includes('11')) return `<span class="room-badge room-11">${room}</span>`;
    if (room.includes('12')) return `<span class="room-badge room-12">${room}</span>`;
    return `<span class="room-badge" style="background: var(--bg-elevated); color: var(--text-secondary); border: 1px solid var(--border)">${room}</span>`;
};
