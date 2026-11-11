<!DOCTYPE html>
<html>
<head>
    <title>Auction Result Request Rejected</title>
</head>
<body>
    <h2>Auction Result Request Rejected</h2>
    <p>Dear,</p>
    <p>Your request ({{ ucfirst($pending->request_type) }}) for Auction Date: {{ $pending->auction_date->format('Y-m-d') }} has been rejected.</p>
    
    <p><strong>Reason:</strong> {{ $pending->rejection_reason }}</p>
    
    <p>Please review and submit a new request if necessary.</p>
    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
