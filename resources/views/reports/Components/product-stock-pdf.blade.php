<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Stock Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #ccc; background-color: #f9fafb; }
    </style>
</head>
<body>
    <h2>Product Stock Report</h2>
    <p>Date: {{ $reportDate ?? date('Y-m-d') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Shop Qty</th>
                <th>Store Qty</th>
                <th>Loose</th>
                <!-- Removed Retail Price and Wholesale Price columns -->
            </tr>
        </thead>
        <tbody>
            @foreach($productsStock as $idx => $p)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $p['name'] }}</td>
                    <td>{{ $p['shop_qty_display'] }}</td>
                    <td>{{ $p['store_qty_display'] }}</td>
                    <td>{{ $p['loose_bundles'] }}</td>
                    <!-- Removed Retail Price and Wholesale Price columns -->
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>

