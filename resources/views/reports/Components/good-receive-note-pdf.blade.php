<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Goods Received Note Report</title>
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
        .date-range {
            text-align: right;
            font-size: 12px;
            margin-bottom: 10px;
            margin-right: 0;
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
            table-layout: fixed;
        }
        .col-grn-no {
            width: 18%;
        }
        .col-supplier {
            width: 10%;
        }
        .col-product {
            width: 20%;
        }
        .col-qty {
            width: 10%;
        }
        .col-gross {
            width: 12%;
        }
        .col-discount {
            width: 10%;
        }
        .col-tax {
            width: 10%;
        }
        .col-net {
            width: 10%;
        }
        th {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            word-wrap: break-word;
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
        <h1>Goods Received Notes Report</h1>
        <p>Inventory Receipts Summary</p>
    </div>

    <div class="date-range">
        <strong>Period:</strong> {{ $startDate }} to {{ $endDate }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-grn-no">GRN No</th>
                <th class="col-supplier">Supplier</th>
                <th class="col-product">Product Name</th>
                <th class="col-qty text-right">Quantity</th>
                <th class="col-gross text-right">Gross ({{ $currency ?? '' }})</th>
                <th class="col-discount text-right">Discount ({{ $currency ?? '' }})</th>
                <th class="col-tax text-right">Tax ({{ $currency ?? '' }})</th>
                <th class="col-net text-right">Net ({{ $currency ?? '' }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @forelse($row['items'] ?? [] as $item)
                <tr>
                    @if ($loop->first)
                        <td><strong>{{ $row['grn_no'] }}</strong></td>
                        <td>{{ $row['supplier_name'] }}</td>
                    @else
                        <td></td>
                        <td></td>
                    @endif
                    <td>{{ $item['name'] }}</td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    @if ($loop->first)
                        <td class="text-right">{{ $currency ?? '' }} {{ number_format($row['gross_total'], 2) }}</td>
                        <td class="text-right">{{ $currency ?? '' }} {{ number_format($row['line_discount'] + $row['header_discount'], 2) }}</td>
                        <td class="text-right">{{ $currency ?? '' }} {{ number_format($row['tax_total'], 2) }}</td>
                        <td class="text-right"><strong>{{ $currency ?? '' }} {{ number_format($row['net_total'], 2) }}</strong></td>
                    @else
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td><strong>{{ $row['grn_no'] }}</strong></td>
                    <td>{{ $row['supplier_name'] }}</td>
                    <td>—</td>
                    <td></td>
                    <td class="text-right">{{ $currency ?? '' }} {{ number_format($row['gross_total'], 2) }}</td>
                    <td class="text-right">{{ $currency ?? '' }} {{ number_format($row['line_discount'] + $row['header_discount'], 2) }}</td>
                    <td class="text-right">{{ $currency ?? '' }} {{ number_format($row['tax_total'], 2) }}</td>
                    <td class="text-right"><strong>{{ $currency ?? '' }} {{ number_format($row['net_total'], 2) }}</strong></td>
                </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #999;">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }}<br>
        Powered by JAAN Network (PVT) Ltd
    </div>
</body>
</html>
