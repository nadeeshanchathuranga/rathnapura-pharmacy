<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GRN Return Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        .date-range {
            text-align: right;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .summary {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            font-size: 12px;
        }
        .summary-box {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            flex: 1;
            margin: 0 5px;
        }
        .summary-box strong {
            display: block;
            font-size: 14px;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        th {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Goods Received Notes Return Report</h1>
        <p>Returns & Refunds Summary</p>
    </div>

    <div class="date-range">
        <strong>Period:</strong> {{ $startDate }} to {{ $endDate }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>GRN No</th>
                <th>Handled By</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Est. Value</th>
                <th>Items</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td><strong>{{ $row['grn_no'] ?? '—' }}</strong></td>
                    <td>{{ $row['handled_by'] }}</td>
                    <td class="text-right">{{ $row['total_quantity'] }}</td>
                    <td class="text-right">{{ $currency }} {{ number_format($row['estimated_value'], 2) }}</td>
                    <td>
                        @forelse($row['items'] ?? [] as $item)
                            {{ $item['product_name'] }} - {{ $item['quantity'] }} pcs ({{ $currency }} {{ number_format($item['estimated_value'], 2) }})<br>
                        @empty
                            —
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #999;">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }}
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
