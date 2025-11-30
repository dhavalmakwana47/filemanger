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

    <div class="image-container">
        <div class="image-viewport">
            <img id="zoomableImg" 
                 src="{{ route($routeName, ['id' => $id]) }}" 
                 alt="{{ $fileName }}">
        </div>
        
        <!-- Zoom Controls -->
        <div class="zoom-controls">
            <button type="button" id="zoomInBtn" class="btn btn-sm btn-primary" title="Zoom In">
                <i class="bi bi-plus-lg"></i>
            </button>
            <button type="button" id="zoomOutBtn" class="btn btn-sm btn-primary" title="Zoom Out">
                <i class="bi bi-dash-lg"></i>
            </button>
            <button type="button" id="resetBtn" class="btn btn-sm btn-secondary" title="Reset">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const img = document.getElementById('zoomableImg');
    const viewport = document.querySelector('.image-viewport');
    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let startX, startY, initialTranslateX, initialTranslateY;

    function updateTransform() {
        img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
    }

    // Zoom In
    document.getElementById('zoomInBtn').addEventListener('click', () => {
        scale += 0.25;
        updateTransform();
    });

    // Zoom Out
    document.getElementById('zoomOutBtn').addEventListener('click', () => {
        scale = Math.max(0.5, scale - 0.25);
        updateTransform();
    });

    // Reset
    document.getElementById('resetBtn').addEventListener('click', () => {
        scale = 1;
        translateX = 0;
        translateY = 0;
        updateTransform();
    });

    // Mouse wheel zoom
    viewport.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.1 : 0.1;
        scale = Math.max(0.5, Math.min(5, scale + delta));
        updateTransform();
    });

    // Mouse drag
    img.addEventListener('mousedown', (e) => {
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        initialTranslateX = translateX;
        initialTranslateY = translateY;
        img.style.cursor = 'grabbing';
        e.preventDefault();
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        translateX = initialTranslateX + (e.clientX - startX);
        translateY = initialTranslateY + (e.clientY - startY);
        updateTransform();
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
        img.style.cursor = 'grab';
    });

    // Prevent context menu
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
    margin-bottom: 20px;
}

.image-container {
    position: relative;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,.05);
    height: calc(100vh - 200px);
}

.image-viewport {
    width: 100%;
    height: 100%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    border-radius: 8px;
}

.image-viewport img {
    max-width: 90%;
    max-height: 90%;
    cursor: grab;
    transform-origin: center center;
    transition: transform 0.1s ease;
}

.zoom-controls {
    position: absolute;
    bottom: 20px;
    right: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 10;
}

.zoom-controls button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .zoom-controls {
        flex-direction: row;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        right: auto;
    }
}
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">