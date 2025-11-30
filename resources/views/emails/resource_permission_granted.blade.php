<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Files/Folders Added - Data-room Services</title>
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
        ol {
            padding-left: 20px;
            margin: 15px 0;
        }
        ol li {
            margin: 5px 0;
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
        }
        .company {
            font-weight: bold;
            color: #2c3e50;
        }
        .contact {
            margin-top: 15px;
            font-size: 12px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <p>Dear User,</p>
        
        <p>We would like to inform you that <strong>{{ $companyName }}</strong> has added new file(s)/folder(s) to your Data-room Services.</p>
        
        <p>You can now access and review the newly added content using your Data-room account.</p>
        
        <p><strong>List of New Files/Folders:</strong></p>
        <ol>
            @foreach (array_merge($folderNames ?? [], $fileNames ?? []) as $index => $name)
                <li>{{ $name }}</li>
            @endforeach
        </ol>
        
        <p>• <strong>Login URL:</strong> <a href="{{ url('/') }}">{{ url('/') }}</a></p>
        
        <p>For any assistance or queries, please feel free to contact our support team.</p>
        
        <p><strong>Best Regards,</strong><br>
        The Data Safe Hub Team</p>
        
        <div class="footer">
            <div class="contact">
                <strong>Dixit Prajapati</strong> (Mo: 7990822351/7874138237)<br>
                Head of IT/Marketing Department<br>
                Apexrise Consultant and E-Service<br>
                1018, Derasar Vado Khancho, Maneck Chowk Ahmedabad<br>
                Gujarat India 380001<br>
                W: <a href="https://www.datasafehub.in">www.datasafehub.in</a> &nbsp; E: <a href="mailto:info@indiaevoting.com">info@indiaevoting.com</a>
            </div>
        </div>
    </div>
</body>
</html>
