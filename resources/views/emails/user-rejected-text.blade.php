Account Request Update
======================

Dear, {{ $user->full_name }}

We wanted to inform you about the status of your account request for {{ config('app.name') }}.

Unfortunately, your account request has not been approved at this time.

REASON FOR REJECTION:
---------------------
"{{ $reason }}"

ℹ️ WHAT THIS MEANS: Your account will not be created and you will not have access to the system at this time.

If you believe this decision was made in error or if you would like to discuss this further, please don't hesitate to contact our support team or the administrator who reviewed your request.

NEED HELP?
----------
Contact our support team for assistance
Email: support@{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com' }}

Thank you for your understanding.

This is an automated message, please do not reply to this email.

---
{{ config('app.name', 'SMLARS') }}
© {{ date('Y') }} {{ config('app.name', 'SMLARS') }}. All rights reserved.
{{ config('app.url') }}
