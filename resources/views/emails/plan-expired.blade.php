<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data-room Service Expiry Reminder</title>
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
            background-color: #d32f2f;
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
            color: #d32f2f;
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
            color: #d32f2f;
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
            background-color: #d32f2f;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }
        .button:hover {
            background-color: #b71c1c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Data-room Service Expiry Reminder</h1>
        </div>
        <div class="content">
            <p>Dear {{ $company->admin->name }},</p>
            <p>This is a gentle reminder that your Data-room Service for <strong>{{ $company->name }}</strong> is set to expire on {{ \Carbon\Carbon::parse($company->end_date)->format('Y-m-d') }}.</p>
            <h2>Your Account Details</h2>
            <ul>
                <li><strong>Company Name:</strong> {{ $company->name }}</li>
                <li><strong>Email ID:</strong> {{ $company->admin->email }}</li>
                <li><strong>Plan Period:</strong> {{ \Carbon\Carbon::parse($company->start_date)->format('Y-m-d') }} to {{ \Carbon\Carbon::parse($company->end_date)->format('Y-m-d') }}</li>
                <li><strong>Storage:</strong> {{ $company->storage_size_mb ?? 'Not specified' }} MB</li>
                <li><strong>Website Link:</strong> <a href="https://www.datasafehub.in">https://www.datasafehub.in</a></li>
            </ul>
            <p>To ensure uninterrupted access, please take the necessary steps to renew or extend your service before the expiry date and time.</p>
            <p>Please send an email to <a href="mailto:info@indiaevoting.com">info@indiaevoting.com</a> for your extension or contact Mr. Dixit Prajapati at 7874138237.</p>
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