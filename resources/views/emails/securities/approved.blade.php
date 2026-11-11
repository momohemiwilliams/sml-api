<!DOCTYPE html>
<html>
<head>
    <title>Security Request Approved</title>
</head>
<body>
    <h2>Security Request Approved</h2>
    <p>Dear {{ $recipientName }},</p>
    
    <p>Your Security request has been <strong>approved</strong>.</p>
    
    <p><strong>Request Details:</strong></p>
    <ul>
        <li><strong>Type:</strong> {{ ucfirst($pending->request_type) }}</li>
        <li><strong>ISIN:</strong> {{ $pending->isin ?? 'N/A' }}</li>
        <li><strong>Issuer:</strong> {{ $pending->issuer ?? 'N/A' }}</li>
        <li><strong>Description:</strong> {{ $pending->description ?? 'N/A' }}</li>
        <li><strong>Security Type:</strong> {{ $pending->security_type_name ?? 'N/A' }}</li>
        <li><strong>Product Type:</strong> {{ $pending->product_type_name ?? 'N/A' }}</li>
    </ul>

    @if($pending->request_type === 'create')
        <p>The security has been successfully created in the system.</p>
    @elseif($pending->request_type === 'update')
        <p>The security has been successfully updated in the system.</p>
    @elseif($pending->request_type === 'delete')
        <p>The security has been successfully deleted from the system.</p>
    @endif

    <a href="https://adgnode.fmdqgroup.com/smlar/login">Go to Dashboard</a>
</body>
</html>
