<!DOCTYPE html>
<html>
<head>
    <title>Customer Payment Receipt</title>

    <!-- html2canvas for download screenshot -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        body {
            width: 80mm;
            font-family: monospace;
            font-size: 12px;
            margin: 0;
            padding: 8px;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; }
        td { padding: 2px 0; }
        button { width: 100%; padding: 6px; margin-top: 8px; font-size: 12px; }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div id="receipt">

    <div class="center bold">WIJDAN EXCLUSIVE</div>
    <div class="center">Customer Payment Receipt</div>
    <div class="center">{{ now()->format('d-m-Y h:i A') }}</div>

    <div class="line"></div>

    <table>
        <tr>
            <td>Recieved No:</td>
            <td class="bold">{{ $payment->received_no }}</td>
        </tr>
        <tr>
            <td>Customer:</td>
            <td class="bold">{{ $payment->customer->customer_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Date:</td>
            <td>{{ $payment->payment_date }}</td>
        </tr>
        <tr>
            <td>Method:</td>
            <td>{{ ucfirst($payment->payment_method) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <table>
        <tr>
            <td class="bold">Amount:</td>
            <td class="bold" align="right">Rs {{ number_format($payment->amount, 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div>
        <strong>Amount in Words:</strong><br>
        <span id="amountWords"></span>
    </div>

    <div class="line"></div>

    <div>
        Note:<br>
        {{ $payment->note ?? '-' }}
    </div>

    <div class="line"></div>

    <div class="center">
        Paid To <strong>Wijdan Exclusive</strong>
    </div>
    <div class="center">Thank You</div>

</div>

<div class="no-print">
    <button onclick="window.print()">🖨 Print Receipt</button>
    <button onclick="downloadReceipt()">📸 Download Screenshot</button>
</div>

<script>
    function downloadReceipt() {
        html2canvas(document.getElementById('receipt'), { scale: 2 }).then(canvas => {
            let link = document.createElement('a');
            link.download = 'payment-receipt-{{ $payment->id }}.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    }

    function numberToWords(num) {
        const a = ['', 'One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        const b = ['', '', 'Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        if (num === 0) return 'Zero';
        function inWords(n){
            if(n<20) return a[n];
            if(n<100) return b[Math.floor(n/10)] + ' ' + a[n%10];
            if(n<1000) return a[Math.floor(n/100)] + ' Hundred ' + inWords(n%100);
            if(n<100000) return inWords(Math.floor(n/1000)) + ' Thousand ' + inWords(n%1000);
            if(n<10000000) return inWords(Math.floor(n/100000)) + ' Lakh ' + inWords(n%100000);
            return '';
        }
        return inWords(num);
    }

    document.addEventListener('DOMContentLoaded', function() {
        let amount = parseInt("{{ (int)$payment->amount }}");
        document.getElementById('amountWords').innerText = numberToWords(amount) + ' Rupees Only';
    });
</script>

</body>
</html>
