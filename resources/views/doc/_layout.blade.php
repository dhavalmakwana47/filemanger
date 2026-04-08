<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'View Document')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .doc-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.10); padding: 40px; width: 100%; max-width: 440px; }
        .doc-card .doc-icon { font-size: 40px; color: #1a73e8; }
        .doc-card h5 { font-weight: 600; color: #202124; }
        .doc-card .doc-title { font-size: 13px; color: #5f6368; margin-bottom: 24px; }
        .btn-primary { background: #1a73e8; border-color: #1a73e8; }
        .btn-primary:hover { background: #1557b0; border-color: #1557b0; }
    </style>
</head>
<body>
    <div class="doc-card">
        <div class="text-center mb-3">
            <i class="bi bi-file-earmark-text-fill doc-icon"></i>
        </div>
        @yield('content')
    </div>
</body>
</html>
