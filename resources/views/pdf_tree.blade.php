<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Directory Structure</title>
    <style>
        @page { margin: 40px; size: A4 landscape; }

        body {
            font-family: 'DejaVu Sans', 'Segoe UI', sans-serif;
            font-size: 11pt;
            line-height: 1.7;
            color: #2d3748;
            background: #f8fafc;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #4299e1;
        }

        .header h1 {
            font-size: 26pt;
            color: #2b6cb0;
            margin: 0;
        }

        .header p {
            color: #718096;
            margin: 10px 0 0;
            font-size: 12pt;
        }

        .tree {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            font-family: 'Courier New', 'DejaVu Sans Mono', monospace;
            font-size: 11.5pt;
            line-height: 1.6;
            white-space: pre; /* Critical for alignment */
            overflow-x: auto;
        }

        .tree-line {
            color: #4a5568;
        }

        .folder {
            color: #2c5282;
            font-weight: bold;
        }

        .file {
            color: #555;
        }

        .ext-pdf { color: #e53e3e; }
        .ext-img { color: #3182ce; }
        .ext-code { color: #6d28d9; }
        .ext-archive { color: #dd6b20; }

        .stats {
            margin: 20px 0;
            padding: 12px 20px;
            background: #edf2f7;
            border-radius: 8px;
            display: inline-block;
            font-size: 11pt;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            color: #718096;
            font-size: 10pt;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Directory Structure</h1>
        <p>Complete file & folder tree • {{ now()->format('F d, Y \a\t g:i A') }}</p>
    </div>

    <div class="stats">
        Folders: <strong>{{ collect($flatTree)->where('isDirectory', true)->count() }}</strong> | 
        Files: <strong>{{ collect($flatTree)->where('isDirectory', false)->count() }}</strong>
    </div>

    <div class="tree">
@php
    $lines = [];
    $prefixStack = [];

    foreach ($flatTree as $index => $item) {
        $depth = $item['depth'];
        $isLast = !isset($flatTree[$index + 1]) || $flatTree[$index + 1]['depth'] <= $depth;
        $name = $item['name'];
        $isDir = $item['isDirectory'];

        // Build prefix (├──, └──, │   )
        $prefix = '';
        for ($i = 0; $i < $depth; $i++) {
            $prefix .= isset($prefixStack[$i]) && $prefixStack[$i] ? '    ' : '│   ';
        }

        // Current item connector
        $connector = $isLast ? '└── ' : '├── ';
        $line = $prefix . $connector;

        // Update prefix stack
        $prefixStack[$depth] = !$isLast;
        while (count($prefixStack) > $depth + 1) {
            array_pop($prefixStack);
        }

        // File type styling
        if (!$isDir) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $extClass = match($ext) {
                'pdf' => 'ext-pdf',
                'jpg','jpeg','png','gif','svg','webp' => 'ext-img',
                'php','js','html','css','json','blade.php','ts','py','java' => 'ext-code',
                'zip','rar','7z','tar','gz' => 'ext-archive',
                default => ''
            };
            $name = htmlspecialchars($name);
            $nameHtml = $extClass ? "<span class=\"$extClass\">$name</span>" : $name;
        } else {
            $nameHtml = "<span class=\"folder\">$name</span>";
        }

        $lines[] = "<span class=\"tree-line\">$line</span>" . $nameHtml;
    }

    echo implode("\n", $lines);
@endphp
    </div>

    <div class="footer">
        Generated on {{ now()->format('l, F j, Y \a\t h:i:s A') }}
    </div>
</div>

</body>
</html>