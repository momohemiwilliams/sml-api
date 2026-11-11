<!DOCTYPE html>
<html>
<head>
    <title>Security Type Request Rejected</title>
</head>
<body>
    <h2>Security Type Request Rejected</h2>
    <p>Dear {{ $recipientName }},</p>
    
    <p>Your request to <strong>{{ $pending->request_type }}</strong> the Security Type <strong>{{ $pending->name }} ({{ $pending->code }})</strong> has been REJECTED.</p>
    
    <p><strong>Rejected By:</strong> {{ $pending->selectedAuthoriser->name ?? 'Authoriser' }}</p>
    <p><strong>Reason:</strong> {{ $reason }}</p>


    <p>Please review the reason and submit a new request if necessary.</p>
    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
  
</body>
</html>
