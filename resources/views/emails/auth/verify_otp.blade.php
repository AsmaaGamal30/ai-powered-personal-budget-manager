<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 6px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .otp {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 6px;
            color: #1a73e8;
            text-align: center;
            margin: 20px 0;
        }
        .content {
            color: #333333;
            line-height: 1.6;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Email Verification</h2>
        </div>

        <div class="content">
            <p>Hi {{ $name }},</p>

            <p>
                Thank you for signing up. Please use the following One-Time Password (OTP)
                to verify your email address:
            </p>

            <div class="otp">
                {{ $otp }}
            </div>

            <p>
                This OTP is valid for a limited time. If you did not request this verification,
                please ignore this email.
            </p>

            <p>
                Best regards,<br>
                {{ config('app.name') }} Team
            </p>
        </div>

        <div class="footer">
            <p>
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
