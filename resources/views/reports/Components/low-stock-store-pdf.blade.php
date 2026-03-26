<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Store Low Stock Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .date-range {
            text-align: center;
            margin-bottom: 20px;
            font-size: 11px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #16a34a;
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f0fdf4;
            border-left: 4px solid #16a34a;
        }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #ccc; background-color: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏬 Store Low Stock Report</h1>
    </div>

    @if($startDate && $endDate)
    <div class="date-range">
        Period: {{ $startDate }} to {{ $endDate }}
    </div>
    @endif

    <div class="summary">
        <strong>Total Low Stock Items:</strong> {{ count($products) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item Name</th>
                <th class="text-right">Store Qty</th>
                <th class="text-right">Low Stock Margin</th>
                <th class="text-center">Sales Unit</th>
                <th class="text-center">Symbol</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $index => $product)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $product['name'] }}</td>
                <td class="text-right">{{ $product['store_quantity'] }}</td>
                <td class="text-right">{{ $product['store_low_stock_margin'] }}</td>
                <td class="text-center">{{ $product['sales_unit'] }}</td>
                <td class="text-center">{{ $product['symbol'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($products) == 0)
    <div style="text-align: center; padding: 40px; color: #666;">
        No low stock products found in store.
    </div>
    @endif
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
