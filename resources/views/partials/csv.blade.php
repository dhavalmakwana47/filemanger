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

    <div class="sheet-container">
        <!-- Loading Overlay -->
        <div id="csv-loader" class="loader-overlay">
            <div class="loader-spinner"></div>
            <div class="loader-text">Loading CSV file...</div>
        </div>
        
        <!-- Error Message Container -->
        <div id="csv-error" class="alert alert-danger d-none" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span id="error-message"></span>
        </div>
        
        <!-- CSV Table Container -->
        <div id="csv-table" class="table-responsive"></div>
    </div>
</div>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<!-- PapaParse CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const csvUrl = "{{ route($routeName, ['id' => $id]) }}";
        const csvTable = document.getElementById('csv-table');
        const csvLoader = document.getElementById('csv-loader');
        const csvError = document.getElementById('csv-error');
        const errorMessage = document.getElementById('error-message');

        // Show loader
        function showLoader() {
            if (csvLoader) {
                csvLoader.style.display = 'flex';
                csvLoader.style.opacity = '1';
            }
        }

        // Hide loader
        function hideLoader() {
            if (csvLoader) {
                csvLoader.style.opacity = '0';
                setTimeout(() => {
                    csvLoader.style.display = 'none';
                }, 300);
            }
        }

        // Show error
        function showError(message) {
            console.error('CSV Viewer Error:', message);
            if (errorMessage) errorMessage.textContent = message;
            if (csvError) csvError.classList.remove('d-none');
        }

        // Load and display CSV
        async function loadCSV() {
            showLoader();
            
            try {
                // Fetch CSV file
                const response = await fetch(csvUrl);
                if (!response.ok) {
                    throw new Error(`Failed to load CSV (HTTP ${response.status})`);
                }
                
                const csvText = await response.text();
                
                // Parse CSV
                Papa.parse(csvText, {
                    header: true,
                    skipEmptyLines: true,
                    complete: function(results) {
                        if (!results.meta.fields || results.meta.fields.length === 0) {
                            throw new Error('No headers found in CSV file.');
                        }
                        
                        // Create table
                        const table = document.createElement('table');
                        table.className = 'table table-bordered table-striped table-hover';
                        
                        // Create header
                        const thead = document.createElement('thead');
                        const headerRow = document.createElement('tr');
                        results.meta.fields.forEach(field => {
                            const th = document.createElement('th');
                            th.textContent = field;
                            headerRow.appendChild(th);
                        });
                        thead.appendChild(headerRow);
                        table.appendChild(thead);
                        
                        // Create body
                        const tbody = document.createElement('tbody');
                        results.data.forEach(row => {
                            const tr = document.createElement('tr');
                            results.meta.fields.forEach(field => {
                                const td = document.createElement('td');
                                td.textContent = row[field] || '';
                                tr.appendChild(td);
                            });
                            tbody.appendChild(tr);
                        });
                        table.appendChild(tbody);
                        
                        // Update table in DOM
                        if (csvTable) {
                            csvTable.innerHTML = '';
                            csvTable.appendChild(table);
                        }
                    },
                    error: function(error) {
                        throw new Error(`CSV Parse Error: ${error.message}`);
                    }
                });
                
            } catch (error) {
                console.error('Error:', error);
                showError(error.message || 'Failed to load CSV file. Please try again.');
            } finally {
                hideLoader();
            }
        }

        // Initialize
        loadCSV();
        
        // Fullscreen toggle
        window.toggleCSVFullScreen = function() {
            const wrapper = document.querySelector('.csv-viewer-wrapper');
            if (!document.fullscreenElement) {
                wrapper.requestFullscreen().catch(err => {
                    showError(`Error enabling fullscreen: ${err.message}`);
                });
            } else {
                document.exitFullscreen();
            }
        };
    });
</script>

<style>
    /* Base Styles */
    .csv-viewer-wrapper {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .viewer-header {
        background-color: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 15px;
    }

    .sheet-container {
        flex: 1;
        position: relative;
        min-height: 300px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    /* Loader Styles */
    .loader-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    .loader-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 15px;
    }

    .loader-text {
        color: #555;
        font-weight: 500;
    }

    /* Table Styles */
    #csv-table {
        height: 100%;
        overflow: auto;
        padding: 15px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 12px 15px;
        text-align: left;
        border: 1px solid #dee2e6;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    th {
        background-color: #f8f9fa;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 5;
    }

    tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    /* Animations */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .viewer-header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        th, td {
            padding: 8px 10px;
            font-size: 13px;
        }
    }
</style>
