<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inward Gatepass Receipt</title>
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

        <h2 style="text-align:center; margin-bottom:20px;">Inward Gatepass Receipt</h2>

        <table style="margin-bottom:20px;">
            <tr>
                <th>Receipt #</th>
                <td>{{ $gatepass->invoice_no ?? 'N/A' }}</td>
                <th>Date</th>
                <td>{{ $gatepass->gatepass_date ? \Carbon\Carbon::parse($gatepass->gatepass_date)->format('d M Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Builty #</th>
                <td>{{ $gatepass->gatepass_no ?? 'N/A' }}</td>
                <th>Transport</th>
                <td>{{ $gatepass->transport_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Vendor</th>
                <td>{{ $gatepass->vendor->name ?? 'N/A' }}</td>
                <th>Contact</th>
                <td>{{ $gatepass->vendor->phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Warehouse</th>
                <td>{{ $gatepass->warehouse->warehouse_name ?? 'N/A' }}</td>
                <th>Location</th>
                <td>{{ $gatepass->warehouse->location ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Remarks</th>
                <td>{{ $gatepass->remarks ?? 'N/A' }}</td>
            </tr>
        </table>

        <span class="section-title">Received Items</span>
        @php
        $totalYard = 0;
        $totalPiece = 0;
        $totalMeter = 0;
        @endphp

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th>UOM</th>
                    <th>Qty</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gatepass->items as $index => $item)

                @php
                if ($item->product->unit_id == 'Yard') {
                $totalYard += $item->qty;
                } elseif ($item->product->unit_id == 'Piece') {
                $totalPiece += $item->qty;
                } elseif ($item->product->unit_id == 'Meter') {
                $totalMeter += $item->qty;
                }
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->item_name ?? 'N/A' }}</td>
                    <td>{{ $item->product->unit_id ?? 'N/A' }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ $item->note }}</td>
                </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">Totals</th>
                    <th>Piece</th>
                    <th>Meter</th>
                    <th>Yard</th>
                </tr>
                <tr>
                    <th colspan="2"></th>
                    <th>{{ $totalPiece }}</th>
                    <th>{{ $totalMeter }}</th>
                    <th>{{ $totalYard }}</th>
                </tr>
            </tfoot>

        </table>

        @if($gatepass->note)
        <p style="margin-top:15px;"><strong>Note:</strong> {{ $gatepass->note }}</p>
        @endif

        <div class="footer">
            This is a system-generated receipt — No signature required. <br>
            Thank you — WIJDAN Exclusive Store
        </div>
    </div>

</body>

</html>