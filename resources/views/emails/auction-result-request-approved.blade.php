<!DOCTYPE html>
<html>
<head>
    <title>Auction Result Request Approved</title>
</head>
<body>
    <h2>Auction Result Request Approved</h2>
    <p>Dear,</p>
    <p>Your request ({{ ucfirst($pending->request_type) }}) for Auction Date: {{ $pending->auction_date->format('Y-m-d') }} has been approved.</p>
    
    <p>The record is now active/updated in the system.</p>
    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
