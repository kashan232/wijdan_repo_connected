yeh recepit ka code 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt</title>
<style>
    body {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        color: #000;
        background: #fff;
        margin: 0;
        padding: 0;
    }
    .receipt-container {
        width: 100%;
        max-width: 340px;
        margin: auto;
        padding: 10px;
    }
    .center {
        text-align: center;
    }
    .bold {
        font-weight: bold;
    }
    .line {
        border-top: 1px dashed #000;
        margin: 4px 0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 2px 0;
    }
    th {
        text-align: left;
        font-size: 11px;
    }
    td {
        font-size: 11px;
    }
    td:last-child, th:last-child {
        text-align: right;
    }
    .footer {
        text-align: center;
        font-size: 11px;
        margin-top: 6px;
        border-top: 1px dashed #000;
        padding-top: 4px;
    }
</style>
</head>
<body>

<div class="receipt-container">

    <!-- Header -->
    <div class="center">
        <h2 style="margin:0;font-size:14px;" class="bold">WIJDAN</h2>
        <p style="margin:0;">EXCLUSIVE STORE</p>
        <p style="margin:0;">Chandni market, Salahuddin Road, Cantt, Hyderabad</p>
        <p style="margin:0;">Phone: +92 327 9226901</p>
    </div>

    <div class="line"></div>
    <div class="center bold">DELIVERY CHALLAN</div>
    <div class="line"></div>

    <!-- Details -->
    <table>
        <tr><th>Customer:</th><td> {{ $sale->customer_relation->customer_name ?? 'N/A' }}</td></tr>
        <tr><th>Reference:</th><td>{{ $sale->reference ?? 'N/A' }}</td></tr>
        <tr><th>Print Time:</th><td>{{ \Carbon\Carbon::parse($sale->created_at)->format('d M Y, h:i A') }}</td></tr>
    </table>

    <div class="line"></div>

    <!-- Items -->
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>code</th>
                <th>brand</th>
                <th>unit</th>
                <th>qty</th>
            </tr>
        </thead>
        <tbody>
             @foreach($saleItems as $index => $item)
                                        <tr>
                                            <td>{{ $index+1 }}</td>
                                            <td class="text-start">{{ $item['item_name'] }}</td>
                                            <td>{{ $item['item_code'] }}</td>
                                            <td>{{ $item['brand'] }}</td>
                                            <td>{{ $item['unit'] }}</td>
                                            <td class="fw-bold">{{ $item['qty'] }}</td>
                                        </tr>
                                        @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <!-- Totals -->
    <table>
        <tr><th>Total Pieces</th><td>6</td></tr>
        <tr><th>Sale Type</th><td>CASH</td></tr>
        <tr><th>Net Amount</th><td>8,700</td></tr>
        <tr><th>Cash</th><td>9,000</td></tr>
        <tr><th>Change</th><td>300</td></tr>
    </table>

    <div class="line"></div>
    <p><strong>Amount In Words:</strong><br>
    Rupees Eight Thousand Seven Hundred Only</p>

    <!-- Footer -->
    <div class="footer">
        <p>Always DRYCLEAN the fancy suits</p>
        <p>No Warranty of FANCY Suits</p>
        <p>*** Thank You! Visit Again ***</p>
    </div>
</div>

</body>
</html>