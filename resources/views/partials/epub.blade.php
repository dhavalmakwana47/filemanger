<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $fileName }} - EPUB Viewer</title>
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- JSZip -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- EPUB.js -->
    <script src="https://cdn.jsdelivr.net/npm/epubjs/dist/epub.min.js"></script>
</head>
<body>
    <!-- EPUB Viewer Container -->
    <div class="epub-viewer-container position-relative p-3 bg-white rounded-3 shadow-sm">
        <!-- Header with Branding -->
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <div class="d-flex align-items-center">
                <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo" class="me-2" style="height: 40px;">
                <span class="fs-4 fw-bold text-primary">SmartView</span>
            </div>
            <span id="section-info" class="badge bg-secondary fs-6"></span>
        </div>

        <!-- EPUB Display Area -->
        <div class="position-relative">
            <div id="epub-viewer" class="w-100 border rounded-3 shadow-sm" style="height: calc(100vh - 200px); overflow-y: auto;"></div>

            <!-- Floating Controls -->
            <div class="epub-controls fixed-bottom d-flex justify-content-center gap-3 mt-3">
                <button id="prev-section" class="btn btn-outline-primary navy-blue rounded-pill px-4" disabled>
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <button id="next-section" class="btn btn-outline-primary navy-blue-600 rounded-pill px-4">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- EPUB.js Script -->
    <script>
        const url = "{{ route($routeName, ['id' => $id, 'model_type' => $modelType]) }}";
        let book = null;
        let rendition = null;

        // Fetch EPUB file as ArrayBuffer
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch EPUB: ${response.statusText}`);
                }
                return response.arrayBuffer();
            })
            .then(arrayBuffer => {
                // Initialize EPUB book with ArrayBuffer
                book = ePub(arrayBuffer);
                rendition = book.renderTo("epub-viewer", {
                    width: "100%",
                    height: "100%",
                    spread: "none"
                });

                // Render the initial section
                return book.ready.then(() => rendition.display());
            })
            .then(() => {
                // Wait for rendering to stabilize
                setTimeout(() => {
                    updateSectionInfo();
                    updateNavigationButtons();
                }, 500);
            })
            .catch(error => {
                console.error('EPUB load error:', error);
                document.body.innerHTML += `<div class="alert alert-danger mt-3">Error: ${error.message}</div>`;
            });

        // Navigation controls
        document.getElementById('next-section').addEventListener('click', () => {
            rendition.next().then(() => {
                console.log('Navigated to next section:', rendition.currentLocation());
                updateSectionInfo();
                updateNavigationButtons();
            }).catch(error => {
                console.error('Next navigation error:', error);
                document.getElementById('section-info').textContent = 'Navigation error';
            });
        });

        document.getElementById('prev-section').addEventListener('click', () => {
            rendition.prev().then(() => {
                console.log('Navigated to previous section:', rendition.currentLocation());
                updateSectionInfo();
                updateNavigationButtons();
            }).catch(error => {
                console.error('Previous navigation error:', error);
                document.getElementById('section-info').textContent = 'Navigation error';
            });
        });

        // Update section information (chapter or section title)
        function updateSectionInfo() {
            book.loaded.navigation.then(nav => {
                const currentLocation = rendition.currentLocation();
                if (currentLocation && currentLocation.start) {
                    const currentCfi = currentLocation.start.cfi;
                    let currentChapter = '';
                    nav.toc.forEach(item => {
                        if (currentLocation.start.cfi.includes(item.href)) {
                            currentChapter = item.label.trim() || 'Unknown Section';
                        }
                    });
                    document.getElementById('section-info').textContent = currentChapter || `Section ${currentLocation.start.index + 1}`;
                } else {
                    document.getElementById('section-info').textContent = 'Loading...';
                }
            }).catch(error => {
                console.error('Navigation load error:', error);
                document.getElementById('section-info').textContent = 'Error loading section';
            });
        }

        // Update navigation button states
        function updateNavigationButtons() {
            book.loaded.navigation.then(() => {
                const currentLocation = rendition.currentLocation();
                if (currentLocation && rendition.location) {
                    document.getElementById('prev-section').disabled = rendition.location.atStart;
                    document.getElementById('next-section').disabled = rendition.location.atEnd;
                } else {
                    document.getElementById('prev-section').disabled = true;
                    document.getElementById('next-section').disabled = true;
                }
            }).catch(error => {
                console.error('Navigation button update error:', error);
                document.getElementById('prev-section').disabled = true;
                document.getElementById('next-section').disabled = true;
            });
        }

        // Prevent right-click on EPUB viewer
        document.getElementById('epub-viewer').addEventListener('contextmenu', e => {
            e.preventDefault();
        });
    </script>

    <!-- Custom Styling -->
    <style>
        .epub-viewer-container {
            max-width: 900px;
            margin: auto;
        }

        .epub-controls button {
            transition: all 0.2s ease;
        }

        .epub-controls button:hover {
            background-color: #0d6efd;
            color: white;
        }

        #epub-viewer {
            overflow-y: auto;
            background: #fff;
            padding: 10px;
        }

        @media (max-width: 576px) {
            .epub-controls {
                flex-direction: column;
            }

            .epub-controls button {
                width: 100%;
            }
        }
    </style>
</body>
</html>