/*
 * Import Learners page.
 * Before anything is uploaded the chosen CSV is read in the browser: required header
 * columns, number of learners and rows whose column count is off (the server rejects the
 * whole file for those). Import stays disabled until the file passes, and the form is
 * still posted normally to library.csv.upload so the server flow is unchanged.
 */
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('learnerImport');
    var form = document.getElementById('liForm');
    if (!root) {
        return;
    }

    var clearButton = document.getElementById('liClearResult');
    if (clearButton) {
        clearButton.addEventListener('click', function () {
            var result = document.getElementById('liResult');
            result.classList.add('is-leaving');
            setTimeout(function () { result.remove(); }, 200);
            fetch(root.dataset.clearUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': root.dataset.csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: '{}'
            }).catch(function () {
                // Nothing to undo: the list is only a reminder kept in the session.
            });
        });
    }

    // Shifts / Plans tabs
    root.querySelectorAll('.li-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            root.querySelectorAll('.li-tab').forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            root.querySelectorAll('.li-pane').forEach(function (pane) {
                var on = pane.dataset.pane === tab.dataset.tab;
                pane.classList.toggle('is-active', on);
                pane.hidden = !on;
            });
        });
    });

    var toast = document.getElementById('liToast');
    var toastTimer = null;
    function showToast(text) {
        if (!toast) {
            return;
        }
        toast.textContent = text;
        toast.classList.add('is-show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('is-show'); }, 1600);
    }

    // Copy a plan / shift name exactly as the importer expects it
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            try {
                document.execCommand('copy') ? resolve() : reject();
            } catch (e) {
                reject(e);
            } finally {
                document.body.removeChild(area);
            }
        });
    }

    root.querySelectorAll('.li-copy').forEach(function (button) {
        button.addEventListener('click', function () {
            var text = button.dataset.copy || '';
            copyText(text).then(function () {
                var icon = button.querySelector('i');
                button.classList.add('is-copied');
                if (icon) {
                    icon.className = 'fa-solid fa-check';
                }
                showToast('Copied "' + text + '"');
                setTimeout(function () {
                    button.classList.remove('is-copied');
                    if (icon) {
                        icon.className = 'fa-regular fa-copy';
                    }
                }, 1400);
            }).catch(function () {
                showToast('Could not copy. Select the name and copy it.');
            });
        });
    });

    // Blank template: just the header row, every column the importer reads
    var templateButton = document.getElementById('liTemplate');
    if (templateButton) {
        templateButton.addEventListener('click', function () {
            var columns = JSON.parse(root.dataset.templateColumns || '[]');
            var blob = new Blob(['﻿' + columns.join(',') + '\r\n'], { type: 'text/csv;charset=utf-8' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'learners-import-template.csv';
            document.body.appendChild(link);
            link.click();
            setTimeout(function () {
                URL.revokeObjectURL(link.href);
                link.remove();
            }, 0);
        });
    }

    if (!form) {
        return;
    }

    var requiredColumns = JSON.parse(root.dataset.requiredColumns || '[]');
    var input = document.getElementById('liFile');
    var drop = document.getElementById('liDrop');
    var fileCard = document.getElementById('liFileCard');
    var checks = document.getElementById('liChecks');
    var preview = document.getElementById('liPreview');
    var previewTable = document.getElementById('liPreviewTable');
    var submit = document.getElementById('liSubmit');
    var steps = root.querySelectorAll('.li-step');

    function setStep(current) {
        steps.forEach(function (step) {
            var number = parseInt(step.dataset.step, 10);
            step.classList.toggle('is-done', number < current);
            step.classList.toggle('is-current', number === current);
        });
    }

    function formatSize(bytes) {
        return bytes < 1024 * 1024 ? (bytes / 1024).toFixed(1) + ' KB' : (bytes / 1024 / 1024).toFixed(2) + ' MB';
    }

    // Small CSV parser (quotes, escaped quotes, CRLF) - enough for a pre-check.
    function parseCsv(text) {
        var rows = [];
        var row = [];
        var field = '';
        var quoted = false;
        for (var i = 0; i < text.length; i++) {
            var ch = text[i];
            if (quoted) {
                if (ch === '"' && text[i + 1] === '"') {
                    field += '"';
                    i++;
                } else if (ch === '"') {
                    quoted = false;
                } else {
                    field += ch;
                }
            } else if (ch === '"') {
                quoted = true;
            } else if (ch === ',') {
                row.push(field);
                field = '';
            } else if (ch === '\n' || ch === '\r') {
                if (ch === '\r' && text[i + 1] === '\n') {
                    i++;
                }
                row.push(field);
                rows.push(row);
                row = [];
                field = '';
            } else {
                field += ch;
            }
        }
        if (field !== '' || row.length) {
            row.push(field);
            rows.push(row);
        }
        // Drop blank lines.
        return rows.filter(function (r) {
            return r.some(function (cell) { return cell.trim() !== ''; });
        });
    }

    function addCheck(state, text) {
        var icons = { ok: 'fa-circle-check', warn: 'fa-triangle-exclamation', bad: 'fa-circle-xmark' };
        var li = document.createElement('li');
        li.className = 'li-check is-' + state;
        li.innerHTML = '<i class="fa-solid ' + icons[state] + '"></i><span></span>';
        li.querySelector('span').textContent = text;
        checks.appendChild(li);
    }

    function renderPreview(rows) {
        previewTable.innerHTML = '';
        var shown = rows.slice(0, 4);
        shown.forEach(function (cells, index) {
            var tr = document.createElement('tr');
            cells.forEach(function (cell) {
                var td = document.createElement(index === 0 ? 'th' : 'td');
                td.textContent = cell.trim();
                tr.appendChild(td);
            });
            previewTable.appendChild(tr);
        });
        preview.classList.toggle('d-none', shown.length < 2);
    }

    function reset() {
        input.value = '';
        fileCard.classList.add('d-none');
        drop.classList.remove('d-none');
        checks.innerHTML = '';
        submit.disabled = true;
        setStep(2);
    }

    function inspect(file) {
        drop.classList.add('d-none');
        fileCard.classList.remove('d-none');
        document.getElementById('liFileName').textContent = file.name;
        document.getElementById('liFileMeta').textContent = formatSize(file.size);
        checks.innerHTML = '';
        preview.classList.add('d-none');
        submit.disabled = true;

        if (!/\.csv$/i.test(file.name)) {
            addCheck('bad', 'This is not a .csv file. Save the sheet as CSV (Comma delimited) and choose it again.');
            setStep(2);
            return;
        }

        var reader = new FileReader();
        reader.onerror = function () {
            addCheck('bad', 'The file could not be read. Please choose it again.');
        };
        reader.onload = function () {
            var rows = parseCsv(String(reader.result).replace(/^﻿/, ''));
            if (rows.length === 0) {
                addCheck('bad', 'The file is empty.');
                return;
            }

            // Same as the server: "Plan Type" / "start_date " / "PLAN_TYPE" all count
            var header = rows[0].map(function (cell) { return cell.trim().toLowerCase().replace(/[\s\-]+/g, '_'); });
            var missing = requiredColumns.filter(function (column) { return header.indexOf(column) === -1; });
            var learners = rows.length - 1;
            var badRows = [];
            rows.slice(1).forEach(function (cells, index) {
                // Empty cells after the last column are ignored by the server too
                var count = cells.length;
                while (count > header.length && cells[count - 1].trim() === '') {
                    count--;
                }
                if (count !== header.length) {
                    badRows.push(index + 2);
                }
            });

            var ok = true;
            if (missing.length) {
                ok = false;
                addCheck('bad', 'Missing column' + (missing.length > 1 ? 's' : '') + ': ' + missing.join(', ') + '. Copy the header row from the sample file.');
            } else {
                addCheck('ok', 'All required columns are present.');
            }

            if (learners === 0) {
                ok = false;
                addCheck('bad', 'No learner rows found below the header.');
            } else {
                addCheck('ok', learners + ' learner' + (learners > 1 ? 's' : '') + ' found.');
            }

            if (badRows.length) {
                ok = false;
                addCheck('bad', 'Row' + (badRows.length > 1 ? 's ' : ' ') + badRows.slice(0, 8).join(', ') + (badRows.length > 8 ? '…' : '') +
                    ' ha' + (badRows.length > 1 ? 've' : 's') + ' a different number of columns than the header. Remove extra commas there.');
            }

            if (ok) {
                addCheck('warn', 'Plan, shift and date values are checked during import; rows with mistakes will be listed for you.');
            }

            renderPreview(rows);
            submit.disabled = !ok;
            setStep(ok ? 3 : 2);
        };
        reader.readAsText(file);
    }

    input.addEventListener('change', function () {
        if (input.files.length) {
            inspect(input.files[0]);
        }
    });

    ['dragenter', 'dragover'].forEach(function (type) {
        drop.addEventListener(type, function (event) {
            event.preventDefault();
            drop.classList.add('is-over');
        });
    });
    ['dragleave', 'drop'].forEach(function (type) {
        drop.addEventListener(type, function (event) {
            event.preventDefault();
            drop.classList.remove('is-over');
        });
    });
    drop.addEventListener('drop', function (event) {
        var files = event.dataTransfer && event.dataTransfer.files;
        if (files && files.length) {
            input.files = files;
            inspect(files[0]);
        }
    });

    document.getElementById('liFileRemove').addEventListener('click', reset);

    // One import at a time: lock the form while the server works.
    form.addEventListener('submit', function (event) {
        if (submit.disabled || !input.files.length) {
            event.preventDefault();
            return;
        }
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> <span>Importing learners...</span>';
        document.getElementById('liFileRemove').disabled = true;
    });
});
