<!DOCTYPE html>
<html>
<head>
    <title>Security Request Rejected</title>
</head>
<body>
    <h2>Security Request Rejected</h2>
    <p>Dear {{ $pending->requester?->firstname ?? 'User' }},</p>
    
    <p>Your Security request has been <strong>rejected</strong> by {{ $pending->selectedAuthoriser?->firstname ?? 'Authoriser' }} {{ $pending->selectedAuthoriser?->last_name ?? '' }}.</p>
    
    <p><strong>Request Details:</strong></p>
    <ul>
        <li><strong>Type:</strong> {{ ucfirst($pending->request_type) }}</li>
        <li><strong>ISIN:</strong> {{ $pending->isin ?? 'N/A' }}</li>
        <li><strong>Issuer:</strong> {{ $pending->issuer ?? 'N/A' }}</li>
        <li><strong>Description:</strong> {{ $pending->description ?? 'N/A' }}</li>
        <li><strong>Security Type:</strong> {{ $pending->security_type_name ?? 'N/A' }}</li>
        <li><strong>Product Type:</strong> {{ $pending->product_type_name ?? 'N/A' }}</li>
        <li><strong>Rejected At:</strong> {{ now()->format('Y-m-d H:i') }}</li>
    </ul>

    <p><strong>Rejection Reason:</strong></p>
    <p style="background-color: #f8d7da; padding: 10px; border-left: 4px solid #dc3545;">
        {{ $reason ?? 'No reason provided' }}
    </p>

    <p>Please review the rejection reason and submit a new request if needed.</p>

    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
