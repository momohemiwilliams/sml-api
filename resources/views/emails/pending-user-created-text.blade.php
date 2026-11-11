New User Pending Approval
==========================

Dear, Authoriser!

A new user account has been created and is awaiting your approval.

USER DETAILS:
-------------
Full Name: {{ $user->full_name }}
Email: {{ $user->email }}
Department: {{ $user->department ?? 'Not specified' }}
Requested Role: {{ $user->role ?? ($user->roles->first()->name ?? 'N/A') }}
Created At: {{ optional($user->created_at)->format('M d, Y h:i A') ?? now()->format('M d, Y h:i A') }}

Please review this user's information and take appropriate action.

Review Pending Users: {{ $pendingUsersUrl }}

Note: You can approve or reject this user from the pending users page in your admin dashboard.

This is an automated notification. Please log in to your account to take action.

---
{{ config('app.name', 'SMLARS') }}
© {{ date('Y') }} {{ config('app.name', 'SMLARS') }}. All rights reserved.
{{ config('app.url') }}
