<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Auction Results Summary</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #333; }
        h1 { text-align: center; color: #1a237e; margin-bottom: 5px; }
        .subtitle { text-align: center; font-size: 8pt; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; font-size: 8pt; }
        tr:nth-child(even) { background-color: #fafafa; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 7pt; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Auction Results Summary</h1>
    <div class="subtitle">Generated on {{ date('d F Y, H:i') }} | Licensed to FMDQ Exchange</div>
    
    <table>
        <thead>
            <tr>
                <th>Auction No</th>
                <th>Date</th>
                <th>Security</th>
                <th>Tenor</th>
                <th>Offered (N'bn)</th>
                <th>Subscribed (N'bn)</th>
                <th>Sold (N'bn)</th>
                <th>Stop Rate</th>
                <th>BCR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($auctionResults as $res)
            <tr>
                <td>{{ $res->auction_number }}</td>
                <td>{{ $res->auction_date->format('d/m/Y') }}</td>
                <td>{{ $res->security->security_name }}<br><small>{{ $res->security->isin }}</small></td>
                <td>{{ $res->tenor_days }}</td>
                <td class="text-right">{{ number_format($res->amount_offered / 1000000000, 2) }}</td>
                <td class="text-right">{{ number_format($res->amount_subscribed / 1000000000, 2) }}</td>
                <td class="text-right">{{ number_format($res->total_amount_sold / 1000000000, 2) }}</td>
                <td class="text-right">{{ $res->stop_rate }}%</td>
                <td class="text-right">{{ $res->bid_cover_ratio }}x</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        Security Master List & Resolution System (SMLARS) - Confidential Report
    </div>
</body>
</html>
