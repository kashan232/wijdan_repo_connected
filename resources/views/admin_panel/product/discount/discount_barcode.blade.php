<!DOCTYPE html>
<html>
<head>
    <title>Discount Barcode</title>
    <style>
        :root {
            --label-w: 42mm;
            --barcode-w: 58mm;
            /* barcode + path ka exact block width */
            --barcode-h: 5mm;
            /* barcode height */
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin: 0px;
            padding: 0;
            background: #fff;
        }

        .label {
            border: 1px dashed #000;
            /* padding: 8px 10px; */
            width: var(--label-w);
            text-align: center;
        }

        .brand-name {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 6px;
        }

        /* --- BARCODE + TEXT as one centered block --- */
        .barcode-block {
            width: var(--barcode-w);
            margin: 4px auto 0 auto;
            /* center whole block */
        }

        .barcode-block svg {
            display: block;
            width: var(--barcode-w) !important;
            height: var(--barcode-h) !important;
            margin: 0 30px;
            shape-rendering: crispEdges;
            overflow: visible;
        }

        .barcode-text {
            text-align: center;
            font-size: 12px;
            /* font-weight: bold; */
            line-height: 1.2;
            margin-top: 5px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .price {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }

        @media print {
            @page {
                margin: 2.5mm;
            }

            .label {
                page-break-inside: avoid;
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="brand-name">WIJDAN</div>

        <div class="barcode-block">
            {!! DNS1D::getBarcodeSVG($discount->discount_code, 'C128', 1.4, 20, 'black', false) !!}
        </div>

        <div class="barcode-text">
            {{ $discount->discount_code }}
            {{ $discount->product->item_name }}
        </div>

        <div class="price">
            SALE: {{ number_format($discount->final_price) }}
        </div>
    </div>
</body>
</html>
