<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resource Permission Granted</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #444;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 650px;
            margin: 30px auto;
            background: #ffffff;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        h1 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            display: inline-block;
            padding-bottom: 5px;
        }
        h3 {
            color: #3498db;
            margin: 20px 0 10px 0;
            font-size: 18px;
        }
        ul {
            list-style-type: none;
            padding-left: 0;
            margin: 0 0 15px 0;
        }
        ul li {
            background: #f4f8ff;
            margin: 6px 0;
            padding: 10px 12px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
            font-size: 14px;
        }
        p {
            margin: 10px 0;
            font-size: 15px;
        }
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 13px;
            color: #666;
            text-align: center;
        }
        .footer a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .company {
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Resource Permission Granted</h1>
        <p>Hello,</p>
        <p>You have been granted permissions for the following resources:</p>

        @if (!empty($folderNames))
            <h3>{{ count($folderNames) > 1 ? 'Folders' : 'Folder' }}</h3>
            <ul>
                @foreach ($folderNames as $name)
                    <li>{{ $name }}</li>
                @endforeach
            </ul>
        @endif

        @if (!empty($fileNames))
            <h3>{{ count($fileNames) > 1 ? 'Files' : 'File' }}</h3>
            <ul>
                @foreach ($fileNames as $name)
                    <li>{{ $name }}</li>
                @endforeach
            </ul>
        @endif

        <p>Thank you,<br><span class="company">{{ $companyName }}</span> Team</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            <p>
                Visit us at: <a href="https://datasafehub.in/" target="_blank">https://datasafehub.in/</a>
            </p>
        </div>
    </div>
</body>
</html>
