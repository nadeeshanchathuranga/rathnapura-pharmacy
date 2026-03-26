<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Release Report</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 11px; 
            margin: 20px; 
        }
        h2 { 
            color: #333; 
            margin-bottom: 5px; 
            font-size: 18px;
        }
        .period { 
            color: #666; 
            margin-bottom: 20px; 
            font-size: 10px;
        }
        .release-block {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 10px;
            background: #fafafa;
        }
        .release-header {
            background: #f2f2f2;
            padding: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #4CAF50;
        }
        .release-info {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 150px;
            padding: 3px 0;
        }
        .info-value {
            display: table-cell;
            padding: 3px 0;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
        }
        th, td { 
            border: 1px solid #ccc; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background: #e8e8e8; 
            font-weight: bold;
            font-size: 10px;
        }
        td {
            font-size: 10px;
        }
        .text-right { 
            text-align: right; 
        }
        .status-approved {
            color: #4CAF50;
            font-weight: bold;
        }
        .status-pending {
            color: #FF9800;
            font-weight: bold;
        }
        .summary-box {
            margin-top: 20px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 5px;
        }
        .page-break {
            page-break-after: always;
        }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #ccc; background-color: #f9fafb; }
    </style>
    </style>
</head>
<body>
    <h2>Product Release Report</h2>
    <p class="period">Period: {{ $startDate }} to {{ $endDate }}</p>
    
    @if($releases->isEmpty())
        <p style="text-align: center; color: #999; padding: 40px;">No product releases found for the selected date range.</p>
    @else
        @foreach($releases as $index => $release)
            <div class="release-block">
                <div class="release-header">
                    <strong>Release #{{ $release['id'] }}</strong> - {{ $release['release_date'] }}
                </div>
                
                <div class="release-info">
                    <div class="info-row">
                        <div class="info-label">Transfer Request No:</div>
                        <div class="info-value">{{ $release['product_transfer_request_no'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Processed By:</div>
                        <div class="info-value">{{ $release['user_name'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value">
                            <span class="{{ $release['status'] == 1 ? 'status-approved' : 'status-pending' }}">
                                {{ $release['status_name'] }}
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Total Items:</div>
                        <div class="info-value"><strong>{{ $release['total_items'] }}</strong></div>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th style="width: 10%;">#</th>
                            <th style="width: 70%;">Product Name</th>
                            <th style="width: 20%;" class="text-right">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($release['products'] as $idx => $product)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $product['product_name'] }}</td>
                                <td class="text-right">{{ $product['quantity'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: #f5f5f5; font-weight: bold;">
                            <td colspan="2" style="text-align: right;">Subtotal:</td>
                            <td class="text-right">{{ $release['total_items'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            @if(($index + 1) % 2 == 0 && $index + 1 < $releases->count())
                <div class="page-break"></div>
            @endif
        @endforeach
        
        <div class="summary-box">
            <p><strong>Summary:</strong></p>
            <p>Total Releases: <strong>{{ $releases->count() }}</strong></p>
            <p>Total Items Released: <strong>{{ $releases->sum('total_items') }}</strong></p>
        </div>
    @endif
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
