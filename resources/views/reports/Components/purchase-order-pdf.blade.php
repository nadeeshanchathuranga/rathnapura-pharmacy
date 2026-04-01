<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $purchaseOrder->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5px;
            padding: 0;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 5px;
            margin-left: 0;
            margin-right: 0;
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
        .info-section {
            margin-bottom: 15px;
            font-size: 12px;
        }
        .info-table {
            width: 100%;
            border: none;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 3px 5px;
            border: none;
            font-size: 12px;
            vertical-align: top;
        }
        .info-table .label {
            font-weight: bold;
            width: 140px;
            color: #555;
        }
        table.products {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
            table-layout: fixed;
        }
        table.products th {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            word-wrap: break-word;
        }
        table.products td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }
        .col-no { width: 6%; }
        .col-product { width: 30%; }
        .col-unit { width: 14%; }
        .col-qty { width: 10%; }
        .col-price { width: 14%; }
        .col-discount { width: 12%; }
        .col-total { width: 14%; }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-section {
            width: 100%;
            margin-top: 10px;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 12px;
        }
        .totals-table td {
            padding: 5px 8px;
            border: none;
        }
        .totals-table .label {
            text-align: right;
            font-weight: bold;
            color: #555;
        }
        .totals-table .value {
            text-align: right;
            width: 120px;
        }
        .totals-table .grand-total td {
            border-top: 2px solid #333;
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }
        .remarks-section {
            margin-top: 15px;
            font-size: 12px;
        }
        .remarks-section strong {
            color: #555;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fef3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-completed { background-color: #cce5ff; color: #004085; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
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
        <h1>Purchase Order</h1>
        <p>{{ $purchaseOrder->order_number }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Supplier:</td>
            <td>{{ $purchaseOrder->supplier->name ?? 'N/A' }}</td>
            <td class="label">PO Number:</td>
            <td>{{ $purchaseOrder->order_number }}</td>
        </tr>
        <tr>
            <td class="label">Order Date:</td>
            <td>{{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d M Y') : 'N/A' }}</td>
            <td class="label">Status:</td>
            <td>
                <span class="status-badge status-{{ $purchaseOrder->status ?? 'pending' }}">
                    {{ ucfirst($purchaseOrder->status ?? 'pending') }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="label">Created By:</td>
            <td>{{ $purchaseOrder->user->name ?? 'N/A' }}</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <table class="products">
        <thead>
            <tr>
                <th class="col-no text-center">#</th>
                <th class="col-product">Product</th>
                <th class="col-unit">Unit</th>
                <th class="col-qty text-right">Qty</th>
                <th class="col-price text-right">Price ({{ $currency ?? '' }})</th>
                <th class="col-discount text-right">Disc %</th>
                <th class="col-total text-right">Total ({{ $currency ?? '' }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrder->products as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->product_name ?? 'N/A' }}</td>
                    <td>{{ $item->measurementUnit->name ?? '-' }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->purchase_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->discount_percentage, 2) }}%</td>
                    <td class="text-right">{{ number_format($item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #999;">No products found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="value">{{ $currency ?? '' }} {{ number_format($purchaseOrder->subtotal, 2) }}</td>
            </tr>
            @if($purchaseOrder->discount > 0)
            <tr>
                <td class="label">
                    Discount
                    @if($purchaseOrder->discount_type === 'percentage')
                        ({{ number_format($purchaseOrder->discount_percentage, 2) }}%)
                    @endif
                    :
                </td>
                <td class="value">- {{ $currency ?? '' }} {{ number_format($purchaseOrder->discount, 2) }}</td>
            </tr>
            @endif
            @if($purchaseOrder->tax_total > 0)
            <tr>
                <td class="label">Tax:</td>
                <td class="value">{{ $currency ?? '' }} {{ number_format($purchaseOrder->tax_total, 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td class="label">Grand Total:</td>
                <td class="value">{{ $currency ?? '' }} {{ number_format($purchaseOrder->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($purchaseOrder->remarks)
    <div class="remarks-section">
        <strong>Remarks:</strong> {{ $purchaseOrder->remarks }}
    </div>
    @endif

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }}<br>
        Powered by JAAN Network (PVT) Ltd
    </div>
</body>
</html>
