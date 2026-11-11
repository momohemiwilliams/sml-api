<!DOCTYPE html>
<html>
<head>
    <title>Security Master Request Rejected</title>
</head>
<body>
    <h2>Request Rejected</h2>
    <p>Your request to {{ $pending->request_type }} security "<strong>{{ $pending->security_name }}</strong>" has been rejected.</p>
    <p><strong>Reason:</strong> {{ $reason }}</p>
    
    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
