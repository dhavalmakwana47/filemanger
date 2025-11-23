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

    <div class="image-body mt-3 rounded shadow-sm bg-white p-3 text-center position-relative">
        <img id="zoomableImg"
             src="{{ route($routeName, ['id' => $id]) }}"
             alt="{{ $fileName }}"
             class="img-fluid rounded shadow"
             style="max-height:80vh; object-fit:contain; cursor:grab;">
        
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
        const body = document.querySelector('.image-body');
        
        let scale = 1;

        const update = () => {
            img.style.transform = `scale(${scale})`;
            if (scale > 1) {
                body.style.overflow = 'auto';
            } else {
                body.style.overflow = 'hidden';
            }
        };

        document.getElementById('zoomInBtn').addEventListener('click', () => {
            scale += 0.3;
            update();
        });

        document.getElementById('zoomOutBtn').addEventListener('click', () => {
            scale = Math.max(1, scale - 0.3);
            update();
        });

        // Wheel zoom with Ctrl
        img.addEventListener('wheel', e => {
            if (e.ctrlKey) {
                e.preventDefault();
                if (e.deltaY > 0) {
                    scale = Math.max(1, scale - 0.3);
                } else {
                    scale += 0.3;
                }
                update();
            }
        });

        // Prevent right-click
        img.addEventListener('contextmenu', e => e.preventDefault());
    });
</script>

<style>
    .image-viewer-wrapper {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        min-height: 100vh;
    }
    .viewer-header {
        background-color: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,.05);
    }
    .image-body {
        overflow: hidden;
        position: relative;
        height: calc(100vh - 160px);
    }
    .image-body img {
        transition: transform .2s ease;
        transform-origin: center center;
    }
    .zoom-controls button {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    @media (max-width: 768px) {
        .zoom-controls {
            flex-direction: row;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
        }
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">