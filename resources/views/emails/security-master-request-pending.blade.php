<!DOCTYPE html>
<html>
<head>
    <title>Security Master Request Pending</title>
</head>
<body>
    <h2>New Security Master Request: {{ ucfirst($pending->request_type) }}</h2>
    <p>A new request has been submitted by {{ $requester['name'] ?? 'Unknown' }}.</p>
    <p><strong>Security Name:</strong> {{ $pending->security_name }}</p>
    
    <p>Please review the request in the dashboard.</p>
    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
