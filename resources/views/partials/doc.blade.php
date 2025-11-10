<!-- Word Viewer with Branding -->
<div class="word-viewer-wrapper">
    <div class="viewer-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            {{-- <img src="{{ $systemSettings['horizontal_logo'] ?? asset('/assets/alais-logo.jpg') }}" alt="Logo" style="height: 40px;"> --}}
            <h5 class="mb-0 text-primary fw-semibold">SmartDoc Viewer</h5>
        </div>
        <span class="text-muted small">.docx Preview</span>
    </div>

    <div class="doc-body mt-3 rounded shadow-sm position-relative" style="min-height: 300px;">
        <!-- Loading Overlay -->
        <div id="doc-loader" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white" style="z-index: 1;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <!-- Content -->
        <div id="doc-content" class="position-relative" style="z-index: 2; min-height: 300px;"></div>
    </div>
</div>

<!-- Mammoth.js for DOCX rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
<script>
    const docUrl = "{{ route($routeName, ['id' => $id]) }}";

    // Wait for the DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Get references to DOM elements
        const docLoader = document.getElementById('doc-loader');
        const docContent = document.getElementById('doc-content');
        
        // Show loader immediately
        if (docLoader) {
            docLoader.style.display = 'flex';
            docLoader.style.opacity = '1';
        }
        if (docContent) {
            docContent.style.display = 'none';
        }

        // Function to handle errors
        function showError(message) {
            console.error('DOCX Viewer Error:', message);
            const errorMessage = `
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    ${message || 'Error loading document. Please try again or contact support.'}
                </div>`;
            
            if (docContent) {
                docContent.innerHTML = errorMessage;
                docContent.style.display = 'block';
            }
        }

        // Function to hide loader with transition
        function hideLoader() {
            return new Promise((resolve) => {
                if (docLoader) {
                    // Add fade out effect
                    docLoader.style.transition = 'opacity 0.3s ease';
                    docLoader.style.opacity = '0';
                    
                    // Remove from layout after fade out
                    setTimeout(() => {
                        docLoader.style.display = 'none';
                        resolve();
                    }, 300);
                } else {
                    resolve();
                }
            });
        }

        // Main document loading logic
        (async function() {
            try {
                console.log('Starting document load...');
                
                // 1. Fetch the document
                console.log('Fetching document from:', docUrl);
                const response = await fetch(docUrl);
                if (!response.ok) {
                    throw new Error(`Failed to load document (HTTP ${response.status})`);
                }

                // 2. Convert to ArrayBuffer
                console.log('Converting response to ArrayBuffer...');
                const arrayBuffer = await response.arrayBuffer();
                
                // 3. Convert DOCX to HTML
                console.log('Converting DOCX to HTML...');
                const result = await mammoth.convertToHtml({ arrayBuffer });
                
                // 4. Display the content
                if (docContent) {
                    console.log('Rendering content...');
                    docContent.innerHTML = result.value;
                    // Wait for the next frame to ensure content is rendered
                    await new Promise(resolve => requestAnimationFrame(resolve));
                }
            } catch (error) {
                console.error('Error in document loading:', error);
                showError(error.message);
            } finally {
                console.log('Hiding loader...');
                await hideLoader();
                if (docContent) {
                    docContent.style.display = 'block';
                }
                console.log('Done.');
            }
        })();
    });


    document.addEventListener('contextmenu', function(e) {
        if (e.target.closest('#doc-content')) {
            e.preventDefault();
        }
    });
</script>

<!-- Custom Styling -->
<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .spinner-border {
        width: 3rem;
        height: 3rem;
        border-width: 0.25em;
        animation: spin 0.75s linear infinite;
    }

    .word-viewer-wrapper {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        min-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    .viewer-header {
        background-color: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .doc-body {
        flex-grow: 1;
        background-color: #ffffff;
        border: 1px solid #dee2e6;
        padding: 25px;
        overflow-y: auto;
        overflow-x: auto;
        max-height: calc(100vh - 160px);
    }

    #doc-content p {
        margin: 0 0 12px;
        line-height: 1.6;
    }

    #doc-content table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }

    #doc-content th,
    #doc-content td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    #doc-content th {
        background-color: #f1f3f5;
        font-weight: 600;
    }

    #doc-content img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 10px 0;
    }

    @media (max-width: 768px) {
        .viewer-header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
    }
</style>
