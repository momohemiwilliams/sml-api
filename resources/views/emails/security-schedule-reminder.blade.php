<!DOCTYPE html>
<html>
<head>
    <title>Security {{ $type === 'call' ? 'Call Date' : 'Coupon Reset Date' }} Reminder</title>
</head>
<body>
    @if($type === 'call')
        <h2>Call Date Reminder</h2>
        <p>The security "<strong>{{ $security->security_name }}</strong>" has a scheduled call date.</p>
        <ul>
            <li><strong>Call Date:</strong> {{ $schedule->call_date?->format('d M Y') }}</li>
            @if(!is_null($schedule->call_price))
                <li><strong>Call Price:</strong> {{ $schedule->call_price }}</li>
            @endif
            <li><strong>Call Amount:</strong> {{ is_null($schedule->call_amount) ? 'Full redemption' : $schedule->call_amount . " (₦'bn, partial redemption)" }}</li>
        </ul>
        <p>If the issuer has exercised the call, please record it in SMLARS (Exercise Call) so the security can be marked as called.</p>
    @else
        <h2>Coupon Reset Reminder</h2>
        <p>The security "<strong>{{ $security->security_name }}</strong>" has a scheduled coupon reset.</p>
        <ul>
            <li><strong>Reset Date:</strong> {{ $schedule->reset_date?->format('d M Y') }}</li>
            @if($schedule->benchmark)
                <li><strong>Benchmark:</strong> {{ $schedule->benchmark }}</li>
            @endif
            @if(!is_null($schedule->spread))
                <li><strong>Spread:</strong> {{ $schedule->spread }}</li>
            @endif
            @if(!is_null($schedule->new_coupon_rate))
                <li><strong>Expected New Coupon Rate:</strong> {{ $schedule->new_coupon_rate }}%</li>
            @endif
        </ul>
        <p>Please apply the new coupon rate in SMLARS (Apply Reset) so the security's coupon is updated.</p>
    @endif

    <a href="{{ $dashboardUrl }}">Go to Dashboard</a>
</body>
</html>
