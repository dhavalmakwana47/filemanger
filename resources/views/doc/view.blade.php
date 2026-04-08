<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->title }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .doc-topbar {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 12px 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
        }
        .doc-topbar .doc-icon { color: #1a73e8; font-size: 26px; }
        .doc-topbar h6 { margin: 0; font-size: 16px; font-weight: 500; color: #202124; }
        .doc-topbar .badge-viewer { font-size: 11px; background: #e8f0fe; color: #1a73e8; padding: 4px 10px; border-radius: 20px; }
        .doc-page-wrap { display: flex; justify-content: center; padding: 40px 16px 80px; }
        .doc-page {
            background: #fff;
            width: 816px;
            min-height: 600px;
            box-shadow: 0 1px 4px rgba(0,0,0,.18), 0 4px 16px rgba(0,0,0,.06);
            border-radius: 2px;
            padding: 72px 96px;
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #202124;
        }

        /* Quill alignment classes */
        .ql-align-center  { text-align: center; }
        .ql-align-right   { text-align: right; }
        .ql-align-justify { text-align: justify; }

        /* Quill font classes */
        .ql-font-arial   { font-family: Arial, sans-serif; }
        .ql-font-times   { font-family: 'Times New Roman', serif; }
        .ql-font-courier { font-family: 'Courier New', monospace; }
        .ql-font-georgia { font-family: Georgia, serif; }
        .ql-font-verdana { font-family: Verdana, sans-serif; }

        /* Quill size classes */
        .ql-size-8pt  { font-size: 8pt; }
        .ql-size-9pt  { font-size: 9pt; }
        .ql-size-10pt { font-size: 10pt; }
        .ql-size-11pt { font-size: 11pt; }
        .ql-size-12pt { font-size: 12pt; }
        .ql-size-14pt { font-size: 14pt; }
        .ql-size-16pt { font-size: 16pt; }
        .ql-size-18pt { font-size: 18pt; }
        .ql-size-24pt { font-size: 24pt; }
        .ql-size-36pt { font-size: 36pt; }
        .ql-size-48pt { font-size: 48pt; }
        .ql-size-72pt { font-size: 72pt; }

        /* Quill heading / inline styles */
        .doc-page p   { margin-bottom: 6px; }
        .doc-page h1  { font-size: 2em; font-weight: 700; }
        .doc-page h2  { font-size: 1.5em; font-weight: 700; }
        .doc-page h3  { font-size: 1.17em; font-weight: 700; }
        .doc-page blockquote { border-left: 4px solid #ccc; padding-left: 16px; color: #5f6368; }
        .doc-page pre  { background: #f1f3f4; padding: 12px; border-radius: 4px; font-family: 'Courier New', monospace; }
        .doc-page ul, .doc-page ol { padding-left: 1.5em; }

        @media (max-width: 900px) {
            .doc-page { width: 100%; padding: 32px 24px; }
        }
    </style>
</head>
<body>
    <div class="doc-topbar">
        <i class="bi bi-file-earmark-text-fill doc-icon"></i>
        <h6>{{ $document->title }}</h6>
        <span class="badge-viewer ms-auto"><i class="bi bi-eye me-1"></i>View only</span>
    </div>

    <div class="doc-page-wrap">
        <div class="doc-page">
            {!! $document->content !!}
        </div>
    </div>
</body>
</html>
