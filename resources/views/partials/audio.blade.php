<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $fileName }} - Audio Viewer</title>
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Audio Viewer Container -->
    <div class="audio-viewer-container position-relative p-3 bg-white rounded-3 shadow-sm">
        <!-- Header with Branding -->
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <div class="d-flex align-items-center">
                <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo" class="me-2" style="height: 40px;">
                <span class="fs-4 fw-bold text-primary">SmartView</span>
            </div>
            <span id="audio-info" class="badge bg-secondary fs-6">{{ $fileName }}</span>
        </div>

        <!-- Audio Player -->
        <div class="position-relative">
            <audio id="audio-player" class="w-100" controls>
                <source src="{{ route($routeName, ['id' => $id]) }}" type="{{ $mimeType }}">
                Your browser does not support the audio element.
            </audio>

            <!-- Playback Controls -->
            <div class="audio-controls d-flex justify-content-center gap-3 mt-3">
                <button id="play-pause" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-play-fill"></i> Play
                </button>
                <button id="mute" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-volume-up-fill"></i> Mute
                </button>
                <input type="range" id="volume" class="form-range w-25" min="0" max="1" step="0.1" value="1">
            </div>

            <!-- Seek Bar -->
            <div class="seek-bar mt-3">
                <input type="range" id="seek-bar" class="form-range w-100" min="0" max="100" value="0">
                <div class="d-flex justify-content-between">
                    <span id="current-time">0:00</span>
                    <span id="duration">0:00</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio Player Script -->
    <script>
        const audio = document.getElementById('audio-player');
        const playPauseBtn = document.getElementById('play-pause');
        const muteBtn = document.getElementById('mute');
        const volumeSlider = document.getElementById('volume');
        const seekBar = document.getElementById('seek-bar');
        const currentTime = document.getElementById('current-time');
        const duration = document.getElementById('duration');

        // Format time in MM:SS
        function formatTime(seconds) {
            const min = Math.floor(seconds / 60);
            const sec = Math.floor(seconds % 60);
            return `${min}:${sec < 10 ? '0' : ''}${sec}`;
        }

        // Update seek bar and time display
        audio.addEventListener('loadedmetadata', () => {
            seekBar.max = audio.duration;
            duration.textContent = formatTime(audio.duration);
        });

        audio.addEventListener('timeupdate', () => {
            seekBar.value = audio.currentTime;
            currentTime.textContent = formatTime(audio.currentTime);
        });

        // Play/Pause toggle
        playPauseBtn.addEventListener('click', () => {
            if (audio.paused) {
                audio.play();
                playPauseBtn.innerHTML = '<i class="bi bi-pause-fill"></i> Pause';
            } else {
                audio.pause();
                playPauseBtn.innerHTML = '<i class="bi bi-play-fill"></i> Play';
            }
        });

        // Mute/Unmute toggle
        muteBtn.addEventListener('click', () => {
            audio.muted = !audio.muted;
            muteBtn.innerHTML = audio.muted
                ? '<i class="bi bi-volume-mute-fill"></i> Unmute'
                : '<i class="bi bi-volume-up-fill"></i> Mute';
        });

        // Volume control
        volumeSlider.addEventListener('input', () => {
            audio.volume = volumeSlider.value;
            audio.muted = audio.volume === 0;
            muteBtn.innerHTML = audio.muted
                ? '<i class="bi bi-volume-mute-fill"></i> Unmute'
                : '<i class="bi bi-volume-up-fill"></i> Mute';
        });

        // Seek control
        seekBar.addEventListener('input', () => {
            audio.currentTime = seekBar.value;
        });

        // Error handling
        audio.addEventListener('error', () => {
            console.error('Audio load error:', audio.error);
            document.body.innerHTML += `<div class="alert alert-danger mt-3">Error: Failed to load audio file</div>`;
        });

        // Prevent right-click on audio player
        audio.addEventListener('contextmenu', e => {
            e.preventDefault();
        });
    </script>

    <!-- Custom Styling -->
    <style>
        .audio-viewer-container {
            max-width: 900px;
            margin: auto;
        }

        .audio-controls button {
            transition: all 0.2s ease;
        }

        .audio-controls button:hover {
            background-color: #0d6efd;
            color: white;
        }

        #audio-player {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .seek-bar {
            max-width: 100%;
        }

        @media (max-width: 576px) {
            .audio-controls {
                flex-direction: column;
            }

            .audio-controls button,
            .audio-controls input {
                width: 100%;
            }

            .audio-controls input {
                margin-top: 10px;
            }
        }
    </style>
</body>
</html>
