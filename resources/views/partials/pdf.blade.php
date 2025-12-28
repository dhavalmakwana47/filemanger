<!-- PDF Viewer Container -->
<div class="pdf-viewer-container position-relative p-3 bg-white rounded-3 shadow-sm">
    <!-- Password Modal -->
    <div id="password-modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center;">
        <div class="bg-white p-4 rounded-3 shadow" style="max-width:400px;width:90%;">
            <h5 class="mb-3">Password Required</h5>
            <p class="mb-3">This PDF is password protected. Please enter the password:</p>
            <div id="password-error" class="alert alert-danger" style="display:none;">Incorrect password. Please try again.</div>
            <input type="password" id="pdf-password" class="form-control mb-3" placeholder="Enter password">
            <div class="d-flex gap-2">
                <button id="submit-password" class="btn btn-primary">Submit</button>
                <button id="cancel-password" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
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
        <div id="pdf-loader" style="display:none;position:absolute;inset:0;z-index:10;background:rgba(255,255,255,0.6);align-items:center;justify-content:center;">
            <div class="spinner-border text-primary" role="status" aria-label="Loading"></div>
        </div>
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
    let url = "{{ route($routeName, ['id' => $id]) }}";
    let currentPage = 1;
    let pdfDoc = null;
    const scale = 1.4;
    const loader = document.getElementById('pdf-loader');
    let navigationSetup = false;
    
    function showLoader() { if (loader) loader.style.display = 'flex'; }
    function hideLoader() { if (loader) loader.style.display = 'none'; }
    showLoader();

    let passwordAttempted = false;
    
    function loadPDF(pdfUrl) {
        pdfjsLib.getDocument({
            url: pdfUrl,
            password: window.pdfPassword || ''
        }).promise.then(pdf => {
            pdfDoc = pdf;
            updatePageInfo();
            renderPage(currentPage);
            if (!navigationSetup) {
                setupNavigation();
                navigationSetup = true;
            }
            hideLoader();
        }).catch(error => {
            console.error('PDF load error:', error);
            if (error.name === 'PasswordException' || error.message.includes('password')) {
                if (window.pdfPassword) {
                    // Wrong password
                    document.getElementById('password-error').style.display = 'block';
                    showPasswordModal();
                } else {
                    // First time password request
                    passwordAttempted = true;
                    showPasswordModal();
                }
            } else {
                document.body.innerHTML += '<div class="alert alert-danger mt-3">Error loading PDF: ' + error.message + '</div>';
            }
            hideLoader();
        });
    }

    function setupNavigation() {
        document.getElementById('next-page').onclick = () => {
            if (currentPage < pdfDoc.numPages) {
                currentPage++;
                renderPage(currentPage);
                updatePageInfo();
            }
        };

        document.getElementById('prev-page').onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
                updatePageInfo();
            }
        };
    }

    function renderPage(pageNum) {
        showLoader();
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        prevBtn.disabled = true;
        nextBtn.disabled = true;
        
        pdfDoc.getPage(pageNum).then(page => {
            const canvas = document.getElementById('pdf-canvas');
            const context = canvas.getContext('2d');
            const viewport = page.getViewport({ scale: scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            page.render({
                canvasContext: context,
                viewport: viewport
            }).promise.then(() => {
                prevBtn.disabled = pageNum === 1;
                nextBtn.disabled = pageNum === pdfDoc.numPages;
                hideLoader();
            });
        });
    }

    function updatePageInfo() {
        document.getElementById('page-info').textContent = `Page ${currentPage} of ${pdfDoc.numPages}`;
    }

    function showPasswordModal() {
        document.getElementById('password-modal').style.display = 'flex';
        document.getElementById('pdf-password').focus();
    }

    function hidePasswordModal() {
        document.getElementById('password-modal').style.display = 'none';
        document.getElementById('password-error').style.display = 'none';
        document.getElementById('pdf-password').value = '';
    }

    // Password modal event listeners
    document.getElementById('submit-password').onclick = () => {
        const password = document.getElementById('pdf-password').value;
        if (password) {
            window.pdfPassword = password;
            hidePasswordModal();
            showLoader();
            passwordAttempted = false;
            loadPDF(url);
        }
    };

    document.getElementById('cancel-password').onclick = () => {
        hidePasswordModal();
        document.body.innerHTML += '<div class="alert alert-warning mt-3">PDF viewing cancelled - password required.</div>';
    };

    document.getElementById('pdf-password').onkeypress = (e) => {
        if (e.key === 'Enter') {
            document.getElementById('submit-password').click();
        }
    };

    // Initial load
    loadPDF(url);

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