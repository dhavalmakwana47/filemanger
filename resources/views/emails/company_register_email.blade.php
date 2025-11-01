<!DOCTYPE html>
<html>
<head>
    <title>Company Created</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .header {
            background-color: #007bff;
            color: #ffffff;
            padding: 10px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
            border: 1px solid #ddd;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to {{ config('app.name') }}!</h1>
        </div>
        <div class="content">
            <p>Dear {{ $user->name }},</p>
            <p>Congratulations! Your company, <strong>{{ $company->name }}</strong>, has been successfully created on {{ config('app.name') }}. We’re thrilled to have you on board as you take the first step toward securing your data with our trusted platform.</p>
            <p>Your account details:</p>
            <ul>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                <li><strong>Password:</strong> {{ $password }}</li>
                <li><strong>Plan Period:</strong> {{ $company->start_date }} to {{ $company->end_date }}</li>
                <li><strong>Storage:</strong> {{ $company->storage_size_mb ?? 'Not specified' }} MB</li>
                <li><strong>Website Link:</strong> <a href="https://datasafehub.in/">https://datasafehub.in/</a></li>
            </ul>
            <p>We recommend logging in and updating your password for security.</p>
            <p><a href="{{ url('/login') }}" class="button">Log In Now</a></p>
            <p>If you have any questions or need assistance, simply reply to this email or reach out to our team.</p>
            <p>Thank you for choosing our platform!</p>
            <p>Best Regards,<br> </p>
            <p><strong>The Data Safe Hub Team</strong><br> </p>
            <p><strong>Dixit Prajapati (Mo: 7990822351/7874138237)</strong><br> </p>
            <p>Head of IT/Marketing Department<br> </p>
            <p><strong>Apexrise Consultant and E-Service</strong><br> </p>
            <p>1018, Derasar Vado Khancho, Maneck Chowk Ahmedabad<br> </p>
            <p>Gujarat India 380001<br> </p>
            <p>W: <a href="https://www.datasafehub.in">www.datasafehub.in</a>  E: <a href="mailto:info@indiaevoting.com">info@indiaevoting.com</a></p>
        </div>
    </div>
</body>
</html>