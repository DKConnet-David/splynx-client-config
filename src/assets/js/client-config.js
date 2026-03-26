/**
 * Client Config - Rich text editor with auto-save and audit history.
 * Uses Quill.js for the editor component.
 */
(function () {
    'use strict';

    var config = window.ClientConfigInit;
    var saveBtn = document.getElementById('btn-save');
    var statusEl = document.getElementById('config-status');
    var editorEl = document.getElementById('config-editor');
    var historyBody = document.getElementById('history-body');
    var toggleHistory = document.getElementById('toggle-history');
    var historyTableBody = document.getElementById('history-table-body');

    // --- Quill editor setup ---
    var quill = new Quill('#config-editor', {
        modules: {
            toolbar: '#editor-toolbar',
        },
        theme: 'snow',
        placeholder: 'Enter client configuration notes here...',
    });

    // Track original content to detect changes
    var originalContent = quill.root.innerHTML;

    // Enable save button when content changes
    quill.on('text-change', function () {
        var current = quill.root.innerHTML;
        var hasChanges = current !== originalContent;
        saveBtn.disabled = !hasChanges;
        if (hasChanges) {
            saveBtn.classList.add('btn-warning');
            saveBtn.classList.remove('btn-primary');
            statusEl.innerHTML = '<em>Unsaved changes</em>';
        }
    });

    // --- Save handler ---
    saveBtn.addEventListener('click', function () {
        var content = quill.root.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';

        var formData = new FormData();
        formData.append('content', content);
        formData.append(config.csrfParam, config.csrfToken);

        fetch(config.saveUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    originalContent = content;
                    saveBtn.innerHTML = '<i class="fa fa-check"></i> Saved';
                    saveBtn.classList.remove('btn-warning');
                    saveBtn.classList.add('btn-success');
                    statusEl.innerHTML =
                        'Last saved by <strong>' + escapeHtml(data.updated_by_name) +
                        '</strong> on ' + escapeHtml(data.updated_at);

                    // Refresh history if panel is open
                    if (historyBody.style.display !== 'none') {
                        loadHistory();
                    }

                    setTimeout(function () {
                        saveBtn.classList.remove('btn-success');
                        saveBtn.classList.add('btn-primary');
                        saveBtn.innerHTML = '<i class="fa fa-save"></i> Save';
                    }, 2000);
                } else {
                    alert('Save failed: ' + (data.message || 'Unknown error'));
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fa fa-save"></i> Save';
                }
            })
            .catch(function (err) {
                alert('Network error: ' + err.message);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fa fa-save"></i> Save';
            });
    });

    // --- Keyboard shortcut: Ctrl+S / Cmd+S ---
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            if (!saveBtn.disabled) {
                saveBtn.click();
            }
        }
    });

    // --- History toggle ---
    toggleHistory.addEventListener('click', function () {
        var isHidden = historyBody.style.display === 'none';
        historyBody.style.display = isHidden ? 'block' : 'none';
        var icon = toggleHistory.querySelector('.toggle-icon');
        icon.classList.toggle('fa-chevron-down', !isHidden);
        icon.classList.toggle('fa-chevron-up', isHidden);
    });

    // --- View diff ---
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-view-diff');
        if (btn) {
            document.getElementById('diff-before').innerHTML = btn.dataset.before || '<em>(empty)</em>';
            document.getElementById('diff-after').innerHTML = btn.dataset.after || '<em>(empty)</em>';
            $('#diffModal').modal('show');
        }
    });

    // --- Restore previous version ---
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-restore');
        if (btn) {
            if (!confirm('Restore this previous version? You can still save or discard.')) return;
            quill.root.innerHTML = btn.dataset.content || '';
            quill.update();
        }
    });

    // --- Load history via AJAX ---
    var historyPage = 1;

    function loadHistory() {
        fetch(config.historyUrl + '?page=' + historyPage, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (historyPage === 1) {
                    historyTableBody.innerHTML = '';
                }
                data.entries.forEach(function (entry) {
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escapeHtml(entry.created_at) + '</td>' +
                        '<td>' + escapeHtml(entry.changed_by_name) + '</td>' +
                        '<td>' +
                        '<button class="btn btn-xs btn-default btn-view-diff" ' +
                        'data-before="' + escapeAttr(entry.content_before) + '" ' +
                        'data-after="' + escapeAttr(entry.content_after) + '">' +
                        '<i class="fa fa-eye"></i> View Changes</button> ' +
                        '<button class="btn btn-xs btn-warning btn-restore" ' +
                        'data-content="' + escapeAttr(entry.content_before) + '">' +
                        '<i class="fa fa-undo"></i> Restore Before</button>' +
                        '</td>';
                    historyTableBody.appendChild(tr);
                });

                var loadMoreEl = document.getElementById('history-load-more');
                if (data.total > historyPage * data.per_page) {
                    loadMoreEl.style.display = 'block';
                } else {
                    loadMoreEl.style.display = 'none';
                }
            });
    }

    var loadMoreBtn = document.getElementById('btn-load-more');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            historyPage++;
            loadHistory();
        });
    }

    // --- Helpers ---
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escapeAttr(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                  .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

})();
