<!-- YouTube Video Viewer -->
<div class="video-viewer-wrapper">
    <div class="viewer-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo" style="height: 40px;">
            <h5 class="mb-0 text-primary fw-semibold">Video Player</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">YOUTUBE Preview</span>
            <button onclick="toggleVideoFullScreen()" class="btn btn-sm btn-outline-secondary">Fullscreen</button>
        </div>
    </div>

    <div class="video-body mt-3 rounded shadow-sm bg-white p-3">
        <div id="youtube-container" class="w-100 rounded overflow-hidden" style="aspect-ratio: 16/9;">
            <iframe id="youtube-player"
                width="100%" height="100%"
                src="{{$link}}"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        </div>
    </div>
</div>

<script>
    // Fullscreen toggle for iframe
    function toggleVideoFullScreen() {
        const iframeContainer = document.getElementById('youtube-container');
        if (!document.fullscreenElement) {
            iframeContainer.requestFullscreen().catch(err => {
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

    #youtube-container {
        width: 100%;
        max-width: 100%;
        background-color: #000;
        border: 1px solid #ccc;
        border-radius: 8px;
    }

    @media (max-width: 768px) {
        .viewer-header {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }
    }
</style>
