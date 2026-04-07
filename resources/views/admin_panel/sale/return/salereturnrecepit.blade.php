<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SALE RETURN INVOICE</title>
  <style>
    /* Reset & safety */
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
      font-size: 12px;
      line-height: 1.25;
      font-weight: 600;
    }

    /* Buttons row */
    .actions {
      max-width: 80mm;
      margin: 10px auto 0;
      display: flex;
      gap: 8px;
      justify-content: flex-end;
      padding: 0 5mm;
    }

    tbody td {
      padding: 3px 2px;
      vertical-align: top;
    }

    /* Column alignments */
    .col-item {
      text-align: left;
      white-space: normal !important;
      word-break: break-word;
      overflow-wrap: anywhere;
      line-height: 1.3;
    }

    .col-qty,
    .col-unit,
    .col-price,
    .col-disc,
    .col-amount {
      white-space: nowrap;
    }

    .col-qty {
      text-align: center;
    }

    .col-unit {
      text-align: center;
    }

    .col-price {
      text-align: right;
    }

    .col-disc {
      text-align: right;
    }

    .col-amount {
      text-align: right;
    }

    .btn {
      border: 1px solid #000;
      background: #f5f5f5;
      padding: 6px 10px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      border-radius: 6px;
    }

    .btn:active {
      transform: translateY(1px);
    }

    /* Container – sized for 80mm roll, but responsive on screen */
    .receipt-container {
      width: 100%;
      max-width: 80mm;
      margin: 0 auto;
      padding: 3mm 5mm 6mm;
    }

    .center {
      text-align: center;
    }

    .bold {
      font-weight: 700;
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
      padding: 2px 0;
      vertical-align: top;
      word-wrap: break-word;
    }

    th {
      text-align: left;
      font-size: 11px;
      font-weight: 700;
    }

    td {
      font-size: 11px;
    }

    td:last-child,
    th:last-child {
      text-align: right;
    }

    /* Make headings & totals stand out */
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

    .totals th,
    .totals td {
      padding: 3px 0;
    }

    .items-unit {
      text-align: center;
      white-space: nowrap;
      padding-right: 6px;
    }

    .items-price {
      text-align: right;
      white-space: nowrap;
      padding-left: 6px;
    }

    .totals tr:last-child td,
    .totals tr:last-child th {
      font-weight: 800;
    }

    .footer {
      text-align: center;
      font-size: 11px;
      margin-top: 8px;
      border-top: 1px dashed #000;
      padding-top: 6px;
      font-weight: 600;
    }

    /* Print-safe margins so sides don’t cut */
    @media print {
      @page {
        size: 80mm auto;
        margin: 2mm;
      }

      body {
        margin: 0;
      }

      .actions {
        display: none !important;
      }

      .receipt-container {
        max-width: none;
        padding: 5px;
        padding-top: 1mm !important;
      }
    }

    .items-summary {
      border: 1.5px solid #000;
      padding: 6px;
      margin-top: 6px;
      margin-bottom: 6px;
      background: #f8f8f8;
    }

    .items-summary table {
      margin: 0;
    }

    .items-summary th,
    .items-summary td {
      padding: 4px 0;
      font-size: 12px;
    }

    .total-units-row span {
      font-size: 12px;
      text-transform: uppercase;
    }

    /* Extra emphasis on Total Items */
    .total-items-row th,
    .total-items-row td {
      font-weight: 800;
      border-bottom: 1px dashed #000;
      padding-bottom: 6px;
    }

    /* Grand / Net emphasis */
    .final-amount th,
    .final-amount td {
      font-weight: 900;
      font-size: 13px;
    }

    #barcode {
      display: block;
      margin: 0 auto;
      max-width: 40mm;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>

