<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Transfer Return Report</title>
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
        .return-block {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 10px;
            background: #fafafa;
        }
        .return-header {
            background: #f2f2f2;
            padding: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #f44336;
        }
        .return-info {
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
        .reason-box {
            background: #fff3cd;
            padding: 8px;
            margin: 10px 0;
            border-left: 3px solid #ffc107;
            font-style: italic;
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
            background: #ffebee;
            border-radius: 5px;
        }
        .page-break {
            page-break-after: always;
        }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #ccc; background-color: #f9fafb; }
    </style>
</head>
<body>
    <h2>Stock Transfer Return Report</h2>
    <p class="period">Period: {{ $startDate }} to {{ $endDate }}</p>
    
    @if($returns->isEmpty())
        <p style="text-align: center; color: #999; padding: 40px;">No stock transfer returns found for the selected date range.</p>
    @else
        @foreach($returns as $index => $return)
            <div class="return-block">
                <div class="return-header">
                    <strong>Return #{{ $return['return_no'] }}</strong> - {{ $return['return_date'] }}
                </div>
                
                <div class="return-info">
                    <div class="info-row">
                        <div class="info-label">Return ID:</div>
                        <div class="info-value">{{ $return['id'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Processed By:</div>
                        <div class="info-value">{{ $return['user_name'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value">
                            <span class="{{ $return['status'] == 1 ? 'status-approved' : 'status-pending' }}">
                                {{ $return['status_name'] }}
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Total Items:</div>
                        <div class="info-value"><strong>{{ $return['total_items'] }}</strong></div>
                    </div>
                </div>
                
                @if($return['reason'] && $return['reason'] !== 'N/A')
                    <div class="reason-box">
                        <strong>Reason:</strong> {{ $return['reason'] }}
                    </div>
                @endif
                
                <table>
                    <thead>
                        <tr>
                            <th style="width: 8%;">#</th>
                            <th style="width: 60%;">Product Name</th>
                            <th style="width: 17%;" class="text-right">Quantity</th>
                            <th style="width: 15%;">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($return['products'] as $idx => $product)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $product['product_name'] }}</td>
                                <td class="text-right">{{ $product['stock_transfer_quantity'] }}</td>
                                <td>{{ $product['measurement_unit'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: #f5f5f5; font-weight: bold;">
                            <td colspan="2" style="text-align: right;">Subtotal:</td>
                            <td class="text-right">{{ $return['total_items'] }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            @if(($index + 1) % 2 == 0 && $index + 1 < $returns->count())
                <div class="page-break"></div>
            @endif
        @endforeach
        
        <div class="summary-box">
            <p><strong>Summary:</strong></p>
            <p>Total Returns: <strong>{{ $returns->count() }}</strong></p>
            <p>Total Items Returned: <strong>{{ $returns->sum('total_items') }}</strong></p>
        </div>
    @endif
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
