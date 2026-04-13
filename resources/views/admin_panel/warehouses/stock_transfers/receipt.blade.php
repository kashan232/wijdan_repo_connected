<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Receipt</title>
  <style>
    /* ---- Clean layout: fixed column widths, no overlap ---- */
    :root {
      --font-base: 12.5px;
      /* 🔥 overall font increased */
      --line-gap: 1.35;
      --ink: #000;
    }

    * {
      box-sizing: border-box;
    }

    html,
    body {
      margin: 0;
      padding: 0;
    }

    .receipt {
      max-width: 58mm;
      /* 🔥 thermal safe */
      padding: 0 1mm;
    }

    /* ===== TABLE FIX ===== */
    table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      font-size: 11.5px;
    }

    /* Column widths – balanced for 58mm */
    colgroup col.from {
      width: 20%;
    }

    colgroup col.to {
      width: 20%;
    }

    colgroup col.item {
      width: 42%;
    }

    colgroup col.qty {
      width: 18%;
    }

    th {
      font-size: 11px;
      font-weight: 800;
      padding-bottom: 4px;
      border-bottom: 1px dashed #000;
    }

    th:nth-child(1),
    th:nth-child(2) {
      padding-right: 6px;
    }



    td {
      padding: 3px 0;
      font-weight: 600;
      vertical-align: top;
    }

    /* Alignment */
    td.qty,
    th.qty {
      text-align: right;
      font-weight: 800;
    }

    /* Wrapping rules */
    td.from,
    td.to {
      white-space: normal;
      word-break: break-word;
    }

    td.from {
      padding-right: 6px;
      border-right: 1px dotted #000;
    }

    td.item {
      white-space: normal;
      overflow-wrap: anywhere;
      line-height: 1.3;
    }

    /* ===== TOTAL SUMMARY BOX ===== */
    .summary-box {
      padding: 6px;
      margin-top: 10px;
      background: #f7f7f7;
      font-size: 12px;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      font-weight: 800;
      margin-bottom: 2px;
    }

    .summary-row.final {
      border-top: 1px dashed #000;
      padding-top: 4px;
      margin-top: 4px;
      font-size: 13px;
    }

    body {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
        "Liberation Mono", "Courier New", monospace;
      font-size: var(--font-base);
      line-height: var(--line-gap);
      font-weight: 500;
    }

    .page {
      width: 100%;
      display: grid;
      place-items: start center;
      padding: 4px 0;
    }

    .receipt {
      width: 100%;
      max-width: 58mm;
      background: #fff;
      color: var(--ink);
      padding: 0 1mm;
    }

    .center {
      text-align: center;
    }

    .bold {
      font-weight: 700;
    }

    h2 {
      margin: 0 0 4px;
      font-size: 18px;
      /* 🔥 WIJDAN BIG */
      font-weight: 900;
      /* 🔥 EXTRA BOLD */
      letter-spacing: 1px;
    }

    p {
      margin: 0;
    }

    hr {
      border: 0;
      border-top: 1px dashed #000;
      margin: 6px 0;
    }

    /* --- Meta rows (Invoice/Date/Remarks) as two-column grid --- */
    .meta {
      display: grid;
      grid-template-columns: 32% 68%;
      row-gap: 2px;
    }

    .label {
      font-weight: 700;
    }

    .value {
      text-align: left;
    }

    /* --- Items table with fixed column widths so text doesn't collide --- */
    table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      font-size: 12px;
      /* 🔥 table text bigger */
    }


    colgroup col.from {
      width: 24%;
    }

    colgroup col.to {
      width: 24%;
    }

    colgroup col.item {
      width: 38%;
    }

    colgroup col.qty {
      width: 14%;
    }

    th,
    td {
      padding: 3px 0;
      font-weight: 600;
    }

    th {
      font-size: 11.5px;
      font-weight: 800;
      padding: 3px 0;
    }

    td.qty,
    th:last-child {
      text-align: right;
      font-weight: 800;
    }

    td:last-child,
    th:last-child {
      text-align: right;
    }

    .remarks {
      text-align: center;
      font-size: 13.5px;
      font-weight: 800;
      margin-top: 6px;
    }

    /* Prevent ugly mid-word breaks for From/To; allow flexible wrap for Item only */
    td.from,
    td.to {
      word-break: keep-all;
      white-space: normal;
    }

    td.to {
      padding-left: 6px;
    }

    td.item {
      overflow-wrap: anywhere;
    }

    .footer {
      text-align: center;
      font-size: 11px;
      font-weight: 600;
      margin-top: 6px;
    }

    @media print {
      @page {
        size: 58mm auto;
        margin: 2mm;
      }
    }

    /* Print button (screen only) */
    .printbar {
      display: flex;
      justify-content: center;
      margin: 6px 0;
    }

    .printbtn {
      font: inherit;
      padding: 0px 0px;
      border: 1px solid #000;
      background: transparent;
      cursor: pointer;
    }

    .transfer-title {
      font-size: 14.5px;
      font-weight: 800;
      text-transform: uppercase;
    }

    /* ---------- META ---------- */
    .meta {
      display: grid;
      grid-template-columns: 40% 60%;
      row-gap: 3px;
      font-size: 12.5px;
    }

    .receipt {
      max-width: 80mm;
    }

    .label {
      font-weight: 800;
      /* 🔥 Invoice / Date bold */
    }

    .value {
      font-weight: 700;
    }

    @media print {
      .printbar {
        display: none !important;
      }

      @page {
        size: 58mm auto;
        margin: 2mm;
      }

      .page {
        padding: 0;
      }

      .receipt {
        padding: 0 1mm;
      }

      .receipt,
      table,
      tr,
      p,
      .footer {
        break-inside: avoid;
      }
    }


    @media screen {
      .no-pdf {
        display: none;
      }
    }

    @media print {
      .no-print {
        display: none;
      }
    }
  </style>
