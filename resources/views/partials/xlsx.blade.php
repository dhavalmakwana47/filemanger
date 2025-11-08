<!-- Excel Viewer with Branding, Multi-Sheet, Height, and Color Support -->
<!-- Watermark overlay -->
<div class="watermark">File Manager</div>

<!-- Protected content in Shadow DOM -->
<div class="excel-viewer-wrapper">
    <div class="viewer-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo" style="height: 40px;">
            <h5 class="mb-0 text-success fw-semibold">SmartSheet Viewer</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">.xlsx Preview</span>
            <button onclick="toggleFullScreen()" class="btn btn-sm btn-outline-secondary">Fullscreen</button>
        </div>
    </div>

    <!-- Sheet selection tabs -->
    <div class="sheet-tabs mt-3">
        <ul id="sheet-tabs" class="nav nav-tabs"></ul>
    </div>

    <div id="xlsx-table" class="sheet-body mt-3 rounded shadow-sm bg-white p-3 overflow-auto" style="position: relative; min-height: 300px;">
        <div id="xlsx-loader" class="d-flex justify-content-center align-items-center" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.8);">
            <div class="text-center">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading Excel file, please wait...</p>
            </div>
        </div>
    </div>
</div>
<div id="protected"></div>

<style>
    .spinner-border {
        display: inline-block;
        width: 2rem;
        height: 2rem;
        vertical-align: text-bottom;
        border: 0.25em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border .75s linear infinite;
    }
    @keyframes spinner-border {
        to { transform: rotate(360deg); }
    }
</style>

