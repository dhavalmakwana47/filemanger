<!-- Video Viewer -->
<div class="video-viewer-wrapper">
    <div class="viewer-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo" style="height: 40px;">
            <h5 class="mb-0 text-primary fw-semibold">Video Player</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">{{ strtoupper(pathinfo($fileName, PATHINFO_EXTENSION)) }} Preview</span>
            <button onclick="toggleVideoFullScreen()" class="btn btn-sm btn-outline-secondary">Fullscreen</button>
        </div>
    </div>

    <div class="video-body mt-3 rounded shadow-sm bg-white p-3">
        <video id="video-player" controls controlsList="nodownload" class="w-100 rounded">
            <source src="{{ route($routeName, ['id' => $id]) }}" type="{{ $mimeType }}">
            Your browser does not support the video tag.
        </video>
    </div>
</div>

<script>
    // Disable right-click on video
    document.addEventListener('contextmenu', function (e) {
        if (e.target.tagName === 'VIDEO') {
            e.preventDefault();
        }
    });

    // Fullscreen toggle
    function toggleVideoFullScreen() {
        const videoContainer = document.querySelector('.video-viewer-wrapper');
        if (!document.fullscreenElement) {
            videoContainer.requestFullscreen().catch(err => {
                alert(`Error attempting to enable fullscreen mode: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
        }
    }
</script>

<style>
    .video-viewer-wrapper {
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

    .video-body {
        flex-grow: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        max-height: calc(100vh - 160px);
    }

    video {
        max-width: 100%;
        height: auto;
        border: 1px solid #ccc;
        background-color: #000;
    }

    @media (max-width: 768px) {
        .viewer-header {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }
    }
</style>
