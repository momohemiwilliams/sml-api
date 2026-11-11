<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Security Master List</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #333; }
        h1 { text-align: center; color: #1a237e; margin-bottom: 5px; }
        .subtitle { text-align: center; font-size: 9pt; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; font-size: 9pt; }
        tr:nth-child(even) { background-color: #fafafa; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .status-active { color: green; }
        .status-matured { color: red; }
    </style>
</head>
<body>
    <h1>Security Master List</h1>
    <div class="subtitle">Generated on {{ date('d F Y, H:i') }} | Licensed to FMDQ Exchange</div>
    
    <table>
        <thead>
            <tr>
                <th>ISIN</th>
                <th>Security Name</th>
                <th>Issuer</th>
                <th>Type</th>
                <th>Issue Date</th>
                <th>Maturity</th>
                <th>Face Value (₦)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($securities as $sec)
            <tr>
                <td>{{ $sec->isin }}</td>
                <td>{{ $sec->security_name }}</td>
                <td>{{ $sec->issuer }}</td>
                <td>{{ $sec->productType->name ?? 'N/A' }}</td>
                <td>{{ $sec->issue_date->format('d/m/Y') }}</td>
                <td>{{ $sec->maturity_date->format('d/m/Y') }}</td>
                <td style="text-align: right;">{{ number_format($sec->face_value, 2) }}</td>
                <td class="{{ $sec->status === 'Active' ? 'status-active' : 'status-matured' }}">
                    {{ $sec->status }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        Security Master List & Resolution System (SMLARS) - Confidential Report
    </div>
</body>
</html>
