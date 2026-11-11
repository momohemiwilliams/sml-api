<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Request Update</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-body h2 {
            color: #333;
            font-size: 22px;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .email-body p {
            margin-bottom: 20px;
            color: #555;
            font-size: 16px;
        }
        .rejection-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .rejection-box h3 {
            margin: 0 0 10px 0;
            color: #721c24;
            font-size: 16px;
        }
        .rejection-box p {
            margin: 0;
            color: #721c24;
            font-size: 15px;
            font-style: italic;
        }
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #0d47a1;
        }
        .contact-box {
            background-color: #f8f9fa;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
            text-align: center;
        }
        .contact-box p {
            margin: 5px 0;
            font-size: 15px;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .email-footer p {
            margin: 5px 0;
            font-size: 14px;
            color: #6c757d;
        }
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>📋 Account Request Update</h1>
        </div>
        
        <div class="email-body">
            <h2>Dear, {{ $user->full_name }}</h2>
            
            <p>We wanted to inform you about the status of your account request for {{ config('app.name') }}.</p>
            
            <p>Unfortunately, your account request has not been approved at this time.</p>
            
            <div class="rejection-box">
                <h3>Reason for Rejection:</h3>
                <p>"{{ $reason }}"</p>
            </div>
            
            <div class="info-box">
                <p><strong>ℹ️ What this means:</strong> Your account will not be created and you will not have access to the system at this time.</p>
            </div>
            
            <div class="divider"></div>
            
            <p>If you believe this decision was made in error or if you would like to discuss this further, please don't hesitate to contact our support team or the administrator who reviewed your request.</p>
            
            <div class="contact-box">
                <p><strong>Need Help?</strong></p>
                <p>Contact our support team for assistance</p>
                <p style="color: #667eea; font-weight: 600;">support@{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com' }}</p>
            </div>
            
            <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
                Thank you for your understanding.
            </p>
            
            <p style="font-size: 14px; color: #6c757d;">
                This is an automated message, please do not reply to this email.
            </p>
        </div>
        
        <div class="email-footer">
            <p><strong>{{ config('app.name', 'SMLARS') }}</strong></p>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'SMLARS') }}. All rights reserved.</p>
            <p style="margin-top: 15px;">
                <a href="{{ config('app.url') }}" style="color: #ff6b6b; text-decoration: none;">Visit our website</a>
            </p>
        </div>
    </div>
</body>
</html>
