Welcome to {{ config('app.name') }}!
========================================

Dear, {{ $user->full_name }}!

✅ GREAT NEWS! Your account has been approved and is now active.

We're excited to have you on board! Your account has been successfully created and you can now access the system.

YOUR LOGIN CREDENTIALS:
-----------------------
Email Address: {{ $user->email }}
Temporary Password: {{ $password }}

⚠️ IMPORTANT: For security reasons, please change your password immediately after your first login.

Login to your account: {{ $loginUrl }}

ACCOUNT DETAILS:
----------------
Name: {{ $user->full_name }}
Email: {{ $user->email }}
Department: {{ $user->department ?? 'Not specified' }}
Role: {{ $user->roles->first()->name ?? 'User' }}

If you have any questions or need assistance, please don't hesitate to contact our support team.

This is an automated message, please do not reply to this email.

---
{{ config('app.name', 'SMLARS') }}
© {{ date('Y') }} {{ config('app.name', 'SMLARS') }}. All rights reserved.
{{ config('app.url') }}
