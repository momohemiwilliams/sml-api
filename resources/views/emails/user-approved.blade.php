<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Account Approved</title>
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
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
        .credentials-box {
            background-color: #f8f9fa;
            border: 2px solid #4facfe;
            padding: 25px;
            margin: 25px 0;
            border-radius: 8px;
            text-align: center;
        }
        .credentials-box h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 18px;
        }
        .credential-item {
            background-color: #ffffff;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        .credential-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .credential-value {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            font-family: 'Courier New', monospace;
        }
        .login-button {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 172, 254, 0.4);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #856404;
        }
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success-box p {
            margin: 0;
            font-size: 14px;
            color: #155724;
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
            <h1>🎉 Welcome to {{ config('app.name') }}!</h1>
        </div>
        
        <div class="email-body">
            <h2>Dear, {{ $user->full_name }}!</h2>
            
            <div class="success-box">
                <p><strong>✅ Great news!</strong> Your account has been approved and is now active.</p>
            </div>
            
            <p>We're excited to have you on board! Your account has been successfully created and you can now access the system.</p>
            
            <div class="credentials-box">
                <h3>🔐 Your Login Credentials</h3>
                
                <div class="credential-item">
                    <div class="credential-label">Email Address</div>
                    <div class="credential-value">{{ $user->email }}</div>
                </div>
                
                <div class="credential-item">
                    <div class="credential-label">Temporary Password</div>
                    <div class="credential-value">{{ $password }}</div>
                </div>
            </div>
            
            <div class="info-box">
                <p><strong>⚠️ Important:</strong> For security reasons, please change your password immediately after your first login.</p>
            </div>
            
            <p>Click the button below to log in to your account:</p>
            
            <div class="button-container">
                <a href="{{ $loginUrl }}" class="login-button">Login to Your Account</a>
            </div>
            
            <div class="divider"></div>
            
            <p><strong>Account Details:</strong></p>
            <ul style="color: #555; font-size: 15px;">
                <li>Name: {{ $user->full_name }}</li>
                <li>Email: {{ $user->email }}</li>
                <li>Department: {{ $user->department ?? 'Not specified' }}</li>
                <li>Role: {{ $user->roles->first()->name ?? 'User' }}</li>
            </ul>
            
            <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
                If you have any questions or need assistance, please don't hesitate to contact our support team.
            </p>
            
            <p style="font-size: 14px; color: #6c757d;">
                This is an automated message, please do not reply to this email.
            </p>
        </div>
        
        <div class="email-footer">
            <p><strong>{{ config('app.name', 'SMLARS') }}</strong></p>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'SMLARS') }}. All rights reserved.</p>
            <p style="margin-top: 15px;">
                <a href="{{ config('app.url') }}" style="color: #4facfe; text-decoration: none;">Visit our website</a>
            </p>
        </div>
    </div>
</body>
</html>
