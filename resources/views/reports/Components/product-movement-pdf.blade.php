<!DOCTYPE html>
<html>
<head>
    <title>Product Movement Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-box {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #4a5568;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .summary-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .summary-card {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #555;
        }
        .summary-card p {
            margin: 5px 0;
            font-size: 16px;
            font-weight: bold;
        }
        .totals-box {
            background-color: #e6f3ff;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .totals-box h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .totals-box .total-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            padding: 5px 0;
            border-bottom: 1px solid #ccc;
        }
        .totals-box .total-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 14px;
        }
        .movement-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .type-purchase {
            background-color: #d4edda;
            color: #155724;
        }
        .type-sale {
            background-color: #f8d7da;
            color: #721c24;
        }
        .type-transfer_in, .type-purchase_return {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .type-transfer_out, .type-sale_return {
            background-color: #fff3cd;
            color: #856404;
        }
        .type-adjustment {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Product Movement Report</h1>
        <p>Generated on: {{ date('F d, Y H:i:s') }}</p>
    </div>

    <div class="info-box">
        <p><strong>Report Period:</strong> {{ date('F d, Y', strtotime($startDate)) }} to {{ date('F d, Y', strtotime($endDate)) }}</p>
        @if($productName)
            <p><strong>Filtered by Product:</strong> {{ $productName }}</p>
        @else
            <p><strong>Filter:</strong> All Products</p>
        @endif
    </div>

    <h2>Movement Details</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Code</th>
                <th>Type</th>
                <th style="text-align: right;">Quantity</th>
                <th>Reference</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $movement)
                <tr>
                    <td>{{ date('Y-m-d', strtotime($movement['movement_date'])) }}</td>
                    <td>{{ $movement['product_name'] }}</td>
                    <td>{{ $movement['product_code'] }}</td>
                    <td>
                        <span class="movement-type type-{{ str_replace(' ', '_', $movement['movement_type']) }}">
                            {{ ucwords(str_replace('_', ' ', $movement['movement_type'])) }}
                        </span>
                    </td>
                    <td style="text-align: right;">{{ number_format($movement['quantity'], 2) }}</td>
                    <td>{{ $movement['reference'] ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #999;">
                        No movements found for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($movements->isNotEmpty())
        <div class="summary-section">
            <h2>Summary by Movement Type</h2>
            <div class="summary-grid">
                @foreach($summaryByType as $summary)
                    <div class="summary-card">
                        <h3>{{ ucwords(str_replace('_', ' ', $summary['type_name'])) }}</h3>
                        <p>Count: {{ $summary['count'] }}</p>
                        <p>Total Quantity: {{ number_format($summary['total_quantity'], 2) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="totals-box">
                <h3>Overall Totals</h3>
                <div class="total-row">
                    <span>Total Inbound:</span>
                    <span>{{ number_format($totals['inbound'], 2) }}</span>
                </div>
                <div class="total-row">
                    <span>Total Outbound:</span>
                    <span>{{ number_format($totals['outbound'], 2) }}</span>
                </div>
                <div class="total-row">
                    <span>Net Movement:</span>
                    <span>{{ number_format($totals['net'], 2) }}</span>
                </div>
            </div>
        </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated report and does not require a signature.</p>
        <p>© {{ date('Y') }} JPOS. All rights reserved.</p>
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
