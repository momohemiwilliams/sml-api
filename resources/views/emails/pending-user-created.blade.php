<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New User Pending Approval</title>
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        .user-details {
            background-color: #f8f9fa;
            border-left: 4px solid #f5576c;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .user-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .user-details td {
            padding: 8px 0;
            font-size: 15px;
        }
        .user-details td:first-child {
            font-weight: 600;
            color: #333;
            width: 40%;
        }
        .user-details td:last-child {
            color: #555;
        }
        .action-button {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
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
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>👤 New User Pending Approval</h1>
        </div>
        
        <div class="email-body">
            <h2>Dear, Authoriser!</h2>
            
            <p>A new user account has been created and is awaiting your approval.</p>
            
            <div class="user-details">
                <table>
                    <tr>
                        <td>Full Name:</td>
                        <td><strong>{{ $user->full_name }}</strong></td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td>Department:</td>
                        <td>{{ $user->department ?? 'Not specified' }}</td>
                    </tr>
                    <tr>
                        <td>Requested Role:</td>
                        <td><strong>{{ $user->role ?? ($user->roles->first()->name ?? 'N/A') }}</strong></td>
                    </tr>
                    <tr>
                        <td>Created At:</td>
                        <td>{{ optional($user->created_at)->format('M d, Y h:i A') ?? now()->format('M d, Y h:i A') }}</td>
                    </tr>
                </table>
            </div>
            
            <p>Please review this user's information and take appropriate action.</p>
            
            <div class="button-container">
                <a href="{{ $pendingUsersUrl }}" class="action-button">Review Pending Users</a>
            </div>
            
            <div class="info-box">
                <p><strong>ℹ️ Note:</strong> You can approve or reject this user from the pending users page in your admin dashboard.</p>
            </div>
            
            <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
                This is an automated notification. Please log in to your account to take action.
            </p>
        </div>
        
        <div class="email-footer">
            <p><strong>{{ config('app.name', 'SMLARS') }}</strong></p>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'SMLARS') }}. All rights reserved.</p>
            <p style="margin-top: 15px;">
                <a href="{{ config('app.url') }}" style="color: #f5576c; text-decoration: none;">Visit our website</a>
            </p>
        </div>
    </div>
</body>
</html>
