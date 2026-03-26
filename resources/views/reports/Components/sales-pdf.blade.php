<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
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
            background-color: #4a5568;
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
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-retail {
            background-color: #3b82f6;
            color: white;
        }
        .badge-wholesale {
            background-color: #8b5cf6;
            color: white;
        }        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #999;
            padding: 10px;
            border-top: 1px solid #e2e8f0;
            background-color: #f9fafb;
        }    </style>
</head>
<body>
    <div class="header">
        <h1>Sales Report by Type  {{ $startDate }} to {{ $endDate }}</h1>
    </div>

    

    <table>
        <thead>
            <tr> 
                <th>Sale Date</th>
                <th>Type</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Net Amount</th>

            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            <tr>
               
                <td>{{ $sale->sale_date }}</td>
                <td>
                    <span class="badge {{ $sale->type == 1 ? 'badge-retail' : 'badge-wholesale' }}">
                        {{ $sale->type == 1 ? 'Retail' : 'Wholesale' }}
                    </span>
                </td>
                <td class="text-right">{{ $currency ?? '' }} {{ number_format($sale->total_amount, 2) }}</td>
                <td class="text-right">{{ $currency ?? '' }} {{ number_format($sale->discount, 2) }}</td>
                <td class="text-right">{{ $currency ?? '' }} {{ number_format($sale->net_amount, 2) }}</td>

            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
