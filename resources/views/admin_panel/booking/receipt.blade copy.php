<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Receipt #{{ $booking->id }}</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            position: relative;
        }
        .receipt-container {
            width: 100%;
            max-width: 340px;
            margin: auto;
            padding: 10px;
            position: relative;
            z-index: 2;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; font-size: 11px; }
        th { text-align: left; }
        td:last-child, th:last-child { text-align: right; }
        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 6px;
            border-top: 1px dashed #000;
            padding-top: 4px;
        }

        /* Professional Black Stamp */
        .stamp {
            position: absolute;
            top: 40%;
            left: 58%;
            width: 100px;
            height: 100px;
            margin-left: -50px;
            margin-top: -50px;
            {{--  border: 5px solid rgba(0,0,0,0.2);  --}}
            border-radius: 50%;
            text-align: center;
            color: rgba(0, 0, 0, 0.201);
            font-weight: bold;
            z-index: 1;
            pointer-events: none;
            font-size: 14px;
        }
        .stamp img {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            line-height: 14px;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<!-- Black Professional Stamp -->
<div class="stamp">
   <img src="{{ asset('assets/images/stampwijdan.png') }}">
</div>

<!-- Main Receipt -->
<div class="receipt-container">

    <!-- Header -->
    <div class="center">
        <h2 style="margin:0;font-size:14px;" class="bold">WIJDAN</h2>
        <p style="margin:0;">EXCLUSIVE STORE</p>
        <p style="margin:0;">Chandni market, Salahuddin Road, Cantt, Hyderabad</p>
        <p style="margin:0;">Phone: 022786661</p>
    </div>

    <div class="line"></div>
    <div class="center bold">BOOKING RECEIPT</div>
    <div class="line"></div>

    <!-- Booking Details -->
    <table>
        <tr><th>Customer:</th><td>{{ $booking->customer_relation->customer_name ?? 'N/A' }}</td></tr>
        <tr><th>Reference:</th><td>{{ $booking->reference }}</td></tr>
        <tr><th>Print Time:</th><td>{{ \Carbon\Carbon::now()->format('d-m-Y H:i:s') }}</td></tr>
    </table>

    <div class="line"></div>

    <!-- Products Table -->
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $products = explode(',', $booking->product);
                $qtys = explode(',', $booking->qty);
                $units = explode(',', $booking->unit);
                $prices = explode(',', $booking->per_price);
                $discounts = explode(',', $booking->per_discount);
                $totals = explode(',', $booking->per_total);
            @endphp
          @foreach($products as $i => $product_id)
    @php
        $prod = \App\Models\Product::find($product_id);
    @endphp
    <tr>
        <td>{{ $prod->item_name ?? 'N/A' }}</td>
        <td>{{ $qtys[$i] ?? '1' }}</td>
        <td>{{ $prices[$i] ?? '0' }}</td>
        <td>{{ $totals[$i] ?? '0' }}</td>
    </tr>
@endforeach

        </tbody>
    </table>

    <div class="line"></div>

    <!-- Totals -->
    <table>
        <tr><th>Total Pieces</th><td>{{ array_sum($qtys) }}</td></tr>
        <tr><th>Sale Type</th><td>{{ strtoupper($booking->sale_type ?? 'Booking') }}</td></tr>
        <tr><th>Net Amount</th><td>{{ $booking->total_net }}</td></tr>
    </table>

    <div class="line"></div>

    <!-- In Words -->
    <p><strong>Amount In Words:</strong><br>
    {{ $booking->total_amount_Words }}</p>

    <!-- Footer -->
    <div class="footer">
        <p>Always DRYCLEAN the fancy suits</p>
        <p>No Warranty of FANCY Suits</p>
        <p>*** Thank you for the visit
 ***</p>
    </div>
</div>

</body>
</html>
