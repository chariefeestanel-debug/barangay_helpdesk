// BarangayHelpDesk — Admin JS

document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle
    const sidebar  = document.getElementById('adminSidebar');
    const main     = document.getElementById('adminMain');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                sidebar.classList.toggle('mobile-open');
                let overlay = document.getElementById('sidebarOverlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'sidebarOverlay';
                    overlay.className = 'overlay';
                    document.body.appendChild(overlay);
                    overlay.addEventListener('click', () => {
                        sidebar.classList.remove('mobile-open');
                        overlay.classList.remove('show');
                    });
                }
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('expanded');
            }
        });
    }

    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(el => {
        setTimeout(() => { try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch(e){} }, 5000);
    });

    // Confirm dangerous actions
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

    // Lightbox
    document.querySelectorAll('.attachment-thumb').forEach(img => {
        img.addEventListener('click', () => {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `<div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-dark">
                    <div class="modal-body p-1 text-center">
                        <img src="${img.src}" class="img-fluid rounded">
                    </div>
                    <div class="modal-footer border-0 justify-content-center">
                        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>`;
            document.body.appendChild(modal);
            const m = new bootstrap.Modal(modal);
            m.show();
            modal.addEventListener('hidden.bs.modal', () => modal.remove());
        });
    });

    // Auto-scroll chat panel to bottom
    const chatPanel = document.querySelector('.chat-panel');
    if (chatPanel) chatPanel.scrollTop = chatPanel.scrollHeight;

    // Table search filter
    const tableSearch = document.getElementById('tableSearch');
    if (tableSearch) {
        tableSearch.addEventListener('input', () => {
            const q = tableSearch.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
});
