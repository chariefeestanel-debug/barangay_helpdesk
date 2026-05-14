// BarangayHelpDesk — Resident JS

// Drop zone for file uploads
document.addEventListener('DOMContentLoaded', () => {
    const dz = document.getElementById('dropZone');
    const fi = document.getElementById('attachments');
    if (dz && fi) {
        dz.addEventListener('click', () => fi.click());
        dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
        dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
        dz.addEventListener('drop', e => {
            e.preventDefault(); dz.classList.remove('dragover');
            fi.files = e.dataTransfer.files;
            updateFileList(fi);
        });
        fi.addEventListener('change', () => updateFileList(fi));
    }

    function updateFileList(input) {
        const list = document.getElementById('fileList');
        if (!list) return;
        list.innerHTML = '';
        Array.from(input.files).forEach(f => {
            const li = document.createElement('div');
            li.className = 'badge bg-secondary me-1 mb-1';
            li.textContent = f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)';
            list.appendChild(li);
        });
    }

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert-dismissible').forEach(el => {
        setTimeout(() => { const b = bootstrap.Alert.getOrCreateInstance(el); b.close(); }, 5000);
    });

    // Character counters
    document.querySelectorAll('[data-maxlength]').forEach(el => {
        const max = parseInt(el.dataset.maxlength);
        const counter = document.createElement('small');
        counter.className = 'text-muted d-block text-end mt-1';
        el.after(counter);
        const update = () => { counter.textContent = `${el.value.length} / ${max}`; };
        el.addEventListener('input', update);
        update();
    });

    // Lightbox for attachment images
    document.querySelectorAll('.attachment-thumb').forEach(img => {
        img.addEventListener('click', () => {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `<div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-dark">
                    <div class="modal-body p-1 text-center">
                        <img src="${img.src}" class="img-fluid rounded">
                    </div>
                    <div class="modal-footer border-0 py-1 justify-content-center">
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
});
