<!DOCTYPE html>
<html>

<head>
    <title>Product Barcode</title>
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
            margin-top: 2mm;
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
            margin: 8px 25px;
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
                margin: 0mm;
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
            {!! DNS1D::getBarcodeSVG($product->barcode_path, 'C128', 1.6, 23, 'black', false) !!}
        </div>
        <div class="barcode-text">
            {{ $product->barcode_path }}
            {{ $product->item_name }}
        </div>
        <div class="price">
            PKR:
            {{ number_format(
        $product->activeDiscount
            ? $product->activeDiscount->final_price
            : $product->price
    ) }}
        </div>
    </div>
</body>

</html>