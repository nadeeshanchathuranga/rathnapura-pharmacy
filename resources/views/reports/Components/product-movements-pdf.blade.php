<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product Movements Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-date {
            text-align: center;
            margin-bottom: 20px;
            font-size: 10px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background-color: #4a5568;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #ccc; background-color: #f9fafb; }
        .badge-purchase {
            background-color: #48bb78;
            color: white;
        }
        .badge-purchase-return {
            background-color: #ed8936;
            color: white;
        }
        .badge-transfer {
            background-color: #4299e1;
            color: white;
        }
        .badge-sale {
            background-color: #f56565;
            color: white;
        }
        .badge-sale-return {
            background-color: #9f7aea;
            color: white;
        }
        .qty-positive {
            color: #48bb78;
            font-weight: bold;
        }
        .qty-negative {
            color: #f56565;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Product Movements Report</h1>
    </div>
    
    <div class="report-date">
        Period: {{ $startDate }} to {{ $endDate }}
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Barcode</th>
                <th class="text-center">Movement Type</th>
                <th class="text-right">Quantity</th>
                <th>Reference</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productMovements as $movement)
            @php
                $movementTypes = [
                    0 => ['label' => 'Purchase', 'class' => 'badge-purchase'],
                    1 => ['label' => 'Purchase Return', 'class' => 'badge-purchase-return'],
                    2 => ['label' => 'Transfer', 'class' => 'badge-transfer'],
                    3 => ['label' => 'Sale', 'class' => 'badge-sale'],
                    4 => ['label' => 'Sale Return', 'class' => 'badge-sale-return'],
                ];
                $type = $movementTypes[$movement->movement_type] ?? ['label' => 'Unknown', 'class' => ''];
                $qtyClass = $movement->quantity >= 0 ? 'qty-positive' : 'qty-negative';
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($movement->created_at)->format('M d, Y H:i') }}</td>
                <td>{{ $movement->product->name ?? 'N/A' }}</td>
                <td>{{ $movement->product->barcode ?? 'N/A' }}</td>
                <td class="text-center">
                    <span class="badge {{ $type['class'] }}">{{ $type['label'] }}</span>
                </td>
                <td class="text-right {{ $qtyClass }}">{{ number_format($movement->quantity, 2) }}</td>
                <td>{{ $movement->reference }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No product movements found for the selected period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
