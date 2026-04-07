<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DELIVERY CHALLAN</title>
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
    .col-code {
      white-space: nowrap;
    }

    .col-qty {
      text-align: center;
    }

    .col-unit {
      text-align: center;
    }

    .col-code {
        text-align: left;
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

    /* Column widths for DC */
    .items-table th:nth-child(1), .items-table td:nth-child(1) { width: 10%; } /* # */
    .items-table th:nth-child(2), .items-table td:nth-child(2) { width: 50%; } /* Item */
    .items-table th:nth-child(3), .items-table td:nth-child(3) { width: 25%; } /* Code */
    .items-table th:nth-child(4), .items-table td:nth-child(4) { width: 15%; } /* Qty */

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
      <p style="margin:0;">Chandni market, Salahuddin Road, Cantt, Hyderabad</p>
      <p style="margin:0;">Phone: 022786661</p>
    </div>

    <div class="line"></div>
    <div class="center">
      <div style="font-weight: bold; font-size: 18px; margin-bottom: 5px;">DELIVERY CHALLAN</div>
      <svg id="barcode"></svg>
    </div>

    <div class="line"></div>
    <!-- Details -->
    <table>
      <tr>
        <th>DC No</th>
        <td>DC-{{ $sale->id }}</td>
      </tr>
      <tr>
        <th>Reference</th>
        <td>{{ $sale->reference ?? '-' }}</td>
      </tr>
      <tr>
        <th>Date</th>
        <td>{{ optional($sale->created_at)->format('d-m-Y h:i A') ?? 'N/A' }}</td>
      </tr>
      <tr>
        <th>Customer</th>
        <td>{{ $sale->customer_relation->customer_name ?? 'N/A' }}</td>
      </tr>
      @if($sale->customer != 'Walk-in Customer')
      <tr>
        <th>Mobile</th>
        <td>{{ $sale->customer_relation->mobile ?? 'N/A' }}</td>
      </tr>
      @endif
    </table>

    <div class="line"></div>

    <!-- Items -->
    <table class="items-table">
      <thead>
        <tr class="bold">
          <th>#</th>
          <th>Item</th>
          <th>Code</th>
          <th style="text-align: right;">Qty</th>
        </tr>
      </thead>
      <tbody>
        @foreach($saleItems as $index => $item)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td class="col-item">
            {{ $item['item_name'] }}
            @if(!empty($item['unit']))
               <small>({{ $item['unit'] }})</small>
            @endif
          </td>
          <td>{{ $item['item_code'] }}</td>
          <td style="text-align: right;" class="bold">{{ $item['qty'] }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="line"></div>

    @php
    $totalPieces = 0;
    $totalYard = 0;
    $totalMeter = 0;

    foreach($saleItems as $item) {
        $qty = (float)$item['qty'];
        
        // Normalize unit
        $unitRaw = $item['unit'];
        if (is_array($unitRaw)) {
            $unitRaw = $unitRaw[0] ?? '';
        }
        $u = strtolower(trim($unitRaw));

        if (in_array($u, ['pc', 'pcs', 'piece', 'pieces', 'pisces'])) {
            $totalPieces += $qty;
        } elseif (in_array($u, ['mtr', 'meter', 'metre'])) {
            $totalMeter += $qty;
        } elseif (in_array($u, ['yd', 'yard', 'yards'])) {
            $totalYard += $qty;
        } else {
            $totalPieces += $qty; // Default
        }
    }
    @endphp

    @if($totalPieces > 0 || $totalYard > 0 || $totalMeter > 0)
    <div class="items-summary">
        <table>
            @if($totalPieces > 0)
            <tr>
                <th>TOTAL PIECES</th>
                <td style="text-align: right;">{{ $totalPieces }}</td>
            </tr>
            @endif
            @if($totalYard > 0)
            <tr>
                <th>TOTAL YARD</th>
                <td style="text-align: right;">{{ $totalYard }}</td>
            </tr>
            @endif
            @if($totalMeter > 0)
            <tr>
                <th>TOTAL METER</th>
                <td style="text-align: right;">{{ $totalMeter }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

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
      JsBarcode("#barcode", "{{ $sale->invoice_no }}", {
        format: "CODE128",
        lineColor: "#000",
        width: 1,
        height: 20,
        displayValue: false,
        fontSize: 12,
        fontOptions: "bold",
        margin: 0
      });

      const query = new URLSearchParams(window.location.search);
      const returnTo = query.get('return_to') || "{{ route('sale.index') }}";
      const autoprint = query.get('autoprint') === '1';

      // Print button
      document.getElementById('btnPrint').addEventListener('click', () => window.print());

      // Back button
      document.getElementById('btnBack').addEventListener('click', () => {
        try {
            window.location.href = decodeURIComponent(returnTo);
        } catch (e) {
            if (history.length > 1) history.back();
            else window.location.href = "{{ route('sale.add') }}";
        }
      });

      // Auto-print
      if (autoprint) {
        const redirectBack = () => {
            if (window.self !== window.top) return; 
            try {
                window.location.href = decodeURIComponent(returnTo);
            } catch (e) {
                window.location.href = "{{ route('sale.add') }}";
            }
        };

        setTimeout(() => {
            window.print();
            redirectBack();
        }, 300);
      }

      // Alt+B Shortcut
      document.addEventListener('keydown', (e) => {
        if ((e.altKey || e.metaKey) && e.key.toLowerCase() === 'b') {
          e.preventDefault();
          document.getElementById('btnBack').click();
        }
      });
    });
  </script>
</body>

</html>