</head>

<body>
  <div class="page">
    <div class="receipt">
      <div class="printbar">
        <button class="printbtn" onclick="window.print()">🖨️ Print</button>
        <button class="printbtn" onclick="downloadPDF()">💾 Download PDF</button>
        <a href="{{ route('stock_transfers.index') }}" class="btn btn-secondary">
          ← Back
        </a>
      </div>
      <div class="center">
        <h2 class="bold">WIJDAN</h2>
      </div>
      <div class="meta">
        <div class="label">Invoice ID:</div>
        <div class="value">{{ $transfer->id ?? '-' }}</div>
        <div class="label">Date:</div>
        <div class="value">{{ $transfer->created_at?->format('d-m-Y') ?? '-' }}</div>
        <div class="label">Print Time:</div>
        <div class="value">{{ \Carbon\Carbon::now()->format('h:i A') }}</div>
      </div>
      <hr>
      <div class="center transfer-title">Stock Transfer</div>
      <hr>

      <table>
        <colgroup>
          <col class="from">
          <col class="to">
          <col class="item">
          <col class="qty">
        </colgroup>

        <thead>
          <tr>
            <th>From</th>
            <th>To</th>
            <th>Item</th>
            <th class="qty">Qty</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($products as $product)
          <tr>
            <td>{{ $transfer->fromWarehouse->warehouse_name ?? 'Shop' }}</td>
            <td class="center">
              {{ $transfer->toWarehouse->warehouse_name ?? 'Shop' }}
            </td>
            <td class="center">
              {{ $product->item_name }} ({{ $product->unit_id }})
            </td>
            <td class="qty">{{ number_format($product->transfer_qty, 2) }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="center">No products found</td>
          </tr>
          @endforelse
        </tbody>
      </table>

      <div class="summary-box">
        <div class="summary-row final">
        </div>
        @foreach ($unitTotals as $unit => $qty)
        <div class="summary-row">
          <span>{{ $unit }}</span>
          <span>{{ number_format($qty, 2) }}</span>
        </div>
        @endforeach
      </div>
      <div class="remarks center">
        Remarks: {{ $transfer->remarks ?? '-' }}
      </div>

      <hr>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script>
    function downloadPDF() {
      const element = document.querySelector('.receipt');
      const opt = {
        margin: [2, 2, 2, 2], // mm margin for thermal roll
        filename: 'Stock_Transfer_Receipt.pdf',
        image: {
          type: 'jpeg',
          quality: 1
        },
        html2canvas: {
          scale: 4,
          useCORS: true
        },
        jsPDF: {
          unit: 'mm',
          format: [58, 297],
          orientation: 'portrait'
        } // 58mm thermal size
      };

      html2pdf().set(opt).from(element).save();
    }

     window.onload = function() {
      window.print();
    };

    window.onafterprint = function() {
      window.location.href = "{{ route('stock_transfers.index') }}";
    };
  </script>

</body>

</html>