<body>

  <!-- Action Buttons -->
  <div class="actions">
    <button class="btn" id="btnBack" type="button">Back</button>
    <button class="btn" id="btnPrint" type="button">Print</button>
  </div>

  <div class="receipt-container">
    <!-- Header -->
    <div class="center">
      <h2 class="title">WIJDAN</h2>
      <p class="subtitle">EXCLUSIVE STORE</p>
      {{-- Address removed as per user request --}}
    </div>

    <div class="line"></div>
    <div class="center">
      <div style="font-weight: bold; font-size: 18px; margin-bottom: 5px;">SALE RETURN INVOICE</div>
      <svg id="barcode"></svg>
    </div>

    <div class="line"></div>
    <!-- Details -->
    <table>
      <tr>
        <th>Inv Number</th>
        <td>{{ $return->id }}</td>
      </tr>
      <tr>
        <th>Date</th>
        <td>{{ optional($return->created_at)->format('d-m-Y h:i A') ?? 'N/A' }}</td>
      </tr>
      <tr>
        <th>Customer</th>
        <td>{{ $return->sale->customer_relation->customer_name ?? 'N/A' }}</td>
      </tr>

      @if($return->sale->customer != 'Walk-in Customer')
      <tr>
        <th>Mobile</th>
        <td>{{ $return->sale->customer_relation->mobile ?? 'N/A' }}</td>
      </tr>
      @endif

      <tr>
        <th>Reference</th>
        <td>{{ $return->reference ?? '-' }}</td>
      </tr>
    </table>


    <div class="line"></div>

    <!-- Items -->
    @php
    $hasDiscount = false;
    foreach($returnItems as $item){
    if((float)$item['discount'] > 0){
    $hasDiscount = true;
    break;
    }
    }
    @endphp
    <table>
      <colgroup>
        <col style="width:36%;">
        <col style="width:9%;">
        <col style="width:10%;">
        <col style="width:15%;">
        @if($hasDiscount)
        <col style="width:10%;">
        @endif
        <col style="width:20%;">
      </colgroup>

      <thead>
        <tr class="bold">
          <th class="col-item">Item</th>
          <th class="col-qty">Qty</th>
          <th class="col-unit">UOM</th>
          <th class="col-price">Price</th>
          @if($hasDiscount)
          <th class="col-disc">Disc</th>
          @endif
          <th class="col-amount">Amount</th>
        </tr>
      </thead>

      <tbody>
        @foreach($returnItems as $item)
        <tr>
          <td class="col-item">{{ $item['item_name'] }}</td>
          <td class="col-qty">{{ $item['qty'] }}</td>
          <td class="col-unit">
            @php
            $unitRaw = $item['unit'];
            if (is_array($unitRaw)) {
                $unitRaw = $unitRaw[0] ?? '';
            }
            $unit = strtolower(trim($unitRaw));
            if (in_array($unit, ['meter','metre','mtr'])) {
                $unitShort = 'mtr';
            } elseif (in_array($unit, ['piece','pieces','pisces','pcs'])) {
                $unitShort = 'pcs';
            } elseif (in_array($unit, ['yard','yards','yd'])) {
                $unitShort = 'yd';
            } else {
                $unitShort = $unitRaw;
            }
            @endphp
            {{ $unitShort }}
          </td>
          <td class="col-price">
            {{ rtrim(rtrim(number_format($item['price'], 2), '0'), '.') }}
          </td>
          @if($hasDiscount)
          <td class="col-disc">
              {{ rtrim(rtrim(number_format($item['discount'], 2), '0'), '.') }}
          </td>
          @endif
          <td class="col-amount bold">
            {{ rtrim(rtrim(number_format($item['total'], 2), '0'), '.') }}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="line"></div>
    
      <!-- Totals -->
      <div class="items-summary">
        <table class="totals">
          @foreach($unitTotals as $label => $value)
          @if($value > 0)
          <tr class="total-units-row total-items-row">
            <th>{{ $label }}</th>
            <td>{{ $value }}</td>
          </tr>
          @endif
          @endforeach
          <tr>
            <th>Grand Total</th>
            <td class="bold">
              {{ rtrim(rtrim(number_format($return->total_bill_amount ?? 0, 2), '0'), '.') }}
            </td>
          </tr>

          @if(!empty($return->total_extradiscount) && $return->total_extradiscount > 0)
          <tr>
            <th>Extra Discount</th>
            <td>
              {{ rtrim(rtrim(number_format($return->total_extradiscount, 2), '0'), '.') }}
            </td>
          </tr>
          @endif

          <tr>
            <th>Net Amount</th>
            <td class="bold">
              {{ rtrim(rtrim(number_format($return->total_net ?? 0, 2), '0'), '.') }}
            </td>
          </tr>

          <tr>
            <th>Cash</th>
            <td>
              {{ rtrim(rtrim(number_format($return->cash ?? 0, 2), '0'), '.') }}
            </td>
          </tr>
          
          @if(($return->card ?? 0) > 0)
          <tr>
            <th>Card</th>
            <td>
              {{ rtrim(rtrim(number_format($return->card, 2), '0'), '.') }}
            </td>
          </tr>
          @endif

          <tr>
            <th>Change</th>
            <td>
              {{ rtrim(rtrim(number_format($return->change ?? 0, 2), '0'), '.') }}
            </td>
          </tr>
        </table>
      </div>

      <div class="line"></div>

      <p class="bold" style="margin:0 0 4px 0;">Amount In Words:</p>
      <p id="amountInWords" style="margin:0;">Loading...</p>

      <!-- Footer -->
      <div class="footer">
        <p>Always DRYCLEAN the fancy suits</p>
        <p>No Warranty of FANCY Suits</p>
        <p>Exchange allowed from the same branch only.</p>
        <p>Develop By: ProWave Software Solutions</p>
        <p>+92 317 3836 223 | +92 317 3859 647</p>
        <p>*** Thank you for the visit ***</p>
    </div>
  </div>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // 🏷️ Barcode Generation
      JsBarcode("#barcode", "{{ $return->id }}", {
        format: "CODE128",
        lineColor: "#000",
        width: 1,
        height: 20,
        displayValue: false,
        fontSize: 12,
        fontOptions: "bold",
        margin: 0
      });

      // Grab return_to and autoprint from query string
      const query = new URLSearchParams(window.location.search);
      const returnTo = query.get('return_to') || "{{ route('sale.index') }}"; 
      const autoprint = query.get('autoprint') === '1' || true; // Default to true for return as per existing logic

      // Print button
      document.getElementById('btnPrint').addEventListener('click', () => window.print());

      // Back button
      document.getElementById('btnBack').addEventListener('click', () => {
        if (returnTo) {
          try {
            window.location.href = decodeURIComponent(returnTo);
          } catch (e) {
            if (history.length > 1) history.back();
            else window.close();
          }
        } else {
          if (history.length > 1) history.back();
          else window.close();
        }
      });

      // Show amount in words (your existing code)
      const amount = parseFloat(`{{ $return->total_net ?? 0 }}`) || 0;
      const amountInWords = (function numberToWords(num) {
        const ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine",
          "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen",
          "Sixteen", "Seventeen", "Eighteen", "Nineteen"
        ];
        const tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
        if (num === 0) return "Zero";

        function convert_hundreds(n) {
          let str = "";
          if (n > 99) {
            str += ones[Math.floor(n / 100)] + " Hundred ";
            n %= 100;
          }
          if (n > 19) {
            str += tens[Math.floor(n / 10)] + " " + ones[n % 10];
          } else {
            str += ones[n];
          }
          return str.trim();
        }
        let crore = Math.floor(num / 10000000);
        let lakh = Math.floor((num % 10000000) / 100000);
        let thousand = Math.floor((num % 100000) / 1000);
        let hundred = num % 1000;
        let result = "";
        if (crore) result += convert_hundreds(crore) + " Crore ";
        if (lakh) result += convert_hundreds(lakh) + " Lakh ";
        if (thousand) result += convert_hundreds(thousand) + " Thousand ";
        if (hundred) result += convert_hundreds(hundred);
        return result.trim();
      })(Math.floor(amount));
      document.getElementById("amountInWords").innerText = "Rupees " + amountInWords + " Only";

      // Auto-print
      if (autoprint) {
        setTimeout(() => {
            window.print();
        }, 300);
      }
    });
  </script>
</body>

</html>