<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Low Stock Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px }
        .title { font-size:18px; font-weight:700 }
        .cards { display:flex; gap:12px; margin-bottom:16px }
        .card { padding:10px 12px; border-radius:6px; background:#f5f5f5; flex:1 }
        .card .label { font-size:11px; color:#555 }
        .card .value { font-size:16px; font-weight:700; margin-top:6px }
        table { width: 100%; border-collapse: collapse; font-size:12px }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f2f2f2; font-weight:700 }
        .text-center { text-align:center }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #ccc; background-color: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">Products Low Stock Report</div>
            <div style="font-size:11px; color:#666">Generated: {{ date('Y-m-d H:i') }}</div>
        </div>

    </div>

    @php
        $totalLow = count($products);
        $shopLow = collect($products)->filter(fn($p) => ($p['shop_status'] ?? '') === 'Low')->count();
        $storeLow = collect($products)->filter(fn($p) => ($p['store_status'] ?? '') === 'Low')->count();
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Product</th>
                <th>Barcode</th>
                <th style="width:70px" class="text-center">Shop Qty</th>
                <th style="width:70px" class="text-center">Shop Margin</th>
                <th style="width:70px" class="text-center">Shop Status</th>
                <th style="width:70px" class="text-center">Store Qty</th>
                <th style="width:70px" class="text-center">Store Margin</th>
                <th style="width:70px" class="text-center">Store Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $idx => $p)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $p['name'] }}</td>
                    <td>{{ $p['barcode'] }}</td>
                    <td class="text-center">{{ $p['shop_quantity_in_sales_unit'] }}</td>
                    <td class="text-center">{{ $p['shop_low_stock_margin'] }}</td>
                    <td class="text-center">{{ $p['shop_status'] }}</td>
                    <td class="text-center">{{ $p['store_quantity'] }}</td>
                    <td class="text-center">{{ $p['store_low_stock_margin'] }}</td>
                    <td class="text-center">{{ $p['store_status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
