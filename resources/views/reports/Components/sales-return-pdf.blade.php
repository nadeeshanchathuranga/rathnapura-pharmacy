<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Return Bill</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 10px; }
        .company { text-align: center; margin-bottom: 10px; }
        .meta { margin: 10px 0; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #4a5568; color: white; padding: 8px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .section-title { margin-top: 16px; font-weight: bold; }
        .summary { margin-top: 12px; }
        .badge { display: inline-block; padding: 3px 6px; border-radius: 4px; font-size: 10px; color: #fff; }
        .badge-blue { background: #3b82f6; }
        .badge-green { background: #22c55e; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #e2e8f0; background-color: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Sales Return Bill</h2>
    </div>

    <div class="company">
        <div><strong>{{ $company->company_name ?? 'Company' }}</strong></div>
        <div>{{ $company->address ?? '' }}</div>
        <div>{{ $company->phone ?? '' }} {{ $company->email ? ' | ' . $company->email : '' }}</div>
    </div>

    <div class="meta">
        <div><strong>Return No:</strong> {{ $returnNo }}</div>
        <div><strong>Date:</strong> {{ $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('Y-m-d') : now()->format('Y-m-d') }}</div>
        <div><strong>Customer:</strong> {{ $return->customer->name ?? 'Walk-in Customer' }} {{ $return->customer->phone_number ? '(' . $return->customer->phone_number . ')' : '' }}</div>
        <div><strong>Invoice:</strong> {{ $return->sale->invoice_no ?? 'N/A' }}</div>
        <div>
            <strong>Type:</strong>
            <span class="badge {{ $return->return_type === 2 ? 'badge-green' : 'badge-blue' }}">
                {{ $return->return_type === 2 ? 'Cash Refund' : 'Product Return' }}
            </span>
        </div>
        @if($return->notes)
            <div><strong>Notes:</strong> {{ $return->notes }}</div>
        @endif
    </div>

    @if($return->products && $return->products->count() > 0)
        <div class="section-title">Returned Items</div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Barcode</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->products as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->product->barcode ?? '' }}</td>
                        <td class="text-right">{{ (int)$item->quantity }}</td>
                        <td class="text-right">{{ $currency }} {{ number_format((float)$item->price, 2) }}</td>
                        <td class="text-right">{{ $currency }} {{ number_format((float)$item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($return->replacements && $return->replacements->count() > 0)
        <div class="section-title">Replacement Items</div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Barcode</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->replacements as $rep)
                    @php
                        $unit = $rep->unit_price ?? ($rep->product->retail_price ?? 0);
                        $rowTotal = (float)$unit * (int)$rep->quantity;
                    @endphp
                    <tr>
                        <td>{{ $rep->product->name ?? 'N/A' }}</td>
                        <td>{{ $rep->product->barcode ?? '' }}</td>
                        <td class="text-right">{{ (int)$rep->quantity }}</td>
                        <td class="text-right">{{ $currency }} {{ number_format((float)$unit, 2) }}</td>
                        <td class="text-right">{{ $currency }} {{ number_format((float)$rowTotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="summary">
        <table>
            <tbody>
                <tr>
                    <td><strong>Returned Total</strong></td>
                    <td class="text-right">{{ $currency }} {{ number_format((float)$returnedTotal, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Replacement Total</strong></td>
                    <td class="text-right">{{ $currency }} {{ number_format((float)$replacementTotal, 2) }}</td>
                </tr>
                @if($return->return_type === \App\Models\SalesReturn::TYPE_CASH_RETURN)
                    <tr>
                        <td><strong>Refund Method</strong></td>
                        <td class="text-right">{{ ucfirst($return->refund_method ?? 'cash') }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>{{ $balanceLabel }}</strong></td>
                    <td class="text-right">{{ $currency }} {{ number_format((float)$netDisplayAmount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
