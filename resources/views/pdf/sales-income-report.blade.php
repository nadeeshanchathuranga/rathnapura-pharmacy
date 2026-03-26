<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order History Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 20px; }
        h2 { color: #333; margin-bottom: 10px; text-align: center; }
        .period { color: #666; margin-bottom: 20px; text-align: center; }
        .summary-cards { display: flex; justify-content: space-around; margin-bottom: 20px; }
        .summary-card { padding: 15px; text-align: center; border: 2px solid #ddd; border-radius: 5px; width: 30%; }
        .summary-card h3 { margin: 0; font-size: 12px; color: #666; }
        .summary-card p { margin: 5px 0 0; font-size: 18px; font-weight: bold; }
        .income { color: #2e7d32; }
        .return { color: #c62828; }
        .net { color: #1565c0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 10px; }
        th { background: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .amount-income { color: #2e7d32; font-weight: bold; }
        .amount-return { color: #c62828; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <h2>Order History Report</h2>
    <p class="period">Period: {{ $startDate }} to {{ $endDate }}</p>


    <table>
        <thead>
            <tr>
                <th>Invoice No</th>
                <th class="text-center">Income Date</th>
                <th class="text-right">Amount</th>
                <th class="text-center">Type</th>
                <th class="text-center">Payment Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salesIncomeList as $income)
                <tr>
                    <td>{{ $income['invoice_no'] }}</td>
                    <td class="text-center">{{ $income['income_date'] }}</td>
                    <td class="text-right {{ $income['is_return'] ? 'amount-return' : 'amount-income' }}">
                        {{ $currency }} {{ $income['amount'] }}
                    </td>
                    <td class="text-center">{{ $income['type'] }}</td>
                    <td class="text-center">{{ $income['payment_type_name'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
