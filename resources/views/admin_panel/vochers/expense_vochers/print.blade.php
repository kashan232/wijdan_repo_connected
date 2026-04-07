{{-- resources/views/admin_panel/expense/receipt.blade.php --}}
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Expense Receipt - {{ $voucher->evid ?? '' }}</title>

  <style>
    * {
      box-sizing: border-box;
    }

    html,
    body {
      margin: 0;
      padding: 0;
      background: #fff;
      color: #000;
      font-family: Arial, Helvetica, sans-serif;
    }

    body {
      font-size: 12px;
      line-height: 1.25;
      font-weight: 600;
    }

    /* Container sized for 80mm thermal receipts */
    .receipt {
      max-width: 80mm;
      margin: 6px auto;
      padding: 6mm 5mm;
    }

    .center {
      text-align: center;
    }

    .title {
      font-size: 16px;
      font-weight: 800;
      margin: 0;
    }

    .subtitle {
      font-size: 11px;
      font-weight: 700;
      margin: 0;
    }

    .muted {
      font-weight: 700;
      color: #000;
      font-size: 11px;
    }

    .line {
      border-top: 1px dashed #000;
      margin: 6px 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    th,
    td {
      padding: 3px 0;
      vertical-align: top;
      word-wrap: break-word;
      font-size: 11px;
    }

    th {
      text-align: left;
      font-weight: 700;
    }

    td:last-child,
    th:last-child {
      text-align: right;
    }

    .meta {
      margin-top: 4px;
    }

    .meta .row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 4px;
      font-weight: 700;
      font-size: 11px;
    }

    .items thead th {
      font-size: 11px;
    }

    .items td {
      font-size: 11px;
      padding: 2px 0;
    }

    .items .right {
      text-align: right;
    }

    .totals {
      margin-top: 6px;
      font-weight: 700;
    }

    .totals td {
      padding: 4px 0;
      font-size: 12px;
    }

    .totals .label {
      font-weight: 600;
    }

    .totals .value {
      text-align: right;
    }

    .footer {
      margin-top: 8px;
      text-align: center;
      font-size: 11px;
      font-weight: 700;
      border-top: 1px dashed #000;
      padding-top: 6px;
      line-height: 1.2;
    }

    .footer .small {
      font-weight: 600;
      font-size: 10px;
    }

    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 6px;
      margin: 6px 5mm 0;
    }

    .btn {
      border: 1px solid #000;
      padding: 6px 8px;
      font-weight: 700;
      background: #f5f5f5;
      border-radius: 6px;
      cursor: pointer;
      font-size: 12px;
    }

    /* Print rules for receipt printers */
    @media print {
      @page {
        size: 80mm auto;
        margin: 4mm;
      }

      .actions {
        display: none !important;
      }

      body {
        margin: 0;
      }

      .receipt {
        padding: 2mm;
      }
    }
  </style>
</head>

<body>
  <div class="actions">
    <button class="btn" id="btnBack" type="button">Back</button>
    <button class="btn" id="btnPrint" type="button">Print</button>
  </div>

  <div class="receipt" id="receipt">
    <div class="center">
      <h1 class="title">WIJDAN</h1>
      <div class="subtitle">EXCLUSIVE STORE</div>
      <div class="muted">Chandni market, Salahuddin Road, Cantt, Hyderabad</div>
      <div class="muted">Phone: 022786661</div>
    </div>

    <div class="line"></div>

    <div class="center" style="font-size:13px; font-weight:800;">EXPENSE RECEIPT</div>

    <div class="meta">
      <div class="row">
        <div>Voucher No:</div>
        <div>{{ $voucher->evid ?? '-' }}</div>
      </div>

      <div class="row">
        <div>Receipt Date:</div>
        <div>{{ \Carbon\Carbon::parse($voucher->receipt_date ?? now())->format('d-M-Y') }}</div>
      </div>
      <div class="row">
        <div>Date & Time:</div>
        <div>{{ \Carbon\Carbon::parse($voucher->created_at)->format('d-M-Y h:i A') }}</div>
      </div>
    </div>

    <div class="line"></div>

    <table>
      <tr>
        <td style="width:60%; font-weight:700;">Account</td>
        <td style="width:40%; text-align:right;">
          {{ $party->name ?? '-' }}
        </td>
      </tr>
      <tr>
        <td>Code</td>
        <td style="text-align:right;">{{ $party->phone ?? '-' }}</td>
      </tr>
    </table>

    <div class="line"></div>

    <table class="items">
      <colgroup>
        <col style="width:60%;" />
        <col style="width:20%;" />
      </colgroup>
      <thead>
        <tr>
          <th>Description</th>
          <th style="text-align:right; font-weight:900;">Amount</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $row)
        <tr>
          <td style="text-align:left; font-weight:600;">
            {{ $row['description'] }}
          </td>
          <td style="text-align:right; font-weight:900;">
            {{ number_format($row['amount'], 2) }}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="line"></div>

    <table class="totals" style="width:100%;">
      <tr>
        <td class="label">Expense Amount</td>
        <td class="value">-{{ number_format($voucher->total_amount,2) }}</td>
      </tr>
    </table>

    <div class="line"></div>

    <p style="margin:4px 0 2px 0; font-weight:700;">Amount In Words:</p>
    <p id="amountInWords" style="margin:0; font-weight:700;">Loading...</p>

    <!-- <div class="footer">
      <div>Printed: {{ now()->format('H:i:s, d M Y') }}</div>

      <div style="margin-top:6px;">
        <div>Develop By: ProWave Software Solutions</div>
        <div>+92 317 3836 223 | +92 317 3859 647</div>
        <div style="margin-top:6px; font-weight:800;">*** Thank you for the visit ***</div>
      </div>
    </div> -->
  </div>

  <script>
    function numberToWords(num) {
      const ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];
      const tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
      if (num === 0) return "Zero";

      function convert_hundred(n) {
        let s = "";
        if (n > 99) {
          s += ones[Math.floor(n / 100)] + " Hundred ";
          n = n % 100;
        }
        if (n > 19) {
          s += tens[Math.floor(n / 10)] + (n % 10 ? " " + ones[n % 10] : "");
        } else {
          s += ones[n];
        }
        return s.trim();
      }
      let crore = Math.floor(num / 10000000);
      let lakh = Math.floor((num % 10000000) / 100000);
      let thousand = Math.floor((num % 100000) / 1000);
      let hundred = num % 1000;
      let str = "";
      if (crore) str += convert_hundred(crore) + " Crore ";
      if (lakh) str += convert_hundred(lakh) + " Lakh ";
      if (thousand) str += convert_hundred(thousand) + " Thousand ";
      if (hundred) str += convert_hundred(hundred);
      return str.trim();
    }

    document.addEventListener('DOMContentLoaded', function() {
      const amt = Math.floor(parseFloat("{{ $voucher->total_amount ?? 0 }}") || 0);
      const words = numberToWords(amt);
      document.getElementById('amountInWords').innerText = "Rupees " + (words || "Zero") + " Only";

      // Buttons
      const printBtn = document.getElementById('btnPrint');
      if (printBtn) printBtn.addEventListener('click', () => window.print());

      const backBtn = document.getElementById('btnBack');
      if (backBtn) backBtn.addEventListener('click', () => {
        if (history.length > 1) history.back();
        else window.close();
      });
    });
  </script>
</body>

</html>