<!DOCTYPE html>
<html>
<head>
    <title>Product Type Request Rejected</title>
</head>
<body>
    <h2>Product Type Request Rejected</h2>
    <p>Dear {{ $recipientName }},</p>
    
    <p>Your request to <strong>{{ $pending->request_type }}</strong> the Product Type <strong>{{ $pending->name }} ({{ $pending->code }})</strong> has been REJECTED.</p>
    
    <p><strong>Rejected By:</strong> {{ $pending->selectedAuthoriser?->name ?? 'Authoriser' }}</p>
    <p><strong>Reason:</strong> {{ $reason }}</p>

    <p>Please review the reason and submit a new request if necessary.</p>

    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
