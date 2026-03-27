<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Return Invoice</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            background:#fff;
            color:#000;
            margin:40px;
        }
        .invoice-box{
            max-width:900px;
            margin:auto;
            border:1px solid #ccc;
            padding:30px;
        }
        h1,h2,h3{
            margin:0;
        }
        .header{
            text-align:center;
            border-bottom:2px solid #000;
            padding-bottom:10px;
            margin-bottom:20px;
        }
        table{
            width:100%;
            border-collapse:collapse;
            font-size:13px;
        }
        th,td{
            border:1px solid #ccc;
            padding:8px;
        }
        th{
            background:#f5f5f5;
            text-align:left;
        }
        .text-right{ text-align:right; }
        .text-center{ text-align:center; }

        .summary-table td{
            border:none;
            padding:6px;
        }

        .buttons{
            text-align:right;
            margin-bottom:15px;
        }
        .btn{
            padding:6px 12px;
            font-size:13px;
            border:none;
            cursor:pointer;
            border-radius:4px;
        }
        .btn-print{ background:#d9534f; color:#fff; }
        .btn-back{ background:#5bc0de; color:#fff; }

        @media print{
            .buttons{ display:none; }
            body{ margin:0; }
        }
    </style>
</head>

<body>

<div class="invoice-box">

    {{-- Buttons --}}
    <div class="buttons">
        <button class="btn btn-back" onclick="history.back()">← Back</button>
        <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
    </div>

    {{-- HEADER --}}
    <div class="header">
        <h1>WIJDAN</h1>
        <p>Exclusive Store</p>
        <p>Chandni Market, Salahuddin Road, Cantt Hyderabad</p>
        <p>Phone: +92 327 9226901</p>
    </div>

    <h2 class="text-center">Purchase Return Invoice</h2>
    <br>

    {{-- BASIC INFO --}}
    <table style="margin-bottom:20px;">
        <tr>
            <th>Return Invoice #</th>
            <td>{{ $purchase_return->return_invoice }}</td>
            <th>Date</th>
            <td>{{ \Carbon\Carbon::parse($purchase_return->return_date)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <th>Vendor</th>
            <td>{{ $purchase_return->vendor->name ?? 'N/A' }}</td>
            <th>Phone</th>
            <td>{{ $purchase_return->vendor->phone ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Address</th>
            <td colspan="3">{{ $purchase_return->vendor->address ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Warehouse</th>
            <td>{{ $purchase_return->warehouse->warehouse_name ?? 'N/A' }}</td>
            <th>Location</th>
            <td>{{ $purchase_return->warehouse->location ?? 'N/A' }}</td>
        </tr>
    </table>

    {{-- ITEMS TABLE --}}
    <h3>Returned Items</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Note</th>
                <th>Unit</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Disc / Pc</th>
                <th class="text-right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase_return->items as $i => $item)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $item->product->item_name ?? 'N/A' }}</td>
                <td>{{ $item->note }}</td>
                <td>{{ $item->unit }}</td>
                <td class="text-center">{{ $item->qty + 0 }}</td>
                <td class="text-right">{{ number_format($item->price,2) }}</td>
                <td class="text-right">{{ number_format($item->item_discount,2) }}</td>
                <td class="text-right">{{ number_format($item->line_total,2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SUMMARY --}}
    <table class="summary-table" style="margin-top:20px; width:40%; float:right;">
        <tr>
            <td class="text-right"><strong>Subtotal:</strong></td>
            <td class="text-right">{{ number_format($purchase_return->bill_amount,2) }}</td>
        </tr>
        <tr>
            <td class="text-right">Item Discount:</td>
            <td class="text-right">{{ number_format($purchase_return->item_discount,2) }}</td>
        </tr>
        <tr>
            <td class="text-right">Extra Discount:</td>
            <td class="text-right">{{ number_format($purchase_return->extra_discount,2) }}</td>
        </tr>
        <tr>
            <td class="text-right"><strong>Net Amount:</strong></td>
            <td class="text-right"><strong>{{ number_format($purchase_return->net_amount,2) }}</strong></td>
        </tr>
    </table>

    <div style="clear:both"></div>

    {{-- AMOUNT IN WORDS --}}
    <p style="margin-top:25px;">
        <strong>Amount in Words:</strong>
        Rupees <span id="amountWords"></span> Only
    </p>

    <div style="margin-top:40px; text-align:center; font-size:12px; border-top:1px solid #ccc; padding-top:10px;">
        Thank you for your business — <strong>WIJDAN Exclusive Store</strong>
    </div>

</div>

{{-- AMOUNT TO WORDS --}}
<script>
function numberToWords(num){
    const a=['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    const b=['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];

    if(num===0) return 'Zero';

    let str='';
    if(num>=100000){
        str+=a[Math.floor(num/100000)]+' Lakh ';
        num%=100000;
    }
    if(num>=1000){
        str+=a[Math.floor(num/1000)]+' Thousand ';
        num%=1000;
    }
    if(num>=100){
        str+=a[Math.floor(num/100)]+' Hundred ';
        num%=100;
    }
    if(num>0){
        str+=(str!==''?'and ':'')+(a[num]||b[Math.floor(num/10)]+' '+a[num%10]);
    }
    return str.trim();
}

document.addEventListener('DOMContentLoaded',()=>{
    const amount={{ round($purchase_return->net_amount) }};
    document.getElementById('amountWords').innerText=numberToWords(amount);
});
</script>

</body>
</html>
