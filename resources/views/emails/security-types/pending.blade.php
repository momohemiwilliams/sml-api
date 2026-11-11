<!DOCTYPE html>
<html>
<head>
    <title>New Security Type Request</title>
</head>
<body>
    <h2>New Security Type Request</h2>
    <p>Dear {{ $recipientName }},</p>
    
    <p>A new Security Type request has been submitted by {{ $pending->requester?->name ?? 'Inputter' }} and is awaiting your approval.</p>
    
    <p><strong>Request Details:</strong></p>
    <ul>
        <li><strong>Name:</strong> {{ $pending->name }}</li>
        <li><strong>Code:</strong> {{ $pending->code }}</li>
    </ul>

    <p>Please log in to the system to review and approve/reject this request.</p>
    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
