<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Income Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 20px; }
        h2 { color: #333; margin-bottom: 10px; }
        .period { color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { background: #e8f5e9; font-weight: bold; }
        .summary { margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #ccc; background-color: #f9fafb; }
    </style>
</head>
<body>
    <h2>Income Report</h2>
    <p class="period">Period: {{ $startDate }} to {{ $endDate }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Payment Type</th>
                <th>Transaction Type</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Transaction Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incomeSummary as $idx => $income)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $income['payment_type_name'] }}</td>
                    <td>{{ $income['transaction_type'] ?? 'N/A' }}</td>
                    <td class="text-right">{{ $currency }} {{ number_format($income['total_amount'], 2) }}</td>
                    <td class="text-right">{{ $income['transaction_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3"><strong>Grand Total</strong></td>
                <td class="text-right"><strong>{{ $currency }} {{ number_format($totalIncome, 2) }}</strong></td>
                <td class="text-right"><strong>{{ $incomeSummary->sum('transaction_count') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <p><strong>Summary:</strong></p>
        <p>Total Income for the period: <strong>{{ $currency }} {{ number_format($totalIncome, 2) }}</strong></p>
        <p>Total Transactions: <strong>{{ $incomeSummary->sum('transaction_count') }}</strong></p>
    </div>
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
