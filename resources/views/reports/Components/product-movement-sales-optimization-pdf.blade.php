<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product Movement Sales Optimization Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
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
        .summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0f4ff;
            border-left: 4px solid #3b82f6;
        }
        .summary-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .summary-item {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .summary-item .label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 14px;
            font-weight: bold;
        }
        .fast-moving { color: #16a34a; }
        .medium-moving { color: #3b82f6; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #ccc; background-color: #f9fafb; }
        .slow-moving { color: #ea580c; }
        .no-sales { color: #dc2626; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 9px;
        }
        th {
            background-color: #3b82f6;
            color: white;
            padding: 8px 4px;
            text-align: left;
            font-size: 8px;
        }
        td {
            padding: 6px 4px;
            border-bottom: 1px solid #e2e8f0;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .classification-badge {
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-fast { background: #dcfce7; color: #16a34a; }
        .badge-medium { background: #dbeafe; color: #3b82f6; }
        .badge-slow { background: #fed7aa; color: #ea580c; }
        .badge-none { background: #fee2e2; color: #dc2626; }
        .recommendation {
            font-size: 8px;
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>  Product Movement Sales Optimization Report</h1>
    </div>

    <div class="date-range">
        Period: {{ $startDate }} to {{ $endDate }}
        @if($classification)
        | Classification: {{ $classification }}
        @endif
    </div>


    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Barcode</th>
                <th class="text-right">Stock</th>
                <th class="text-right">Sales Qty</th>
                <th class="text-right">Sales Amount</th>

                <th class="text-center">Classification</th>

            </tr>
        </thead>
        <tbody>
            @foreach($products as $index => $product)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $product['name'] }}</td>
                <td>{{ $product['barcode'] }}</td>
                <td class="text-right">{{ $product['current_stock'] }}</td>
                <td class="text-right">{{ $product['sales_quantity'] }}</td>
                <td class="text-right">{{ $currency }} {{ number_format($product['sales_amount'], 2) }}</td>

                <td class="text-center">
                    <span class="classification-badge
                        @if($product['classification'] == 'Fast Moving') badge-fast
                        @elseif($product['classification'] == 'Medium Moving') badge-medium
                        @elseif($product['classification'] == 'Slow Moving') badge-slow
                        @else badge-none
                        @endif
                    ">
                        {{ $product['classification'] }}
                    </span>
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($products) == 0)
    <div style="text-align: center; padding: 40px; color: #666;">
        No products found for the selected criteria.
    </div>
    @endif

    <div style="margin-top: 30px; font-size: 8px; color: #666; text-align: center;">
        Generated on {{ now()->format('Y-m-d H:i:s') }}
    </div>
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
