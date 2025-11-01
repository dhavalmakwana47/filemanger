<!-- Word Viewer with Branding -->
<div class="word-viewer-wrapper">
    <div class="viewer-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            {{-- <img src="{{ $systemSettings['horizontal_logo'] ?? asset('/assets/alais-logo.jpg') }}" alt="Logo" style="height: 40px;"> --}}
            <h5 class="mb-0 text-primary fw-semibold">SmartDoc Viewer</h5>
        </div>
        <span class="text-muted small">.docx Preview</span>
    </div>

    <div id="doc-content" class="doc-body mt-3 rounded shadow-sm"></div>
</div>

<!-- Mammoth.js for DOCX rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
<script>
    const docUrl = "{{ route($routeName, ['id' => $id]) }}";

    fetch(docUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.arrayBuffer();
        })
        .then(data => {
            mammoth.convertToHtml({
                    arrayBuffer: data
                })
                .then(result => {
                    document.getElementById('doc-content').innerHTML = result.value;
                })
                .catch(error => {
                    console.error('Error converting DOCX:', error);
                    document.getElementById('doc-content').innerHTML =
                        `<div class="alert alert-danger">Error rendering DOCX file: ${error.message}</div>`;
                });
        })
        .catch(error => {
            console.error('Error fetching DOCX file:', error);
            document.getElementById('doc-content').innerHTML =
                `<div class="alert alert-danger">Error loading file: ${error.message}</div>`;
        });


    document.addEventListener('contextmenu', function(e) {
        if (e.target.closest('#doc-content')) {
            e.preventDefault();
        }
    });
</script>

<!-- Custom Styling -->
<style>
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
