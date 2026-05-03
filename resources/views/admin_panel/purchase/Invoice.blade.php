<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 40px;
            background: #fff;
            color: #000;
        }

        .invoice-container {
            max-width: 800px;
            margin: auto;
            border: 1px solid #ddd;
            padding: 30px;
            position: relative;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .header p {
            margin: 3px 0;
            font-size: 13px;
        }

        .section-title {
            font-weight: 600;
            margin: 15px 0 5px 0;
            font-size: 15px;
            border-bottom: 1px solid #ccc;
            display: inline-block;
            padding-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        th {
            background: #f5f5f5;
            text-align: left;
        }

        .totals-table th {
            text-align: right;
        }

        .totals-table td {
            text-align: right;
        }

        .amount-words {
            margin-top: 20px;
            font-style: italic;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .buttons {
            text-align: right;
            margin-bottom: 20px;
        }

        .btn {
            background: #4ba064;
            color: #fff;
            border: none;
            padding: 8px 14px;
            margin-left: 6px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn:hover {
            background: #3d8b55;
        }

        @media print {
            .buttons {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <div class="invoice-container">

        <!-- Buttons -->
        <div class="buttons">
            <button class="btn" onclick="window.history.back()">← Back</button>
            <button class="btn" style="background-color: red;" onclick="window.print()">🖨 Print</button>
        </div>

        <div class="header">
            <h1>WIJDAN</h1>
            <p>Exclusive Store</p>
            <p>Chandni Market, Salahuddin Road, Cantt Hyderabad</p>
            <p>Phone: +92 327 9226901</p>
        </div>

        <h2 style="text-align:center; margin-bottom:20px;">Purchase Invoice</h2>

        <table style="margin-bottom:20px;">
            <tr>
                <th>Invoice # (Internal)</th>
                <td>{{ $purchase->invoice_no }}</td>
                <th>Company Inv #</th>
                <td>{{ $purchase->company_invoice_no ?? '-' }}</td>
            </tr>
            <tr>
                <th>Date</th>
                <td>{{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') : 'N/A' }}</td>
                <th>Vendor Name</th>
                <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Vendor Contact</th>
                <td>{{ $purchase->vendor->phone ?? 'N/A' }}</td>
                <th>Warehouse</th>
                <td>{{ $purchase->warehouse->warehouse_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Vendor Address</th>
                <td colspan="3">{{ $purchase->vendor->address ?? 'N/A' }}</td>
            </tr>
        </table>

        <span class="section-title">Item Details</span>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th>Note</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Discount (per pc)</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->item_name ?? 'N/A' }}</td>
                    <td>{{ $item->note ?? 'N/A' }}</td>
                    <td>{{ number_format($item->qty, 0) }}</td>
                    <td>{{ number_format($item->price, 0) }}</td>
                    <td>{{ number_format($item->item_discount, 0) }}</td>
                    <td>{{ number_format($item->line_total, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table" style="margin-top:20px;">
            <tr>
                <th style="text-align:left;">Subtotal:</th>
                <td>{{ number_format($purchase->subtotal ?? 0, 0) }}</td>
            </tr>

            <tr>
                <th style="text-align:left;">Discount (Overall):</th>
                <td>{{ number_format($purchase->discount ?? 0, 0) }}</td>
            </tr>

            <tr>
                <th style="text-align:left;">Extra Charges:</th>
                <td>{{ number_format($purchase->extra_cost ?? 0, 0) }}</td>
            </tr>

            <tr>
                <th style="text-align:left;"><strong>Grand Total:</strong></th>
                <td><strong>{{ number_format($purchase->net_amount ?? 0, 0) }}</strong></td>
            </tr>
        </table>


        <p class="amount-words">
            <strong>Amount in Words:</strong> Rupees <span id="amount-in-words">...</span> Only
        </p>

        <div class="footer">
            Thank you for your business. — WIJDAN Exclusive Store
        </div>
    </div>

    <script>
        // numberToWords supports integer rupees (no decimals).
        function numberToWords(num) {
            num = parseInt(num, 10) || 0;
            if (num === 0) return 'Zero';

            const a = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven',
                'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen',
                'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
            ];
            const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty',
                'Seventy', 'Eighty', 'Ninety'
            ];

            function twoDigit(n) {
                if (n < 20) return a[n];
                const tens = Math.floor(n / 10);
                const ones = n % 10;
                return b[tens] + (ones ? ' ' + a[ones] : '');
            }

            function threeDigit(n) {
                const hundred = Math.floor(n / 100);
                const rest = n % 100;
                let s = '';
                if (hundred) s += a[hundred] + ' Hundred';
                if (rest) s += (s ? ' and ' : '') + twoDigit(rest);
                return s;
            }

            let str = '';
            const crore = Math.floor(num / 10000000);
            num = num % 10000000;
            const lakh = Math.floor(num / 100000);
            num = num % 100000;
            const thousand = Math.floor(num / 1000);
            num = num % 1000;
            const hundreds = num; // 0-999

            if (crore) str += (twoDigit(crore) + ' Crore ');
            if (lakh) str += (twoDigit(lakh) + ' Lakh ');
            if (thousand) str += (twoDigit(thousand) + ' Thousand ');
            if (hundreds) str += (threeDigit(hundreds) + ' ');

            return str.trim();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const totalAmount = @json((int) round($purchase->net_amount ?? 0));

            // Convert to words and set into span, ensure "Only" outside span as in HTML
            const words = numberToWords(totalAmount);
            document.getElementById('amount-in-words').textContent = words || 'Zero';
        });
    </script>

</body>

</html>
