    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inward Gatepass Invoice</title>
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

        @php
        $subtotal = 0;
        $inlineDiscount = 0;

        foreach ($gatepass->items as $item) {
        $price = $item->price ?? $item->product->wholesale_price ?? 0;
        $qty = $item->qty;

        $lineTotal = $price * $qty;
        $subtotal += $lineTotal;

        // ✅ Handle PKR and Percent
        $dVal = $item->discount_value ?? 0;
        $dType = $item->discount_type ?? 'pkr';

        $discountParams = 0;
        if($dType == 'percent') {
            $discountParams = ($price * $qty * $dVal) / 100;
        } else {
            $discountParams = $dVal * $qty;
        }
        $inlineDiscount += $discountParams;
        }

        // bottom discount (simple)
        $bottomDiscount = $gatepass->discount ?? 0;

        // total discount (inline + bottom)
        $totalDiscount = $inlineDiscount + $bottomDiscount;

        // extra cost
        $extraCost = $gatepass->extra_cost ?? 0;

        // final grand total
        $grandTotal = ($subtotal - $totalDiscount) + $extraCost;
        @endphp


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

            <h2 style="text-align:center; margin-bottom:20px;">Inward Gatepass Bill Invoice</h2>

            <table style="margin-bottom:20px;">
                <tr>
                    <th>Invoice #</th>
                    <td>{{ $gatepass->invoice_no ?? 'N/A' }}</td>
                    <th>Date</th>
                    <td>{{ $gatepass->gatepass_date ? \Carbon\Carbon::parse($gatepass->gatepass_date)->format('d M Y') : 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Builty #</th>
                    <td>{{ $gatepass->gatepass_no ?? 'N/A' }}</td>
                    <th>Company Inv #</th>
                    <td>{{ $gatepass->company_invoice_no ?? 'N/A' }}</td>
                    
                </tr>
                <tr>
                    <th>Vendor Name</th>
                    <td>{{ $gatepass->vendor->name ?? 'N/A' }}</td>
                    <th>Vendor Contact</th>
                    <td>{{ $gatepass->vendor->phone ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Transport</th>
                    <td>{{ $gatepass->transport_name ?? 'N/A' }}</td>
                    <th>Address</th>
                    <td colspan="3">{{ $gatepass->vendor->address ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Warehouse</th>
                    <td>{{ $gatepass->warehouse->warehouse_name ?? 'N/A' }}</td>
                    <th>Location</th>
                    <td>{{ $gatepass->warehouse->location ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Note</th>
                    <td>{{ $gatepass->remarks ?? 'N/A' }}</td>
                    
                </tr>
                
            </table>

            <span class="section-title">Item Details</span>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Name</th>
                        <th>Location</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Discount</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gatepass->items as $index => $item)
                    @php
                    $price = $item->price ?? $item->product->wholesale_price ?? 0;
                    $qty = $item->qty;

                    $lineTotal = $price * $qty;

                    // Calculate discount based on type
                    $dVal = $item->discount_value ?? 0;
                    $dType = $item->discount_type ?? 'pkr';
                    
                    $itemDiscountTotal = 0;
                    if($dType == 'percent') {
                        $itemDiscountTotal = ($price * $qty * $dVal) / 100;
                    } else {
                        $itemDiscountTotal = $dVal * $qty;
                    }

                    $netItemTotal = $lineTotal - $itemDiscountTotal;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->product->item_name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($item->receive_type ?? $gatepass->receive_type) }}</td>
                        <td>{{ $qty }}</td>
                        <td>{{ number_format($price, 2) }}</td>
                        <td>
                            @if(($item->discount_type ?? 'pkr') == 'percent')
                                {{ $item->discount_value }}% ({{ number_format($itemDiscountTotal, 2) }})
                            @else
                                Rs {{ number_format($item->discount_value ?? 0, 2) }}
                            @endif
                        </td>
                        <td>{{ number_format($netItemTotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>


            </table>

            <table class="totals-table" style="margin-top:20px;">
                <tr>
                    <th>Subtotal:</th>
                    <td>{{ number_format($subtotal, 2) }}</td>
                </tr>

                <tr>
                    <th>Inline Discount:</th>
                    <td>{{ number_format($inlineDiscount, 2) }}</td>
                </tr>

                <tr>
                    <th>Discount:</th>
                    <td>{{ number_format($bottomDiscount, 2) }}</td>
                </tr>

                <tr>
                    <th><strong>Overall Discount:</strong></th>
                    <td><strong>{{ number_format($totalDiscount, 2) }}</strong></td>
                </tr>

                <tr>
                    <th>Extra Cost:</th>
                    <td>{{ number_format($extraCost, 2) }}</td>
                </tr>

                <tr>
                    <th><strong>Grand Total:</strong></th>
                    <td><strong>{{ number_format($grandTotal, 2) }}</strong></td>
                </tr>
            </table>



            <div class="footer">
                Thank you for your business. — WIJDAN Exclusive Store
            </div>
        </div>





        @section('scripts')

        <script>
            function numberToWords(num) {
                const a = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven',
                    'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen',
                    'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
                ];
                const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty',
                    'Seventy', 'Eighty', 'Ninety'
                ];

                if ((num = num.toString()).length > 9) return 'Overflow';
                let n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
                if (!n) return '';
                let str = '';
                str += (n[1] != 0) ? (a[Number(n[1])] || (b[n[1][0]] + ' ' + a[n[1][1]])) + ' Crore ' : '';
                str += (n[2] != 0) ? (a[Number(n[2])] || (b[n[2][0]] + ' ' + a[n[2][1]])) + ' Lakh ' : '';
                str += (n[3] != 0) ? (a[Number(n[3])] || (b[n[3][0]] + ' ' + a[n[3][1]])) + ' Thousand ' : '';
                str += (n[4] != 0) ? (a[Number(n[4])] + ' Hundred ') : '';
                str += (n[5] != 0) ? ((str != '') ? 'and ' : '') +
                    (a[Number(n[5])] || (b[n[5][0]] + ' ' + a[n[5][1]])) + ' ' : '';
                return str.trim();
            }

            document.addEventListener('DOMContentLoaded', function() {
                const totalAmount = {
                    {
                        round($gatepass - > net_amount ?? 0)
                    }
                };
                document.getElementById('amount-in-words').textContent = numberToWords(totalAmount);
            });
        </script>
        @endsection


    </body>

    </html>
