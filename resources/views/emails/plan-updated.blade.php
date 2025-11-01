<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Update Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #1a73e8;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
            line-height: 1.6;
            color: #333333;
        }
        .content h2 {
            font-size: 20px;
            color: #1a73e8;
            margin-top: 0;
        }
        .content ul {
            list-style-type: none;
            padding: 0;
        }
        .content ul li {
            margin-bottom: 10px;
            font-size: 16px;
        }
        .content ul li strong {
            color: #333333;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #666666;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #1a73e8;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .contact-info {
            margin-top: 20px;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            background-color: #1a73e8;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }
        .button:hover {
            background-color: #1557b0;
        }
    </style>
</head>
<body>
    <div class="container">
       
        <div class="content">
            <p>Dear {{ $company->admin->name }},</p>
            <p>We would like to inform you that the Service Provider has extended the date and time for your Data-room Services for your company, <strong>{{ $company->name }}</strong>.</p>
            <h2>Your Account Details</h2>
            <ul>
                <li><strong>Company Name:</strong> {{ $company->name }}</li>
                <li><strong>Email ID:</strong> {{ $company->admin->email }}</li>
                <li><strong>Plan Period:</strong> {{ \Carbon\Carbon::parse($company->start_date)->format('Y-m-d') }} to {{ \Carbon\Carbon::parse($company->end_date)->format('Y-m-d') }}</li>
                <li><strong>Storage:</strong> {{ $company->storage_size_mb ?? 'Not specified' }} MB</li>
                <li><strong>Website Link:</strong> <a href="https://www.datasafehub.in">https://www.datasafehub.in</a></li>
            </ul>
            <p>You may now continue to access and use the services within the updated schedule.</p>
            <p>For any assistance or further queries, please feel free to contact our support team.</p>
            <a href="mailto:info@indiaevoting.com" class="button">Contact Support</a>
        </div>
        <div class="footer">
            <p><strong>Best Regards,</strong></p>
            <p><strong>The Data Safe Hub Team</strong></p>
            <div class="contact-info">
                <p><strong>Dixit Prajapati</strong></p>
                <p>Head of IT/Marketing Department</p>
                <p>Mo: 7990822351 / 7874138237</p>
                <p><strong>Apexrise Consultant and E-Service</strong></p>
                <p>1018, Derasar Vado Khancho, Maneck Chowk</p>
                <p>Ahmedabad, Gujarat, India 380001</p>
                <p>Website: <a href="https://www.datasafehub.in">www.datasafehub.in</a></p>
                <p>Email: <a href="mailto:info@indiaevoting.com">info@indiaevoting.com</a></p>
            </div>
        </div>
    </div>
</body>
</html>