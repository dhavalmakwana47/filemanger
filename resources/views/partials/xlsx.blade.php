{{-- Optimized SmartSheet Excel Viewer --}}
<div class="excel-viewer">
    <div class="watermark">File Manager - Protected</div>

    <div class="viewer-container">
        <header class="viewer-header">
            <div class="brand">
                <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo" height="40">
                <h5 class="title">SmartSheet Viewer</h5>
            </div>
            <div class="actions">
                <span class="badge bg-light text-dark">XLSX Preview</span>
                <button onclick="toggleFullscreen()" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-expand"></i> Fullscreen
                </button>
            </div>
        </header>

        <div class="sheet-tabs-container">
            <ul id="sheet-tabs" class="nav nav-tabs" role="tablist"></ul>
        </div>

        <div id="table-container" class="table-container">
            <div id="loader" class="loader">
                <div class="spinner"></div>
                <p>Loading Excel file...</p>
            </div>
        </div>
    </div>
</div>

{{-- Shadow DOM for minimal protected content (optional) --}}
<div id="shadow-host"></div>

{{-- Scripts --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    const XLSX_URL = "{{ route($routeName, ['id' => $id]) }}";
    let workbook = null;

    // DOM Elements
    const $ = (selector) => document.querySelector(selector);
    const $$ = (selector) => document.querySelectorAll(selector);
    const tableContainer = $('#table-container');
    const loader = $('#loader');
    const sheetTabs = $('#sheet-tabs');

    // Load and parse Excel file
    async function loadWorkbook() {
        try {
            showLoader();
            const response = await fetch(XLSX_URL);
            if (!response.ok) throw new Error('Failed to load file');

            const arrayBuffer = await response.arrayBuffer();
            workbook = XLSX.read(arrayBuffer, { 
                type: 'array', 
                cellStyles: true,
                cellDates: true 
            });

            renderSheetTabs();
            renderSheet(0); // First sheet
        } catch (err) {
            showError(err.message);
        } finally {
            hideLoader();
        }
    }

    function showLoader() {
        loader.style.display = 'flex';
    }

    function hideLoader() {
        loader.style.display = 'none';
    }

    function showError(message) {
        tableContainer.innerHTML = `
            <div class="alert alert-danger text-center p-5">
                <h5>Cannot Load Excel File</h5>
                <p class="mb-0">${message}</p>
            </div>`;
    }

    function renderSheetTabs() {
        sheetTabs.innerHTML = '';
        workbook.SheetNames.forEach((name, index) => {
            const isActive = index === 0 ? 'active' : '';
            const tab = `
                <li class="nav-item" role="presentation">
                    <button class="nav-link ${isActive}" 
                            data-index="${index}" 
                            type="button" 
                            role="tab">
                        ${name || `Sheet ${index + 1}`}
                    </button>
                </li>`;
            sheetTabs.insertAdjacentHTML('beforeend', tab);
        });

        // Attach click handlers
        $$('#sheet-tabs .nav-link').forEach(tab => {
            tab.addEventListener('click', () => {
                const idx = parseInt(tab.dataset.index);
                document.querySelector('#sheet-tabs .nav-link.active')?.classList.remove('active');
                tab.classList.add('active');
                renderSheet(idx);
            });
        });
    }

    function renderSheet(sheetIndex) {
        const sheetName = workbook.SheetNames[sheetIndex];
        const worksheet = workbook.Sheets[sheetName];
        const json = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
        if (!json.length) return;

        const { merges = [], '!rows': rows = [], '!cols': cols = [] } = worksheet;

        const table = document.createElement('table');
        table.className = 'excel-table';

        // Dynamic column widths
        if (cols.length) {
            const style = document.createElement('style');
            cols.forEach((col, i) => {
                if (col?.wpx) {
                    style.textContent += `.excel-table th:nth-child(${i + 1}), .excel-table td:nth-child(${i + 1}) { min-width: ${col.wpx}px; width: ${col.wpx}px; }`;
                }
            });
            table.appendChild(style);
        }

        // Header
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        json[0].forEach((cell, i) => {
            const th = document.createElement('th');
            th.textContent = cell ?? '';
            applyCellStyle(th, worksheet, 0, i);
            applyMerge(th, merges, 0, i);
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Body
        const tbody = document.createElement('tbody');
        json.slice(1).forEach((row, rIdx) => {
            const realRow = rIdx + 1;
            const tr = document.createElement('tr');
            if (rows[realRow]?.hpx) tr.style.height = `${rows[realRow].hpx}px`;

            row.forEach((cell, cIdx) => {
                // Skip merged cells (not origin)
                if (isMergedCellCovered(merges, realRow, cIdx)) return;

                const td = document.createElement('td');
                td.textContent = cell ?? '';
                applyCellStyle(td, worksheet, realRow, cIdx);
                applyMerge(td, merges, realRow, cIdx);
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        tableContainer.innerHTML = '';
        tableContainer.appendChild(table);
    }

    function applyCellStyle(el, ws, row, col) {
        const ref = XLSX.utils.encode_cell({ r: row, c: col });
        const cell = ws[ref];
        if (!cell?.s) return;

        if (cell.s.fill?.fgColor?.rgb) {
            el.style.backgroundColor = '#' + cell.s.fill.fgColor.rgb.slice(2);
        }
        if (cell.s.font?.color?.rgb) {
            el.style.color = '#' + cell.s.font.color.rgb.slice(2);
        }
        if (cell.s.font?.bold) el.style.fontWeight = 'bold';
        if (cell.s.alignment?.horizontal) el.style.textAlign = cell.s.alignment.horizontal;
        if (cell.s.alignment?.vertical) el.style.verticalAlign = cell.s.alignment.vertical === 'top' ? 'top' : 'middle';
    }

    function applyMerge(el, merges, row, col) {
        const merge = merges.find(m => m.s.r === row && m.s.c === col);
        if (merge) {
            if (merge.e.c >= merge.s.c) el.colSpan = merge.e.c - merge.s.c + 1;
            if (merge.e.r >= merge.s.r) el.rowSpan = merge.e.r - merge.s.r + 1;
        }
    }

    function isMergedCellCovered(merges, row, col) {
        return merges.some(m => 
            row > m.s.r && row <= m.e.r && 
            col >= m.s.c && col <= m.e.c
        );
    }

    // Fullscreen
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.querySelector('.excel-viewer').requestFullscreen().catch(() => {});
        } else {
            document.exitFullscreen();
        }
    }

    // Protection Enhancements (Client-side only – for deterrence)
    document.addEventListener('keydown', e => {
        if (
            e.key === 'PrintScreen' ||
            (e.ctrlKey && ['p', 's', 'a'].includes(e.key)) ||
            (e.metaKey && ['p', 's'].includes(e.key)) ||
            (e.ctrlKey && e.shiftKey && e.key === 'I')
        ) {
            e.preventDefault();
            alert('This action is restricted.');
        }
    });

    document.addEventListener('contextmenu', e => {
        if (e.target.closest('.excel-table, .viewer-header')) {
            e.preventDefault();
        }
    });

    window.onbeforeprint = () => false;

    // Optional: Blur on tab switch (screenshot deterrent)
    document.addEventListener('visibilitychange', () => {
        document.body.style.filter = document.hidden ? 'blur(10px)' : '';
    });

    // Init
    document.addEventListener('DOMContentLoaded', loadWorkbook);
</script>

{{-- Optimized & Clean CSS --}}
<style>
    :root {
        --primary: #007bff;
        --border: #dee2e6;
        --bg: #f8f9fa;
        --header-bg: #e9ecef;
    }

    .excel-viewer {
        min-height: 100vh;
        background: var(--bg);
        font-family: system-ui, -apple-system, sans-serif;
        position: relative;
        overflow: hidden;
    }

    .viewer-container {
        max-width: 100%;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        height: 100vh;
        padding: 16px;
        box-sizing: border-box;
    }

    .viewer-header {
        background: white;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        z-index: 10;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .title {
        margin: 0;
        color: #28a745;
        font-weight: 600;
    }

    .sheet-tabs-container {
        margin-top: 12px;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .nav-tabs {
        border-bottom: none;
        overflow-x: auto;
        white-space: nowrap;
        padding: 4px;
    }

    .nav-tabs .nav-link {
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        color: #495057;
        font-weight: 500;
    }

    .nav-tabs .nav-link.active {
        background: var(--primary);
        color: white;
    }

    .table-container {
        flex: 1;
        margin-top: 12px;
        background: white;
        border-radius: 12px;
        overflow: auto;
        position: relative;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .excel-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
        table-layout: fixed;
    }

    .excel-table th,
    .excel-table td {
        border: 1px solid var(--border);
        padding: 10px;
        text-align: left;
        vertical-align: middle;
        word-wrap: break-word;
        overflow: hidden;
    }

    .excel-table th {
        background: var(--header-bg);
        position: sticky;
        top: 0;
        z-index: 5;
        font-weight: 600;
    }

    .excel-table tr:nth-child(even) td {
        background: #fdfdfd;
    }

    .loader {
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.95);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 100;
        backdrop-filter: blur(2px);
    }

    .spinner {
        width: 48px;
        height: 48px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid var(--primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 16px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .watermark {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 72px;
        font-weight: bold;
        color: rgba(0,0,0,0.05);
        pointer-events: none;
        z-index: 1;
        user-select: none;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .viewer-header { flex-direction: column; text-align: center; }
        .excel-table { font-size: 12px; }
        .excel-table th, .excel-table td { padding: 6px; }
    }
</style>