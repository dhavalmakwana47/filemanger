<!-- CSV Viewer -->
<div class="csv-viewer-wrapper">
    <div class="viewer-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo" style="height: 40px;">
            <h5 class="mb-0 text-primary fw-semibold">CSV Viewer</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">.csv Preview</span>
            <button onclick="toggleCSVFullScreen()" class="btn btn-sm btn-outline-secondary">Fullscreen</button>
        </div>
    </div>

    <div id="csv-table" class="sheet-body mt-3 rounded shadow-sm bg-white p-3 overflow-auto"></div>
</div>

<!-- PapaParse CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>

<script>
    const csvUrl = "{{ route($routeName, ['id' => $id]) }}";
    fetch(csvUrl)
        .then(response => response.text())
        .then(data => {
            const result = Papa.parse(data, { header: true, skipEmptyLines: true });
            const table = document.createElement('table');
            table.classList.add('table', 'table-bordered', 'table-striped');

            const thead = document.createElement('thead');
            const tbody = document.createElement('tbody');

            if (!result.meta.fields || result.meta.fields.length === 0) {
                document.getElementById('csv-table').innerHTML = '<div class="alert alert-warning">No headers found in CSV file.</div>';
                return;
            }

            const headerRow = document.createElement('tr');
            result.meta.fields.forEach(field => {
                const th = document.createElement('th');
                th.textContent = field;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);

            result.data.forEach(row => {
                const tr = document.createElement('tr');
                result.meta.fields.forEach(field => {
                    const td = document.createElement('td');
                    td.textContent = row[field] || '';
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });

            table.appendChild(thead);
            table.appendChild(tbody);
            document.getElementById('csv-table').appendChild(table);
        })
        .catch(error => {
            console.error('Error loading CSV:', error);
            document.getElementById('csv-table').innerHTML =
                `<div class="alert alert-danger">Error loading CSV: ${error.message}</div>`;
        });

    // Disable right-click on table
    document.addEventListener('contextmenu', function(e) {
        if (e.target.closest('#csv-table')) {
            e.preventDefault();
        }
    });

    // Fullscreen toggle
    function toggleCSVFullScreen() {
        const elem = document.querySelector('.csv-viewer-wrapper');
        if (!document.fullscreenElement) {
            elem.requestFullscreen().catch(err => {
                alert(`Error attempting fullscreen: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
        }
    }
</script>

<style>
    .csv-viewer-wrapper {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        min-height: 100vh;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .viewer-header {
        background-color: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .sheet-body {
        flex-grow: 1;
        overflow: auto;
        max-height: calc(100vh - 160px);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    th, td {
        border: 1px solid #dee2e6;
        padding: 10px;
        vertical-align: top;
    }

    th {
        background-color: #f1f3f5;
        font-weight: 600;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    @media (max-width: 768px) {
        .viewer-header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        table {
            font-size: 12px;
        }
    }
</style>
