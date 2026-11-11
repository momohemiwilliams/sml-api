
            <h2>Dear {{ $recipientName }},</h2>
            <p>A new request has been submitted by <strong>{{ $requester['firstname'] ?? 'Unknown'  }} {{ $requester['lastname'] ?? ''  }}</strong> and is awaiting your approval.</p>
            
            <table class="details-table">
                <tr>
                    <td>Request Type:</td>
                    <td><span style="background: #eee; padding: 2px 6px; border-radius: 4px; font-weight: bold;">{{ strtoupper($pending->request_type) }}</span></td>
                </tr>
                <tr>
                    <td>Category Name:</td>
                    <td>{{ $pending->name }}</td>
                </tr>
                <tr>
                    <td>Code:</td>
                    <td>{{ $pending->code }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>Pending Approval</td>
                </tr>
            </table>

            <p>You can view the request details by clicking the link below:</p>
            <a href="https://adgnode.fmdqgroup.com/smlar/login" class="button">View Request</a>

      
