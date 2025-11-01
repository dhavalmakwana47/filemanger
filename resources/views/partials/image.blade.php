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

    <div class="image-body mt-3 rounded shadow-sm bg-white p-3 text-center">
        <img src="{{ route($routeName, ['id' => $id]) }}" alt="{{ $fileName }}" class="img-fluid rounded shadow">
    </div>
</div>

<script>
    document.addEventListener('contextmenu', function(e) {
        if (e.target.tagName === 'IMG') {
            e.preventDefault();
        }
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
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .image-body img {
        max-height: 80vh;
        object-fit: contain;
    }

    @media (max-width: 768px) {
        .viewer-header {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }
    }
</style>
