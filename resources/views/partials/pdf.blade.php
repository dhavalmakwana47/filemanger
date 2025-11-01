<!-- PDF Viewer Container -->
<div class="pdf-viewer-container position-relative p-3 bg-white rounded-3 shadow-sm">
    <!-- Print Warning Overlay -->
    <div id="print-warning" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:9999;color:#fff;align-items:center;justify-content:center;font-size:2rem;text-align:center;">
        Printing is disabled for this document.<br>Please contact the administrator for access.
    </div>
    <!-- Header with Branding -->
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <div class="d-flex align-items-center">
            <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo"
                class="me-2" style="height: 40px;">
            <span class="fs-4 fw-bold text-primary">SmartView</span>
        </div>
        <span id="page-info" class="badge bg-secondary fs-6"></span>
    </div>
    <!-- PDF Canvas -->
    <div class="position-relative">
        <canvas id="pdf-canvas" class="w-100 border rounded-3 shadow-sm"></canvas>
        <!-- Floating Controls -->
        <div class="pdf-controls d-flex justify-content-center gap-3 mt-3">
            <button id="prev-page" class="btn btn-outline-primary rounded-pill px-4" disabled>
                <i class="bi bi-chevron-left"></i> Prev
            </button>
            <button id="next-page" class="btn btn-outline-primary rounded-pill px-4">
                Next <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
</div>
<!-- Bootstrap & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
<!-- PDF.js Script -->
<script>
    const url = "{{ route($routeName, ['id' => $id]) }}";
    let currentPage = 1;
    let pdfDoc = null;
    const scale = 1.4;

    pdfjsLib.getDocument(url).promise.then(pdf => {
        pdfDoc = pdf;
        const numPages = pdf.numPages;
        updatePageInfo();
        renderPage(currentPage);

        document.getElementById('next-page').addEventListener('click', () => {
            if (currentPage < numPages) {
                currentPage++;
                renderPage(currentPage);
                updatePageInfo();
            }
        });

        document.getElementById('prev-page').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
                updatePageInfo();
            }
        });

        function renderPage(pageNum) {
            pdfDoc.getPage(pageNum).then(page => {
                const canvas = document.getElementById('pdf-canvas');
                const context = canvas.getContext('2d');
                const viewport = page.getViewport({
                    scale: scale
                });
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                page.render({
                    canvasContext: context,
                    viewport: viewport
                });

                document.getElementById('prev-page').disabled = pageNum === 1;
                document.getElementById('next-page').disabled = pageNum === numPages;
            });
        }

        function updatePageInfo() {
            document.getElementById('page-info').textContent = `Page ${currentPage} of ${pdfDoc.numPages}`;
        }
    }).catch(error => {
        console.error('PDF load error:', error);
        document.body.innerHTML += `<div class="alert alert-danger mt-3">Error: ${error.message}</div>`;
    });

    // Prevent right-click, F12, Ctrl+Shift+I, Ctrl+U, Ctrl+P, and context menu
    document.addEventListener('contextmenu', e => {
        if (e.target.tagName === 'CANVAS') e.preventDefault();
    });
    document.addEventListener('keydown', function(e) {
        // F12, Ctrl+Shift+I, Ctrl+U, Ctrl+P
        if (
            e.key === 'F12' ||
            (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i')) ||
            (e.ctrlKey && (e.key === 'U' || e.key === 'u')) ||
            (e.ctrlKey && (e.key === 'P' || e.key === 'p'))
        ) {
            e.preventDefault();
            if (e.key === 'P' || e.key === 'p') showPrintWarning();
            return false;
        }
    });
    // Show warning overlay on print
    function showPrintWarning() {
        const warning = document.getElementById('print-warning');
        if (warning) {
            warning.style.display = 'flex';
            setTimeout(() => { warning.style.display = 'none'; }, 4000);
        }
    }
    // Listen for print events
    window.onbeforeprint = function() {
        showPrintWarning();
        setTimeout(() => { window.stop(); }, 100); // Try to stop print
        return false;
    };
</script>
<!-- Custom Styling -->
<style>
    .pdf-viewer-container {
        max-width: 900px;
        margin: auto;
    }

    .pdf-controls button {
        transition: all 0.2s ease;
    }

    .pdf-controls button:hover {
        background-color: #0d6efd;
        color: white;
    }

    @media (max-width: 576px) {
        .pdf-controls {
            flex-direction: column;
        }

        .pdf-controls button {
            width: 100%;
        }
    }

    /* Prevent printing */
    @media print {
        body { display: none !important; }
    }
</style>
