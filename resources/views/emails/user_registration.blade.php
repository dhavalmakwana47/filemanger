<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
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
        <div class="header">
            <h1>{{ $isNewUser ? 'Welcome!' : 'Registration Confirmation' }}</h1>
        </div>
        <div class="content">
            <p>Dear {{ $user->name }},</p>
            <p>You have been added as a user to the Data-room Services for <strong>{{ $companyName }}</strong>.</p>
            <p>Please find your login information below:</p>
            <ul>
                <li><strong>Login URL:</strong> <a href="https://www.datasafehub.in">https://www.datasafehub.in</a></li>
                <li><strong>User ID / Email:</strong> {{ $user->email }}</li>
                @if (isset($password))
                    <li><strong>Password:</strong> {{ $password }}</li>
                @endif
            </ul>
            @if ($isNewUser)
                <p><strong>Security Note:</strong> Please change your password after your first login for security purposes.</p>
            @else
                <p>Please use your existing password to log in.</p>
            @endif
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