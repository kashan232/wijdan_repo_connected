<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Booking Receipt #{{ $booking->id }}</title>

    <style>
        /* ===== RESET ===== */
        * {
            box-sizing: border-box
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.25;
            font-weight: 500;
        }

        /* ===== CONTAINER ===== */
        .receipt-container {
            width: 100%;
            max-width: 80mm;
            margin: auto;
            position: relative;
            padding: 3mm 5mm 6mm;
        }

        /* ===== CORNER RIBBON (THERMAL SAFE) ===== */
        /* ===== ROTATED CORNER RIBBON (THERMAL SAFE) ===== */
        .ribbon-wrap {
            position: absolute;
            top: -2mm;
            right: 0;
            width: 70px;
            height: 70px;
            overflow: hidden;
            z-index: 99;
        }

        .ribbon {
            position: absolute;
            top: 20px;
            right: -32px;
            width: 128px;
            text-align: center;
            background: #000;
            color: #fff;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1px;
            padding: 5px 0;
            transform: rotate(45deg);
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* print safety */
        @media print {
            .ribbon {
                background: #000 !important;
                color: #fff !important;
            }
        }




        /* ===== COMMON ===== */
        .center {
            text-align: center
        }

        .bold {
            font-weight: 700
        }

        .line {
            border-top: 1px dashed #000;
            margin: 6px 0
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            padding: 2px 0;
            font-size: 11px;
        }

        th {
            text-align: left;
            font-weight: 700
        }

        td:last-child,
        th:last-child {
            text-align: right
        }

        /* ===== HEADER ===== */
        .title {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: .5px;
        }

        .subtitle {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
        }

        /* ===== ITEMS TABLE ===== */
        .items col:nth-child(1) {
            width: 38%
        }

        .items col:nth-child(2) {
            width: 12%
        }

        .items col:nth-child(3) {
            width: 20%
        }

        .items col:nth-child(4) {
            width: 30%
        }

        .items thead th {
            font-weight: 800;
            font-size: 11px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items th,
        .items td {
            padding: 3px 2px;
            font-size: 11px;
            vertical-align: top;
        }

        /* column widths */
        .items .col-item {
            width: 38%;
            word-break: break-word;
            white-space: normal;
        }

        .items .col-qty {
            width: 10%;
            text-align: center;
        }

        .total-items-row th,
        .total-items-row td {
            font-weight: 800;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
        }

        .items .col-unit {
            width: 10%;
            text-align: center;
        }

        .items .col-price {
            width: 20%;
            text-align: right;
        }

        .items .col-amount {
            width: 22%;
            text-align: right;
            font-weight: 700;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 8px;
            border-top: 1px dashed #000;
            padding-top: 6px;
            font-weight: 600;
        }

        /* ===== PRINT ===== */
        @media print {
            @page {
                size: 80mm auto;
                margin: 2mm;
                /* 🔥 pehle 5mm tha */
            }

            .receipt-container {
                padding-top: 1mm !important;
                /* 🔥 extra gap kill */
            }

            body {
                margin: 0
            }
        }

        .copy-label {
            text-align: center;
            font-weight: 800;
            font-size: 12px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    <div class="receipt-container">
        <div class="receipt-container">
            <div class="ribbon-wrap">
                <div class="ribbon">BOOKED</div>
            </div>
            <!-- ===== HEADER ===== -->
            <div class="center">
                <h2 class="title">WIJDAN</h2>
                <p class="subtitle">EXCLUSIVE STORE</p>
                <p style="margin:0">Chandni market, Salahuddin Road, Cantt, Hyderabad</p>
                <p style="margin:0">Phone: 022786661</p>
            </div>

            <div class="line"></div>
            <div class="center bold" style="font-size:13px">BOOKING RECEIPT</div>
            <div class="line"></div>

            <!-- ===== DETAILS ===== -->
            <table>
                <tr>
                    <th>Customer</th>
                    <td>{{ $booking->customer_relation->customer_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Reference</th>
                    <td>{{ $booking->reference ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Print Time</th>
                    <td>{{ \Carbon\Carbon::now()->format('d-m-Y H:i:s') }}</td>
                </tr>
            </table>

            <div class="line"></div>

            <!-- ===== ITEMS ===== -->
            <table class="items">
                <colgroup>
                    <col style="width:48%">
                    <col style="width:10%">
                    <col style="width:20%">
                    <col style="width:20%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-item">Item</th>
                        <th class="col-qty">Qty</th>
                        <th class="col-price">Price</th>
                        <th class="col-amount">Total</th>
                    </tr>
                </thead>


                <tbody>
                    @php
                    $products = explode(',', $booking->product);
                    $qtys = explode(',', $booking->qty);
                    $prices = explode(',', $booking->per_price);
                    $totals = explode(',', $booking->per_total);
                    @endphp

                    @foreach($products as $i => $pid)
                    @php $prod = \App\Models\Product::find($pid); @endphp
                    <tr>
                        <td class="col-item">{{ $prod->item_name ?? 'N/A' }}</td>

                        <td class="col-qty">
                            {{ $qtys[$i] ?? 1 }}
                        </td>

                        <!-- ✅ UOM with SAFE CONDITION -->
                        <td class="col-price">
                            {{ number_format($prices[$i] ?? 0, 0) }}
                        </td>

                        <td class="bold col-amount">
                            {{ number_format($totals[$i] ?? 0, 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="line"></div>

            <!-- ===== TOTALS ===== -->
            <table>
                @php
                $units = [
                'Pc' => $booking->total_pieces,
                'Mtr' => $booking->total_meter,
                'Yd' => $booking->total_yard,
                ];
                @endphp
                @foreach($units as $label => $value)
                @if(!empty($value) && $value > 0)
                <tr class="total-units-row total-items-row">
                    <th>{{ $label }}</th>
                    <td>{{ $value }}</td>
                </tr>
                @endif
                @endforeach

                <tr>
                    <th>Sale Type</th>
                    <td>{{ strtoupper($booking->sale_type ?? 'BOOKING') }}</td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td class="bold">{{ number_format($booking->total_net,0) }}</td>
                </tr>

                <tr>
                    <th>Advance Paid</th>
                    <td class="bold">
                        {{ number_format($booking->advance_payment ?? 0,0) }}
                    </td>
                </tr>

                @php
                $remaining = max(
                ($booking->total_net ?? 0) - ($booking->advance_payment ?? 0),
                0
                );
                @endphp

                <tr>
                    <th>Remaining Amount</th>
                    <td class="bold">{{ number_format($remaining,0) }}</td>
                </tr>
            </table>

            <div class="line"></div>

            <p class="bold" style="margin:0 0 4px">Amount In Words:</p>
            <p id="amountInWords" style="margin:0">Loading...</p>

            <!-- ===== FOOTER ===== -->
            <div class="footer">
                <p>Always DRYCLEAN the fancy suits</p>
                <p>No Warranty of FANCY Suits</p>
                <p>Developed By: ProWave Software Solutions</p>
                <p>+92 317 3836 223 | +92 317 3859 647</p>
                <p>*** Thank you for the visit ***</p>
            </div>

        </div>

    </div>

    <div class="page-break"></div>

    <div class="receipt-container">
        <div class="receipt-container">
            <div class="ribbon-wrap">
                <div class="ribbon">BOOKED</div>
            </div>
            <!-- ===== HEADER ===== -->
            <div class="center">
                <h2 class="title">WIJDAN</h2>
                <p class="subtitle">EXCLUSIVE STORE</p>
                <p style="margin:0">Chandni market, Salahuddin Road, Cantt, Hyderabad</p>
                <p style="margin:0">Phone: 022786661</p>
            </div>

            <div class="line"></div>
            <div class="center bold" style="font-size:13px">BOOKING RECEIPT</div>
            <div class="line"></div>

            <!-- ===== DETAILS ===== -->
            <table>
                <tr>
                    <th>Customer</th>
                    <td>{{ $booking->customer_relation->customer_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Reference</th>
                    <td>{{ $booking->reference ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Print Time</th>
                    <td>{{ \Carbon\Carbon::now()->format('d-m-Y H:i:s') }}</td>
                </tr>
            </table>

            <div class="line"></div>

            <!-- ===== ITEMS ===== -->
            <table class="items">
                <colgroup>
                    <col style="width:48%">
                    <col style="width:10%">
                    <col style="width:20%">
                    <col style="width:20%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-item">Item</th>
                        <th class="col-qty">Qty</th>
                        <th class="col-price">Price</th>
                        <th class="col-amount">Total</th>
                    </tr>
                </thead>


                <tbody>
                    @php
                    $products = explode(',', $booking->product);
                    $qtys = explode(',', $booking->qty);
                    $prices = explode(',', $booking->per_price);
                    $totals = explode(',', $booking->per_total);
                    @endphp

                    @foreach($products as $i => $pid)
                    @php $prod = \App\Models\Product::find($pid); @endphp
                    <tr>
                        <td class="col-item">{{ $prod->item_name ?? 'N/A' }}</td>

                        <td class="col-qty">
                            {{ $qtys[$i] ?? 1 }}
                        </td>
                       

                        <td class="col-price">
                            {{ number_format($prices[$i] ?? 0, 0) }}
                        </td>

                        <td class="bold col-amount">
                            {{ number_format($totals[$i] ?? 0, 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="line"></div>

            <!-- ===== TOTALS ===== -->
            <table>
                @php
                $units = [
                'Pc' => $booking->total_pieces,
                'Mtr' => $booking->total_meter,
                'Yd' => $booking->total_yard,
                ];
                @endphp
                @foreach($units as $label => $value)
                @if(!empty($value) && $value > 0)
                <tr class="total-units-row total-items-row">
                    <th>{{ $label }}</th>
                    <td>{{ $value }}</td>
                </tr>
                @endif
                @endforeach

                <tr>
                    <th>Sale Type</th>
                    <td>{{ strtoupper($booking->sale_type ?? 'BOOKING') }}</td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td class="bold">{{ number_format($booking->total_net,0) }}</td>
                </tr>

                <tr>
                    <th>Advance Paid</th>
                    <td class="bold">
                        {{ number_format($booking->advance_payment ?? 0,0) }}
                    </td>
                </tr>

                @php
                $remaining = max(
                ($booking->total_net ?? 0) - ($booking->advance_payment ?? 0),
                0
                );
                @endphp

                <tr>
                    <th>Remaining Amount</th>
                    <td class="bold">{{ number_format($remaining,0) }}</td>
                </tr>
            </table>

            <div class="line"></div>

            <p class="bold" style="margin:0 0 4px">Amount In Words:</p>
            <p id="amountInWords" style="margin:0">Loading...</p>

            <!-- ===== FOOTER ===== -->
            <div class="footer">
                <p>Always DRYCLEAN the fancy suits</p>
                <p>No Warranty of FANCY Suits</p>
                <p>Developed By: ProWave Software Solutions</p>
                <p>+92 317 3836 223 | +92 317 3859 647</p>
                <p>*** Thank you for the visit ***</p>
            </div>

        </div>

    </div>


    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // ===== CONFIG =====
            const returnTo = "{{ route('sale.add') }}"; // 👈 jahan back jana hai
            const autoPrint = true; // print open hote hi

            // ===== AMOUNT IN WORDS =====
            function numberToWords(num) {
                const o = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine",
                    "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen",
                    "Sixteen", "Seventeen", "Eighteen", "Nineteen"
                ];
                const t = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];

                if (num === 0) return "Zero";

                function c(n) {
                    let s = "";
                    if (n > 99) {
                        s += o[Math.floor(n / 100)] + " Hundred ";
                        n %= 100;
                    }
                    if (n > 19) {
                        s += t[Math.floor(n / 10)] + " " + o[n % 10];
                    } else {
                        s += o[n];
                    }
                    return s.trim();
                }

                let r = "";
                if (num >= 100000) r += c(Math.floor(num / 100000)) + " Lakh ";
                if (num >= 1000) r += c(Math.floor((num % 100000) / 1000)) + " Thousand ";
                r += c(num % 1000);
                return r.trim();
            }

            const amt = parseFloat("{{ $remaining ?? 0 }}") || 0;
            document.getElementById("amountInWords").innerText =
                "Rupees " + numberToWords(Math.floor(amt)) + " Only";

            // ===== AUTO PRINT + AUTO BACK =====
            if (autoPrint) {
                setTimeout(() => {
                    window.print();

                    const goBack = () => {
                        try {
                            window.location.href = returnTo;
                        } catch (e) {
                            if (history.length > 1) history.back();
                        }
                    };

                    // Chrome / modern browsers
                    if ('onafterprint' in window) {
                        window.onafterprint = goBack;
                    }

                    // fallback (agar onafterprint fire na ho)
                    setTimeout(goBack, 1500);

                }, 400);
            }

        });
    </script>

</body>

</html>
