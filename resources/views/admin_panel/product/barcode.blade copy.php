<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Product Barcode (1"×1.5" Roll)</title>
  <style>
    :root{
      /* === Label size (portrait): 1.0in wide × 1.5in high === */
      --label-w: 1in;
      --label-h: 1.5in;

      /* inner paddings ko minimal rakhein to overflow na ho */
      --pad: 0.04in;
    }

    html, body{
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      font-family: Arial, sans-serif;
    }

    /* Each label is its own page of exact size: perfect for roll printers */
    .label{
      box-sizing: border-box;
      width: var(--label-w);
      height: var(--label-h);
      padding: var(--pad);
      margin: 0;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      line-height: 1.1;
      /* screen par alignment check karne ke liye border; print me off */
      border: 1px dashed #000;
    }

    .brand-name{
      margin: 0;
      font-size: 10px;
      font-weight: bold;
      letter-spacing: 0.3px;
    }

    .barcode-block{
      width: 100%;
      margin: 0;
      padding: 0;
      line-height: 0;
    }
    .barcode-block svg{
      display: block;
      width: calc(100% - (var(--pad) * 1)) !important;  /* snug fit */
      height: 0.45in !important;                        /* adjust if needed */
      margin: 0 auto;
      overflow: hidden;
      shape-rendering: crispEdges;
    }

    .barcode-text{
      margin: 0;
      font-size: 9px;
      text-align: center;
      word-break: break-word;
      white-space: normal;
    }

    .price{
      margin: 0;
      font-size: 10px;
      font-weight: bold;
    }

    @media print{
      /* Exact page size = exactly one physical label; no gaps, no extra pages */
      @page{
        size: var(--label-w) var(--label-h);
        margin: 0;
      }
      .label{
        page-break-after: always;   /* next label starts on next physical label */
        border: none;               /* print me border hatado */
      }
    }
  </style>
</head>
<body>

  <!-- Laravel/Blade me jitne labels chahiye utni dafa yeh block repeat kar dein -->
  <div class="label">
    <div class="brand-name">WIJDAN</div>

    <div class="barcode-block">
      {!! DNS1D::getBarcodeSVG($product->barcode_path, 'C128', 1.2, 20, 'black', false) !!}
    </div>

    <div class="barcode-text">
      {{ $product->barcode_path }}<br>
      {{ $product->item_name }}
    </div>

    <div class="price">PKR: {{ number_format($product->price) }}</div>
  </div>

</body>
</html>
