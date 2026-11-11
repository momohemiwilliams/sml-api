Password Reset Request
======================

Dear, {{ $user->full_name }}!

We received a request to reset the password for your account associated with {{ $user->email }}.

To reset your password, please visit the following link:

{{ $resetUrl }}

⏰ IMPORTANT: This link will expire in 60 minutes for security reasons.

If you're having trouble clicking the link, copy and paste it into your web browser.

SECURITY NOTICE
================
⚠️ If you didn't request a password reset, please ignore this email or contact support if you have concerns about your account security.

---

This is an automated message, please do not reply to this email.

{{ config('app.name', 'SMLARS') }}
© {{ date('Y') }} {{ config('app.name', 'SMLARS') }}. All rights reserved.

Visit our website: {{ config('app.url') }}
