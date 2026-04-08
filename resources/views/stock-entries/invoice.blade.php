<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $stockEntry->invoice_number ?: $stockEntry->entry_number }}</title>
    <style>
        body {
            font-family: "Courier New", Courier, monospace;
            color: #2f2f2f;
            margin: 10px;
            font-size: 24px;
            line-height: 1.22;
            letter-spacing: 0.25px;
        }

        .paper {
            max-width: 2100px;
            margin: 0 auto;
        }

        .controls {
            margin-bottom: 16px;
            font-family: Arial, sans-serif;
        }

        .btn {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            background: #2563eb;
            color: #fff;
        }

        .line {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .line-center {
            text-align: center;
        }

        .line-center h1,
        .line-center h2,
        .line-center h3,
        .line-center p {
            margin: 0;
        }

        .sp-1 { margin-top: 8px; }
        .sp-2 { margin-top: 12px; }
        .sp-3 { margin-top: 18px; }

        .rule {
            white-space: nowrap;
            overflow: hidden;
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            padding: 1px 4px;
            vertical-align: top;
        }

        .items {
            margin-top: 8px;
        }

        .items th,
        .items td {
            padding: 2px 4px;
            font-weight: normal;
            text-align: left;
        }

        .items th {
            font-weight: bold;
        }

        .text-right {
            text-align: right !important;
        }

        .summary {
            width: 100%;
            margin-top: 10px;
        }

        .summary td {
            padding: 1px 4px;
        }

        .sign-row {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }

        .sign-col {
            width: 32%;
        }

        .sign-line {
            margin-top: 38px;
            border-top: 1px dashed #555;
            padding-top: 6px;
        }

        @media print {
            .controls {
                display: none;
            }

            body {
                margin: 0;
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="controls">
        <a href="javascript:window.print()" class="btn">Print Invoice</a>
    </div>

    @php($invoiceDate = optional($stockEntry->entry_date)->format('d-m-Y'))
    @php($invoiceNo = $stockEntry->invoice_number ?: $stockEntry->entry_number)
    @php($lineCount = $stockEntry->products->count())
    @php($productCount = $stockEntry->products->pluck('product_id')->filter()->unique()->count())
    @php($grandTotal = 0)

    <div class="paper">
        <div class="line">
            <div>Original</div>
            <div>{{ now()->format('d/m/Y h:i A') }}</div>
        </div>

        <div class="line-center sp-1">
            <h3>{{ $companyInformation?->company_name ?? 'Rathnapura Pharmacy' }}</h3>
            <p>{{ $companyInformation?->address ?? '' }}</p>
        </div>

        <table class="meta sp-2">
            <tr>
                <td style="width: 18%;">Invoice To:</td>
                <td style="width: 42%;">{{ strtoupper($stockEntry->supplier?->name ?? 'WALK IN SUPPLIER') }}</td>
                <td style="width: 18%;">Invoice No</td>
                <td style="width: 22%;">: {{ $invoiceNo }}</td>
            </tr>
            <tr>
                <td></td>
                <td>{{ strtoupper($stockEntry->supplier?->address ?? '-') }}</td>
                <td>Invoiced Date</td>
                <td>: {{ $invoiceDate }}</td>
            </tr>
            <tr>
                <td>Entry No</td>
                <td>: {{ $stockEntry->entry_number }}</td>
                <td>Sales Person</td>
                <td>: {{ $stockEntry->user?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Entry Type</td>
                <td>: {{ $stockEntry->entry_type === 'addition' ? 'Stock In' : 'Stock Out' }}</td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <div class="rule">--------------------------------------------------------------------------------------------------------------</div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 30%;">Description</th>
                    <th style="width: 13%;">Batch No</th>
                    <th style="width: 12%;">Exp Date</th>
                    <th style="width: 8%;">Qty</th>
                    <th style="width: 8%;">FOC</th>
                    <th style="width: 9%;">Price</th>
                    <th style="width: 8%;">Dis%</th>
                    <th style="width: 8%;">Value</th>
                    <th style="width: 8%;">R/Price</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stockEntry->products as $line)
                    @php($qty = (float) ($line->quantity ?? 0))
                    @php($price = (float) ($line->purchase_price ?? 0))
                    @php($value = $qty * $price)
                    @php($grandTotal += $value)
                    @php($retailPrice = (float) ($line->product?->retail_price ?? 0))
                    <tr>
                        <td>{{ strtoupper($line->product?->name ?? 'N/A') }}</td>
                        <td>{{ $stockEntry->entry_number }}</td>
                        <td>-</td>
                        <td>{{ rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') }}</td>
                        <td>0/0</td>
                        <td class="text-right">{{ number_format($price, 2) }}</td>
                        <td class="text-right">0.0%</td>
                        <td class="text-right">{{ number_format($value, 2) }}</td>
                        <td class="text-right">{{ number_format($retailPrice, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">NO ITEMS</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="rule">--------------------------------------------------------------------------------------------------------------</div>

        <table class="summary">
            <tr>
                <td style="width: 45%;">Total No of Line &nbsp;&nbsp;&nbsp;&nbsp;: {{ $lineCount }}</td>
                <td style="width: 35%;">Total Value</td>
                <td style="width: 20%;" class="text-right">{{ number_format($grandTotal, 2) }}</td>
            </tr>
            <tr>
                <td>Total No of Products : {{ $productCount }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td>Net Invoice Value</td>
                <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td>Net Value</td>
                <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>

        <div class="sp-3">Accepted above items in order</div>

        <div class="sign-row">
            <div class="sign-col">
                <div class="sign-line">Customer Signature</div>
            </div>
            <div class="sign-col">
                <div class="sign-line">Invoiced by</div>
            </div>
            <div class="sign-col">
                <div class="sign-line">Authorised by</div>
            </div>
        </div>
    </div>
</body>
</html>