<!-- XLSX JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    const xlsxUrl = "{{ route($routeName, ['id' => $id]) }}";

    async function loadXLSX() {
        try {
            // Show loader
            document.getElementById('xlsx-loader').style.display = 'flex';
            
            const response = await fetch(xlsxUrl);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = new Uint8Array(await response.arrayBuffer());
            
            // Small delay to ensure loader is visible
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const workbook = XLSX.read(data, { type: 'array', cellStyles: true });

            // Hide loader when done
            document.getElementById('xlsx-loader').style.display = 'none';

            // Get all sheet names
            const sheetNames = workbook.SheetNames;
            const tabsContainer = document.getElementById('sheet-tabs');

            // Create tabs for each sheet
            tabsContainer.innerHTML = '';
            sheetNames.forEach((sheetName, index) => {
                const displayName = sheetName && sheetName.trim() ? sheetName : `Sheet ${index + 1}`;
                const li = document.createElement('li');
                li.classList.add('nav-item');
                const button = document.createElement('button');
                button.classList.add('nav-link');
                if (index === 0) button.classList.add('active');
                button.textContent = displayName;
                button.dataset.sheetIndex = index;
                button.addEventListener('click', () => renderSheet(workbook, sheetName, index));
                li.appendChild(button);
                tabsContainer.appendChild(li);
            });

            // Render the first sheet by default
            if (sheetNames.length > 0) {
                renderSheet(workbook, sheetNames[0], 0);
            } else {
                throw new Error('No sheets found in the workbook');
            }
        } catch (error) {
            console.error('Error loading XLSX:', error);
            document.getElementById('xlsx-loader').style.display = 'none';
            document.getElementById('xlsx-table').innerHTML = `
                <div class="alert alert-danger">
                    <h5>Error loading Excel file</h5>
                    <p class="mb-0">${error.message || 'An error occurred while loading the file.'}</p>
                </div>`;
        }
    }

    function renderSheet(workbook, sheetName, activeIndex) {
        const worksheet = workbook.Sheets[sheetName];
        const merges = worksheet['!merges'] || [];
        const rowHeights = worksheet['!rows'] || [];
        const colWidths = worksheet['!cols'] || [];
        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, raw: false, defval: '' });

        // Update active tab
        document.querySelectorAll('#sheet-tabs .nav-link').forEach((tab, index) => {
            tab.classList.toggle('active', index === activeIndex);
        });

        // Create table
        const table = document.createElement('table');
        table.classList.add('table', 'table-bordered', 'table-striped');

        // Apply column widths (approximate, in pixels)
        if (colWidths.length > 0) {
            const style = document.createElement('style');
            colWidths.forEach((col, index) => {
                if (col && col.wpx) {
                    style.textContent += `
                        #xlsx-table th:nth-child(${index + 1}),
                        #xlsx-table td:nth-child(${index + 1}) {
                            width: ${col.wpx}px;
                        }
                    `;
                }
            });
            table.appendChild(style);
        }

        // Create header
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        jsonData[0].forEach((cell, colIndex) => {
            const th = document.createElement('th');
            th.textContent = cell || '';
            // Apply cell styles (background and font color)
            const cellRef = XLSX.utils.encode_cell({ r: 0, c: colIndex });
            const cellObj = worksheet[cellRef];
            if (cellObj && cellObj.s) {
                if (cellObj.s.fill && cellObj.s.fill.fgColor) {
                    const bgColor = cellObj.s.fill.fgColor.rgb ? `#${cellObj.s.fill.fgColor.rgb.slice(2)}` : '#ffffff';
                    th.style.backgroundColor = bgColor;
                }
                if (cellObj.s.font && cellObj.s.font.color) {
                    const fontColor = cellObj.s.font.color.rgb ? `#${cellObj.s.font.color.rgb.slice(2)}` : '#000000';
                    th.style.color = fontColor;
                }
            }
            // Apply merges to header
            merges.forEach(merge => {
                if (merge.s.r === 0 && merge.s.c === colIndex) {
                    const colspan = merge.e.c - merge.s.c + 1;
                    const rowspan = merge.e.r - merge.s.r + 1;
                    if (colspan > 1) th.setAttribute('colspan', colspan);
                    if (rowspan > 1) th.setAttribute('rowspan', rowspan);
                }
            });
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Create body
        const tbody = document.createElement('tbody');
        jsonData.slice(1).forEach((row, rowIndex) => {
            const tr = document.createElement('tr');
            // Apply row height
            if (rowHeights[rowIndex + 1] && rowHeights[rowIndex + 1].hpx) {
                tr.style.height = `${rowHeights[rowIndex + 1].hpx}px`;
            }
            row.forEach((cell, colIndex) => {
                // Skip cells that are part of a merge (already covered)
                if (merges.some(merge =>
                    rowIndex + 1 >= merge.s.r && rowIndex + 1 <= merge.e.r &&
                    colIndex >= merge.s.c && colIndex <= merge.e.c &&
                    (rowIndex + 1 !== merge.s.r || colIndex !== merge.s.c)
                )) return;

                const td = document.createElement('td');
                td.textContent = cell || '';
                // Apply cell styles
                const cellRef = XLSX.utils.encode_cell({ r: rowIndex + 1, c: colIndex });
                const cellObj = worksheet[cellRef];
                if (cellObj && cellObj.s) {
                    if (cellObj.s.fill && cellObj.s.fill.fgColor) {
                        const bgColor = cellObj.s.fill.fgColor.rgb ? `#${cellObj.s.fill.fgColor.rgb.slice(2)}` : '#ffffff';
                        td.style.backgroundColor = bgColor;
                    }
                    if (cellObj.s.font && cellObj.s.font.color) {
                        const fontColor = cellObj.s.font.color.rgb ? `#${cellObj.s.font.color.rgb.slice(2)}` : '#000000';
                        td.style.color = fontColor;
                    }
                }
                // Apply merges to body cells
                merges.forEach(merge => {
                    if (merge.s.r === rowIndex + 1 && merge.s.c === colIndex) {
                        const colspan = merge.e.c - merge.s.c + 1;
                        const rowspan = merge.e.r - merge.s.r + 1;
                        if (colspan > 1) td.setAttribute('colspan', colspan);
                        if (rowspan > 1) td.setAttribute('rowspan', rowspan);
                    }
                });
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        const tableContainer = document.getElementById('xlsx-table');
        tableContainer.innerHTML = ''; // Clear previous content
        tableContainer.appendChild(table);
    }

    // Load XLSX on page load
    loadXLSX();

    // Disable right-click on table
    document.addEventListener('contextmenu', function (e) {
        if (e.target.closest('#xlsx-table')) {
            e.preventDefault();
            alert('Right-click is disabled to protect content.');
        }
    });

    // Fullscreen toggle
    function toggleFullScreen() {
        const elem = document.querySelector('.excel-viewer-wrapper');
        if (!document.fullscreenElement) {
            elem.requestFullscreen().catch(err => {
                alert(`Error attempting fullscreen: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
        }
    }

    // Shadow DOM for protected content
    const protectedDiv = document.getElementById('protected');
    const shadow = protectedDiv.attachShadow({ mode: 'closed' });
    shadow.innerHTML = `
        <div id="protected-content">
            <h1>Protected Content</h1>
            <p>This content is restricted from printing, extensions, and screenshots.</p>
        </div>
    `;

    // Disable common keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        if (
            (e.ctrlKey && e.key === 'p') || // Ctrl+P (Print)
            (e.ctrlKey && e.shiftKey && e.key === 's') || // Ctrl+Shift+S
            (e.key === 'PrintScreen') // PrintScreen key
        ) {
            e.preventDefault();
            alert('Screenshots and printing are disabled.');
        }
    });

    // Block print attempts
    window.onbeforeprint = function () {
        alert('Printing is disabled on this page.');
        return false;
    };

    // Detect possible screenshot attempts (experimental)
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            console.log('Possible screenshot attempt detected.');
            document.body.style.filter = 'blur(5px)';
            alert('Screenshot attempt detected. Content is protected.');
        } else {
            document.body.style.filter = 'none';
        }
    });

    // Basic extension detection
    window.onload = function () {
        if (document.querySelector('.suspicious-extension-class')) {
            document.body.innerHTML = '<p>Unauthorized extension detected. Access denied.</p>';
        }
    };
</script>

<!-- Optimized Viewer Styles -->
<style>
    .excel-viewer-wrapper {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .viewer-header {
        background-color: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        z-index: 10;
    }

    .sheet-tabs {
        background-color: #fff;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .nav-tabs .nav-link {
        cursor: pointer;
        color: #495057;
        border-radius: 6px;
        padding: 8px 16px;
        margin-right: 5px;
    }

    .nav-tabs .nav-link.active {
        background-color: #007bff;
        color: #fff;
        border-color: #007bff;
    }

    .sheet-body {
        flex-grow: 1;
        overflow: auto;
        max-height: calc(100vh - 200px);
        position: relative;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
        table-layout: auto;
    }

    th,
    td {
        border: 1px solid #dee2e6;
        padding: 10px;
        vertical-align: middle;
        text-align: left;
        min-width: 100px;
        word-wrap: break-word;
    }

    th {
        background-color: #e9ecef;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 5;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    td[colspan],
    th[colspan],
    td[rowspan],
    th[rowspan] {
        text-align: center;
        vertical-align: middle;
    }

    .watermark {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.1;
        font-size: 48px;
        color: #000;
        pointer-events: none;
        z-index: 1000;
        user-select: none;
    }

    @media (max-width: 768px) {
        .viewer-header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        .sheet-tabs {
            padding: 5px;
        }

        .nav-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            white-space: nowrap;
        }

        table {
            font-size: 12px;
        }

        th,
        td {
            padding: 8px;
            min-width: 80px;
        }
    }
</style>