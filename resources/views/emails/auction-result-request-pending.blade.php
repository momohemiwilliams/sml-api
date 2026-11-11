<!DOCTYPE html>
<html>
<head>
    <title>Auction Result Request Pending</title>
</head>
<body>
    <h2>New Auction Result Request: {{ ucfirst($pending->request_type) }}</h2>
    <p>A new request has been submitted by {{ $requesterName }}.</p>
    <p><strong>Auction Date:</strong> {{ $pending->auction_date->format('Y-m-d') }}</p>
    <p><strong>Security ID:</strong> {{ $pending->security_id }}</p>
    
    <p>Please review the request in the dashboard.</p>
    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
