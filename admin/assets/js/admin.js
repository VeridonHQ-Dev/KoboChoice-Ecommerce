/**
 * Kobo Choice - Admin Panel JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {

    // ─── CSRF-aware POST helper ────────────────────────────────────────
    // Every state-changing request must carry the session CSRF token.
    function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-Token': (window.CSRF_TOKEN || '') }
        });
    }

    // ─── Sidebar Toggle (mobile) ───────────────────────────────────────
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('adminSidebar');
    var sidebarClose = document.getElementById('sidebarClose');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }

    if (sidebar) {
        function closeSidebar() {
            sidebar.classList.remove('open');
        }

        // The X inside the sidebar header.
        if (sidebarClose) {
            sidebarClose.addEventListener('click', closeSidebar);
        }

        // Escape is the expected way out of an overlay panel.
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });

        // Following a link navigates away, so the panel should not be left
        // sitting open behind the new page if the browser restores it.
        sidebar.addEventListener('click', function(e) {
            if (e.target.closest('.nav-link')) {
                closeSidebar();
            }
        });
    }

    // ─── Auto-dismiss alerts after 5s ─────────────────────────────────
    document.querySelectorAll('.admin-alert').forEach(function(el) {
        setTimeout(function() {
            el.style.transition = 'opacity 0.3s';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 300);
        }, 5000);
    });

    // ─── Confirm Delete ────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-delete');
        if (btn) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this?')) return;

            var url = btn.getAttribute('data-url');
            var id = btn.getAttribute('data-id');
            var type = btn.getAttribute('data-type') || 'item';

            var formData = new FormData();
            formData.append('id', id);

            postForm(url, formData)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        var row = btn.closest('tr');
                        if (row) {
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            setTimeout(function() { row.remove(); }, 300);
                        }
                        showAlert('success', type + ' deleted successfully.');
                    } else {
                        showAlert('danger', res.message || 'Delete failed.');
                    }
                })
                .catch(function() {
                    showAlert('danger', 'Network error. Please try again.');
                });
        }
    });

    // ─── Toggle Status (active/draft, is_active, etc.) ────────────────
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-toggle');
        if (btn) {
            e.preventDefault();
            var url = btn.getAttribute('data-url');
            var formData = new FormData();
            formData.append('id', btn.getAttribute('data-id'));
            formData.append('field', btn.getAttribute('data-field'));
            formData.append('value', btn.getAttribute('data-value'));

            postForm(url, formData)
                .then(r => r.json())
                .then(res => {
                    if (res.success) location.reload();
                    else showAlert('danger', res.message || 'Update failed.');
                });
        }
    });

    // ─── Inline placement flags (the "Deals" column) ──────────────────
    // Saves on change, so the homepage section reflects the tick straight
    // away. The switch reverts itself if the request is rejected, so what
    // the admin sees always matches what is stored.
    document.addEventListener('change', function(e) {
        var cb = e.target.closest('.flag-toggle');
        if (!cb) return;

        var label = cb.getAttribute('data-label') || 'Product';
        var formData = new FormData();
        formData.append('id', cb.getAttribute('data-id'));
        formData.append('field', cb.getAttribute('data-field'));
        formData.append('value', cb.checked ? 1 : 0);

        cb.disabled = true;

        postForm(cb.getAttribute('data-url'), formData)
            .then(r => r.json())
            .then(function(res) {
                cb.disabled = false;
                if (res.success) {
                    showAlert('success', cb.checked
                        ? 'Product added to ' + label + '.'
                        : 'Product removed from ' + label + '.');
                } else {
                    cb.checked = !cb.checked;
                    showAlert('danger', res.message || 'Update failed.');
                }
            })
            .catch(function() {
                cb.disabled = false;
                cb.checked = !cb.checked;
                showAlert('danger', 'Network error. Please try again.');
            });
    });

    // ─── Image Preview ─────────────────────────────────────────────────
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function(input) {
        input.addEventListener('change', function() {
            var preview = document.getElementById(this.getAttribute('data-preview'));
            if (preview && this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // ─── Select All Checkbox ───────────────────────────────────────────
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    // ─── Form submission helper ────────────────────────────────────────
    window.submitForm = function(formId, callback) {
        var form = document.getElementById(formId);
        if (!form) return;

        var formData = new FormData(form);
        var submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        postForm(form.action, formData)
        .then(r => r.json())
        .then(function(res) {
            if (submitBtn) submitBtn.disabled = false;
            if (callback) callback(res);
        })
        .catch(function(err) {
            if (submitBtn) submitBtn.disabled = false;
            showAlert('danger', 'Network error. Please try again.');
        });
    };

    // ─── Generic AJAX POST ─────────────────────────────────────────────
    window.ajaxPost = function(url, data, callback) {
        var formData = new FormData();
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                formData.append(key, data[key]);
            }
        }

        postForm(url, formData)
            .then(r => r.json())
            .then(function(res) {
                if (callback) callback(res);
            })
            .catch(function() {
                showAlert('danger', 'Network error.');
            });
    };

    // ─── Alert Helper ──────────────────────────────────────────────────
    window.showAlert = function(type, message) {
        var existing = document.querySelector('.admin-alert');
        if (existing) existing.remove();

        var icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        var html = '<div class="admin-alert admin-alert-' + type + '">' +
                   '<i class="fa fa-' + icon + '"></i> ' + message + '</div>';

        var content = document.querySelector('.admin-content');
        if (content) {
            content.insertAdjacentHTML('afterbegin', html);
            var alert = content.querySelector('.admin-alert');
            setTimeout(function() {
                alert.style.transition = 'opacity 0.3s';
                alert.style.opacity = '0';
                setTimeout(function() { alert.remove(); }, 300);
            }, 5000);
        }
    };
});
