<!-- Image Viewer -->
<div class="image-viewer-wrapper">
    <div class="viewer-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo" style="height: 40px;">
            <h5 class="mb-0 text-primary fw-semibold">Image Preview</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">{{ strtoupper(pathinfo($fileName, PATHINFO_EXTENSION)) }} Preview</span>
        </div>
    </div>

    <div class="image-body mt-3 rounded shadow-sm bg-white p-3 text-center position-relative overflow-hidden">
        <img id="zoomableImg"
             src="{{ route($routeName, ['id' => $id]) }}"
             alt="{{ $fileName }}"
             class="img-fluid rounded shadow transition-transform"
             style="max-height:80vh;object-fit:contain;transform:scale(1);">
        
        <!-- Zoom Controls -->
        <div class="zoom-controls position-absolute bottom-0 end-0 m-3 d-flex flex-column gap-2">
            <button type="button" id="zoomInBtn" class="btn btn-sm btn-primary rounded-circle shadow" title="Zoom In">
                <i class="bi bi-plus-lg"></i>
            </button>
            <button type="button" id="zoomOutBtn" class="btn btn-sm btn-primary rounded-circle shadow" title="Zoom Out">
                <i class="bi bi-dash-lg"></i>
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const img = document.getElementById('zoomableImg');
        const zoomInBtn  = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');

        let scale = 1;
        const step = 0.25;
        const minScale = 0.5;
        const maxScale = 5;

        const updateTransform = () => {
            img.style.transform = `scale(${scale})`;
        };

        zoomInBtn.addEventListener('click', () => {
            if (scale < maxScale) {
                scale = Math.min(scale + step, maxScale);
                updateTransform();
            }
        });

        zoomOutBtn.addEventListener('click', () => {
            if (scale > minScale) {
                scale = Math.max(scale - step, minScale);
                updateTransform();
            }
        });

        // Optional: wheel zoom (hold Ctrl to avoid page scroll)
        img.addEventListener('wheel', e => {
            if (e.ctrlKey) {
                e.preventDefault();
                const delta = e.deltaY > 0 ? -step : step;
                scale = Math.min(Math.max(scale + delta, minScale), maxScale);
                updateTransform();
            }
        });

        // Prevent right-click on the image (keeps your original context-menu block)
        document.addEventListener('contextmenu', e => {
            if (e.target.tagName === 'IMG') e.preventDefault();
        });
    });
</script>

<style>
    .image-viewer-wrapper {
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
        box-shadow: 0 2px 6px rgba(0,0,0,.05);
    }

    .image-body {
        position: relative;
    }

    .image-body img {
        transition: transform .2s ease;
    }

    .zoom-controls button {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    @media (max-width: 768px) {
        .viewer-header {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }
        .zoom-controls {
            flex-direction: row;
            bottom: auto;
            top: 50%;
            transform: translateY(-50%);
            right: 10px;
        }
    }
</style>

<!-- Bootstrap Icons (if not already included) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">