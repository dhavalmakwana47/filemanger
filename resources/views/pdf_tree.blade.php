<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>File Tree</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12pt;
            margin: 40px;
            line-height: 1.8;
            color: #333;
        }
        h1 {
            font-size: 16pt;
            color: #2c5282;
            margin-bottom: 30px;
            border-bottom: 2px solid #3182ce;
            padding-bottom: 8px;
        }
        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        li {
            margin: 3px 0;
            padding-left: 20px;
        }
        .indent {
            display: inline-block;
            width: 18px;
        }
        .folder {
            font-weight: bold;
            color: #2c5282;
        }
        .file {
            color: #555;
        }
        .footer {
            margin-top: 50px;
            font-size: 10pt;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>

    <h1>File Manager Tree</h1>

    <ul>
        @foreach($flatTree as $item)
            <li>
                @for($i = 0; $i < $item['depth']; $i++)
                    <span class="indent"></span>
                @endfor

                @if($item['isDirectory'])
                    <span class="folder">Folder: {{ $item['name'] }}</span>
                @else
                    <span class="file">File: {{ $item['name'] }}</span>
                @endif
            </li>
        @endforeach
    </ul>

    <div class="footer">
        Generated on {{ now()->format('M d, Y \a\t h:i A') }}
    </div>

</body>
</html>