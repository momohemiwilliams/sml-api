<!DOCTYPE html>
<html>
<head>
    <title>Request Approved</title>
</head>
<body>
    <p>Dear {{ $recipientName }},</p>
    
    <p>Your request to {{ $pending->request_type }} the market category <strong>{{ $pending->name }} ({{ $pending->code }})</strong> has been approved.</p>
    
    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
