@extends('admin_panel.layout.app')
@section('content')
<style>
    body {
        padding-bottom: 90px;
        /* summary bar ki height ke barabar */
    }

    .searchResults {
        position: absolute;
        z-index: 9999;
        width: 20%;
        max-height: 200px;
        overflow-y: auto;
        background: #fff;
        /* border: 1px solid #ddd; */
        text-align: start
    }

    #modalSearchResults .active {
        background-color: #0d6efd;
        /* bootstrap blue */
        color: #fff;
    }

    .search-result-item.active {
        background: #007bff;
        color: white;
    }

    .table-fixed {
        table-layout: fixed;
    }

    .product-col {
        width: 20% !important;
        /* 👈 yahan control hai */
        min-width: 320px;
    }
</style>

<style>
    .table-scroll tbody {
        display: block;
        max-height: calc(60px * 5);
        /* Assuming each row is ~40px tall */
        overflow-y: auto;
    }

    .table-scroll thead,
    .table-scroll tbody tr {
        display: table;
        width: 100%;
        table-layout: fixed;
    }

    /* Optional: Hide scrollbar width impact */
    .table-scroll thead {
        width: calc(100% - 1em);
    }

    .table-scroll .icon-col {
        width: 51px;
        /* Ya jitni chhoti chahiye */
        min-width: 51px;
        max-width: 40px;
    }

    .table-scroll {
        max-height: none !important;
        overflow-y: visible !important;
    }

    .product-col input {
        white-space: normal;
    }

    .product-col input {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sale-btn {
        font-size: 1.5rem;
        font-weight: 600;
        padding: 5px 40px;
        box-shadow: 0 0 10px rgba(0, 128, 0, 0.4);
        transition: all 0.2s ease-in-out;
    }

    .sale-btn:hover {
        transform: scale(1.05);
        background-color: #28a745 !important;
        box-shadow: 0 0 14px rgba(40, 167, 69, 0.6);
    }

    .table thead th {
        font-weight: 700;
        /* Bold */
        font-size: 18px;
        /* Thora bara */
        background-color: #f8f9fa;
        color: #000;
        vertical-align: middle;
    }

    .table tbody input.form-control,
    .table tbody textarea.form-control {
        font-size: 16px;
        /* readable */
        font-weight: 600;
        /* semi-bold */
        color: #000;
    }

    .total input {
        background-color: #f1fdf4;
    }

    .price,
    .quantity,
    .row-total {
        font-weight: 700;
        font-size: 15px;
    }

    .disabled-row input {
        background-color: #f8f9fa;
        pointer-events: none;
    }

    .total-pieces-box {
        font-size: 26px;
        /* Bara text */
        font-weight: 800;
        /* Extra bold */
        color: #000;
        letter-spacing: 1px;
    }

    .total-pieces-box span {
        font-size: 32px;
        /* Quantity aur bhi zyada bari */
        font-weight: 900;
        color: #198754;
        /* Green (sale friendly) */
        margin-left: 6px;
    }

    .fixed-summary-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 0 !important;
        z-index: 9999;
        background: #ffffff;

        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.2);
        border-top: 3px solid #198754;
    }

    /* Th headers thore strong */
    .fixed-summary-bar th {
        background: #f1fdf4;
        font-size: 15px;
        font-weight: 700;
    }

    /* Inputs thore tall */
    .fixed-summary-bar input {
        height: 30px !important;
        font-size: 25px !important;
        padding: 2px 4px !important;
    }

    .invoice-summary-table {
        table-layout: fixed;
        width: 100%;
        white-space: nowrap;
    }

    .invoice-summary-table th,
    .invoice-summary-table td {
        padding-top: 3px !important;
        padding-bottom: 3px !important;

        font-size: 11px !important;
        line-height: 1.2 !important;
        text-align: center;
        vertical-align: middle;
    }

    .fixed-summary-bar .border-top {
        padding: 4px 8px !important;
    }

    .total-pieces-box span {
        font-size: 20px !important;
    }

    /* Text thora chhota */
    .amount-words-label,
    .fixed-summary-bar span,
    .total-pieces-box {
        font-size: 22px !important;
        line-height: 1.2 !important;
    }

    .invoice-summary-table th:not(:first-child),
    .invoice-summary-table td:not(:first-child) {
        width: 10%;
    }

    .amount-words-box {
        padding: 6px;
    }

    .amount-words-label {
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .total-pieces-inline {
        font-size: 22px;
        font-weight: 800;
        margin-top: 6px;
    }

    .total-pieces-inline span {
        font-size: 28px;
        font-weight: 900;
        color: #198754;
        margin-left: 6px;
    }

    .invoice-summary-table th:nth-child(1),
    .invoice-summary-table td:nth-child(1) {
        width: 10%;
    }

    .invoice-summary-table th:nth-child(2) {
        width: 10%;
    }

    .invoice-summary-table th:nth-child(3) {
        width: 10%;
    }

    .invoice-summary-table th:nth-child(4) {
        width: 10%;
    }

    .invoice-summary-table th:nth-child(5) {
        width: 10%;
    }

    .invoice-summary-table th:nth-child(6) {
        width: 10%;
    }

    .invoice-summary-table th:nth-child(7) {
        width: 10%;
    }

    .invoice-summary-table th:nth-child(8) {
        width: 10%;
    }

    .big-change-input {
        font-size: 30px !important;
        font-weight: 900 !important;
        height: 38px !important;
        background-color: #e9f7ef;
        color: #00b460 !important;
        letter-spacing: 1px;
    }

    .qty-highlight {
        background-color: #e9fbe9 !important;
        animation: flashRow 0.8s ease-out;
    }

    @keyframes flashRow {
        from {
            background-color: #b6f2b6;
        }

        to {
            background-color: transparent;
        }
    }

    /* 🔥 Qty input pulse */
    .qty-pulse {
        animation: pulseQty 0.6s ease-out;
    }

    @keyframes pulseQty {
        0% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.9);
        }

        100% {
            box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
        }
    }

    #productModal {
        z-index: 999999 !important;
    }

    /* 🔥 +1 floating badge */
    .qty-indicator {
        position: absolute;
        right: -12px;
        top: -8px;
        background: #198754;
        color: #fff;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 12px;
        font-weight: 700;
        animation: popFade 1s ease-out forwards;
        z-index: 10;
    }

    @keyframes popFade {
        0% {
            opacity: 1;
            transform: translateY(0);
        }

        100% {
            opacity: 0;
            transform: translateY(-12px);
        }
    }

    .total-pieces-box #totalPieces {
        background-color: #cce5ff;
        /* Light Blue */
        color: #004085;
        font-weight: 600;
    }

    .total-pieces-box #totalMeter {
        background-color: #d4edda;
        /* Light Green */
        color: #155724;
        font-weight: 600;
    }

    .total-pieces-box #totalYard {
        background-color: #fff3cd;
        /* Light Orange */
        color: #856404;
        font-weight: 600;
    }
</style>
<div class="container-fluid">
    <div class="card shadow-sm border-0 p-0 m-0">
        <form id="salesForm" action="{{ route('sales.update', $sale->id) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="sale_id" value="{{ $sale->id }}">
            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            <div class="card-body">
                {{-- Top Form --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Customer:</label>
                        <select name="customer" class="form-control form-control-sm" required>
                            <option value="Walk-in Customer" {{ $sale->customer === 'Walk-in Customer' ? 'selected' : '' }}>Walk-in Customer</option>
                            @foreach ($Customer as $c)
                            <option value="{{ $c->id }}" {{ $sale->customer == $c->id ? 'selected' : '' }}>
                                {{ $c->customer_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Reference #</label>
                        <input type="text" name="reference" class="form-control form-control-sm">
                        <!-- hidden field for advance (will be set by modal) -->
                        <input type="hidden" name="advance_payment" id="advance_payment" value="0">

                    </div>
                </div>

                {{-- Table --}}
                <button class="btn btn-primary btn-sm mt-2 mb-2" id="openProductModal">
                    Search Product (F2)
                </button>

                <div class="modal fade" id="productModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title">Search Product</h5>
                                <button type="button" class="btn-close" data-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">

                                <input type="text"
                                    id="modalProductSearch"
                                    class="form-control mb-2"
                                    placeholder="Type product name or scan barcode"
                                    autofocus>

                                <ul class="list-group" id="modalSearchResults"
                                    style="max-height:300px; overflow-y:auto;"></ul>

                            </div>

                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle text-center table-fixed">
                        <thead>
                            <tr class="text-center">
                                <th class="product-col text-start">Product</th>
                                <th>Item Code</th>
                                <th>Note</th>
                                <th>Brand</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Discount</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <style>
                            /* Select2: make selection stay in one line and scroll horizontally */
                            .select2-container--default .select2-selection--multiple {
                                display: flex !important;
                                flex-wrap: nowrap !important;
                                overflow-x: auto !important;
                                overflow-y: hidden !important;
                                min-height: 38px !important;
                                max-height: 38px !important;
                                white-space: nowrap !important;
                                scrollbar-width: thin;
                            }

                            /* Each tag styling */
                            .select2-selection__choice {
                                white-space: nowrap !important;
                                margin-right: 3px !important;
                                font-size: 11px;
                                padding: 2px 5px !important;
                            }

                            /* Remove unwanted spacing */
                            .select2-search--inline {
                                flex: none !important;
                            }
                        </style>
                        <tbody id="purchaseItems" style="max-height: 300px; overflow-y: auto;">
                            @forelse ($saleItems as $item)
                            <tr>
                                <td class="product-col">
                                    <input type="hidden" name="product_id[]" class="product_id" value="{{ $item['product_id'] }}">
                                    <input type="text" class="form-control productSearch" value="{{ $item['item_name'] }}" readonly>
                                </td>

                                <td class="item_code border">
                                    <input type="text" name="item_code[]" class="form-control" value="{{ $item['item_code'] }}" readonly>
                                </td>

                                <td class="color border" style="min-width:200px; max-width:200px;">
                                    <textarea name="color[]" class="form-control product-note" rows="2" readonly>{{ $item['note'] }}</textarea>
                                </td>

                                <td class="uom border">
                                    <input type="text" name="uom[]" class="form-control" value="{{ $item['brand'] }}" readonly>
                                </td>

                                <td class="unit border">
                                    <input type="text" name="unit[]" class="form-control" value="{{ $item['unit'] }}" readonly>
                                </td>

                                <td>
                                    <input type="number" step="0.01" name="price[]" class="form-control price" value="{{ $item['price'] }}">
                                </td>

                                <td>
                                    <input type="text" name="item_disc[]" class="form-control item_disc" value="{{ $item['discount'] }}">
                                </td>

                                <td class="qty">
                                    <input type="number" name="qty[]" class="form-control quantity" min="0.01" step="0.01" value="{{ $item['qty'] }}">
                                </td>

                                <td class="total border">
                                    <input type="text" name="total[]" class="form-control row-total" value="{{ $item['total'] }}" readonly>
                                </td>

                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
                                </td>
                            </tr>
                            @empty
                            {{-- agar koi item nahi toh ek blank row --}}
                            <tr>
                                <td class="product-col">
                                    <input type="hidden" name="product_id[]" class="product_id">
                                    <input type="text" class="form-control productSearch" placeholder="Select product..." readonly>
                                </td>
                                <td class="item_code border">
                                    <input type="text" name="item_code[]" class="form-control" readonly>
                                </td>
                                <td class="color border" style="min-width:200px; max-width:200px;">
                                    <textarea name="color[]" class="form-control product-note" rows="2" readonly></textarea>
                                </td>
                                <td class="uom border">
                                    <input type="text" name="uom[]" class="form-control" readonly>
                                </td>
                                <td class="unit border">
                                    <input type="text" name="unit[]" class="form-control" readonly>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="price[]" class="form-control price">
                                </td>
                                <td>
                                    <input type="text" name="item_disc[]" class="form-control item_disc">
                                </td>
                                <td class="qty">
                                    <input type="number" name="qty[]" class="form-control quantity" min="0.01" step="0.01">
                                </td>
                                <td class="total border">
                                    <input type="text" name="total[]" class="form-control row-total" readonly>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>


                    </table>
                </div>

                {{-- Amount Summary --}}
                <div class="fixed-summary-bar">
                    <table class="table table-bordered table-sm mb-0 invoice-summary-table">
                        <tr>
                            <th>BILL AMOUNT</th>
                            <th>ITEM DISCOUNT</th>
                            <th>EXTRA DISCOUNT</th>
                            <th>NET AMOUNT</th>
                            <th>Cash</th>
                            <th>C/D Card</th>
                            <th>Change</th>
                        </tr>

                        <tr class="align-middle">
                            <td>
                                <input type="text" id="billAmount"
                                    name="total_subtotal"
                                    class="form-control form-control-sm text-center" value="{{ $sale->total_bill_amount }}" readonly>
                            </td>

                            <td>
                                <input type="text" id="itemDiscount"
                                    name="total_discount"
                                    class="form-control form-control-sm text-center" value="{{ $sale->total_items_discount ?? 0 }}" readonly>
                            </td>

                            <td>
                                <input type="number" id="extraDiscount"
                                    name="total_extra_cost"
                                    class="form-control form-control-sm text-center" value="{{ $sale->total_extradiscount }}" value="0">
                            </td>

                            <td>
                                <input type="text" id="netAmount"
                                    name="total_net"
                                    class="form-control form-control-sm text-center" value="{{ $sale->total_net }}" readonly>
                            </td>

                            <td>
                                <input type="number" id="cash"
                                    name="cash"
                                    class="form-control form-control-sm text-center" value="{{ $sale->cash }}">
                            </td>

                            <td>
                                <input type="number" id="card"
                                    name="card"
                                    class="form-control form-control-sm text-center" value="{{ $sale->card }}" value="0">
                            </td>

                            <td>
                                <input type="text" id="change"
                                    name="change"
                                    class="form-control big-change-input text-center" value="{{ $sale->change }}" readonly>
                            </td>

                        </tr>
                    </table>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                        <div class="fw-bold d-flex align-items-center gap-2">
                            <span>Amount In Words:</span>

                            <!-- Show user -->
                            <span id="amountWordsText" class="text-muted"></span>

                            <!-- Store in DB -->
                            <input type="hidden" name="total_amount_Words" id="amountWordsInput">
                            <input type="hidden" name="total_items" id="totalItemsInput">
                            <input type="hidden" name="total_pieces" id="totalPiecesInput">
                            <input type="hidden" name="total_yard" id="totalYardInput">
                            <input type="hidden" name="total_meter" id="totalMeterInput">
                        </div>

                        <div class="total-pieces-box d-flex gap-4">
                            <div>TOTAL PIECES : <span id="totalPieces">0</span></div>
                            <div>TOTAL YARD : <span id="totalYard">0</span></div>
                            <div>TOTAL METER : <span id="totalMeter">0</span></div>
                        </div>
                    </div>
                </div>


                <div class="d-flex justify-content-end align-items-center mt-4">
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="booking" class="btn btn-warning submit-btn">Book</button>
                        <button type="submit" name="action" value="sale" class="btn btn-success sale-btn submit-btn">Sale</button>
                        <a href="{{ route('sale.index') }}" class="btn btn-secondary">Close</a>
                    </div>
                </div>
            </div>
    </div>
    </form>
</div>
</div>


<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookingModalLabel">Booking - Advance Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Net Amount</label>
                    <input type="text" id="modalNetAmount" class="form-control" readonly>
                </div>
                <div class="mb-2">
                    <label class="form-label">Advance Payment</label>
                    <input type="number" step="0.01" id="modalAdvance" class="form-control" min="0" value="0">
                </div>
                <small class="text-muted">Advance must be less than or equal to Net Amount.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" id="confirmBookingBtn" class="btn btn-warning">Confirm Booking</button>
            </div>
        </div>
    </div>
</div>


@endsection

@section('scripts')
<script>
    let IS_SCANNING = false;

    function num(n) {
        return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
    }

    function numberToWords(num) {
        const a = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine",
            "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen",
            "Eighteen", "Nineteen"
        ];
        const b = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];

        if (!num || isNaN(num)) return '';

        num = Math.floor(num);
        if (num > 999999999) return "Overflow";

        const n = ("000000000" + num).slice(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{3})$/);
        if (!n) return '';

        let str = "";
        str += n[1] != 0 ? (a[n[1]] || b[n[1][0]] + " " + a[n[1][1]]) + " Crore " : "";
        str += n[2] != 0 ? (a[n[2]] || b[n[2][0]] + " " + a[n[2][1]]) + " Lakh " : "";
        str += n[3] != 0 ? (a[n[3]] || b[n[3][0]] + " " + a[n[3][1]]) + " Thousand " : "";
        str += n[4] != 0 ? (a[n[4]] || b[n[4][0]] + " " + a[n[4][1]]) : "";

        return str.trim() + " Rupees Only";
    }


    function recalcRow($row) {
        const qty = num($row.find('.quantity').val());
        const price = num($row.find('.price').val());

        let discRaw = ($row.find('.item_disc').val() || '').toString().trim();
        let totalDiscount = 0;

        if (discRaw.endsWith('%')) {
            const percent = parseFloat(discRaw);
            if (!isNaN(percent)) {
                totalDiscount = (price * percent / 100) * qty;
            }
        } else {
            const perQtyDisc = parseFloat(discRaw);
            if (!isNaN(perQtyDisc)) {
                totalDiscount = perQtyDisc * qty;
            }
        }

        let total = (qty * price) - totalDiscount;
        if (total < 0) total = 0;

        $row.find('.row-total').val(total.toFixed(2));
        $row.data('row-discount', totalDiscount);
    }

    function recalcSummary() {
        let billAmount = 0;
        let itemDiscount = 0;

        let totalPieces = 0;
        let totalYard = 0;
        let totalMeter = 0;

        $('#purchaseItems tr').each(function() {
            const qty = num($(this).find('.quantity').val());
            const unit = ($(this).find('.unit input').val() || '').toLowerCase();

            $(this).removeClass('unit-piece unit-meter unit-yard');

            if (unit.includes('piece') || unit.includes('pcs')) {
                totalPieces += qty;
                $(this).addClass('unit-piece');
            } else if (unit.includes('yard')) {
                totalYard += qty;
                $(this).addClass('unit-yard');
            } else if (unit.includes('meter')) {
                totalMeter += qty;
                $(this).addClass('unit-meter');
            }

            billAmount += num($(this).find('.row-total').val());
            itemDiscount += num($(this).data('row-discount'));
        });

        const extraDiscount = num($('#extraDiscount').val());
        const cash = num($('#cash').val());
        const card = num($('#card').val());

        const net = billAmount - extraDiscount;
        const change = (cash + card) - net;

        $('#billAmount').val(billAmount.toFixed(2));
        $('#itemDiscount').val(itemDiscount.toFixed(2));
        $('#netAmount').val(net.toFixed(2));
        $('#change').val(change.toFixed(2));

        // ✅ UNIT TOTALS
        $('#totalPieces').text(totalPieces);
        $('#totalYard').text(totalYard.toFixed(2));
        $('#totalMeter').text(totalMeter.toFixed(2));

        $('#totalPiecesInput').val(totalPieces);
        $('#totalYardInput').val(totalYard.toFixed(2));
        $('#totalMeterInput').val(totalMeter.toFixed(2));

        let totalItems = totalPieces + totalYard + totalMeter;
        $('#totalItemsInput').val(totalItems);

        const words = numberToWords(Math.round(net));
        $('#amountWordsText').text(words);
        $('#amountWordsInput').val(words);
    }

    $(document).ready(function() {
        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }



        // Events
        // Prevent Double Submission
        let isSubmitting = false;
        $('#salesForm').on('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            isSubmitting = true;
            $(this).find('.submit-btn').prop('disabled', true).text('Processing...');
        });

        // Allow button click to submit form with specific value
        $('.submit-btn').on('click', function(e) {
            if (isSubmitting) { 
                e.preventDefault();
                return false;
            }
            if ($(this).val() === 'booking') {
                return;
            }
            e.preventDefault();
            
            let action = $(this).val();
            let $form = $('#salesForm');
            
            $('<input>').attr({
                type: 'hidden',
                name: 'action',
                value: action
            }).appendTo($form);
            
            isSubmitting = true;
            $(this).prop('disabled', true).text('Processing...');
            $('.submit-btn').not(this).prop('disabled', true);
            
            $form.off('submit'); 
            $form.submit();
        });

        // ⌨️ Shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl + S
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                if (isSubmitting) return;

                const $form = $('#salesForm');
                if (!$form.length) return;

                // Ensure action is 'sale'
                if ($form.find('input[name="action"]').length === 0) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'action',
                        value: 'sale'
                    }).appendTo($form);
                } else {
                    $form.find('input[name="action"]').val('sale');
                }

                // Validation
                if ($('#purchaseItems tr').length === 0) {
                    alert('Please add at least one product');
                    return;
                }

                $form.submit();
            }
        });

        $(document).on('input', '.quantity, .price, .item_disc, #extraDiscount, #cash, #card', function() {
            const $row = $(this).closest('tr');
            if ($row.length) {
                recalcRow($row);
            }
            recalcSummary();
        });

        // Initialize
        $('#purchaseItems tr').each(function() {
            recalcRow($(this));
        });
        recalcSummary();
    });
</script>

<script>
    function appendBlankRow(isScanner = false) {
        const newRow = `
<tr data-scanned="0">
    <td class="product-col">
        <input type="hidden" name="product_id[]" class="product_id">
        <input type="text" class="form-control productSearch" placeholder="Select product..." readonly>
    </td>
    <td class="item_code border">
        <input type="text" name="item_code[]" class="form-control" readonly>
    </td>
    <td class="color border" style="min-width:200px; max-width:200px;">
        <textarea name="color[]" class="form-control product-note" rows="2" readonly></textarea>
    </td>
    <td class="uom border">
        <input type="text" name="uom[]" class="form-control" readonly>
    </td>
    <td class="unit border">
        <input type="text" name="unit[]" class="form-control" readonly>
    </td>
    <td>
        <input type="number" step="0.01" name="price[]" class="form-control price">
    </td>
    <td>
        <input type="text" name="item_disc[]" class="form-control item_disc">
    </td>
    <td class="qty">
        <input type="number" name="qty[]" class="form-control quantity" min="0.01" step="0.01">
    </td>
    <td class="total border">
        <input type="text" name="total[]" class="form-control row-total" readonly>
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
    </td>
</tr>`;

        $('#purchaseItems').append(newRow);

        // 🔹 Scanner-specific behavior: focus Product input, not Qty
        setTimeout(() => {
            if (isScanner) {
                $('#purchaseItems tr:last .productSearch').focus();
            } else {
                $('#purchaseItems tr:last .productSearch').focus();
            }
        }, 10);
    }

    function isRowComplete($row) {
        const productId = $row.find('.product_id').val();
        const price = parseFloat($row.find('.price').val());
        const qty = parseFloat($row.find('.quantity').val());

        return (
            productId &&
            !isNaN(price) && price > 0 &&
            !isNaN(qty) && qty > 0
        );
    }

    function hasEmptyRow() {
        let exists = false;
        $('#purchaseItems tr').each(function() {
            if (!$(this).find('.product_id').val()) {
                exists = true;
                return false;
            }
        });
        return exists;
    }

    $(document).ready(function() {
        setTimeout(() => {
            $('#purchaseItems tr:last .productSearch').focus();
        }, 300);
        // ---------- Helper Functions ----------
        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        function recalcRow($row) {
            const qty = num($row.find('.quantity').val());
            const price = num($row.find('.price').val());

            let discRaw = ($row.find('.item_disc').val() || '').toString().trim();
            let totalDiscount = 0;

            // % discount
            if (discRaw.endsWith('%')) {
                const percent = parseFloat(discRaw);
                if (!isNaN(percent)) {
                    totalDiscount = (price * percent / 100) * qty;
                }
            }
            // PKR per qty
            else {
                const perQtyDisc = parseFloat(discRaw);
                if (!isNaN(perQtyDisc)) {
                    totalDiscount = perQtyDisc * qty;
                }
            }

            let total = (qty * price) - totalDiscount;
            if (total < 0) total = 0;

            $row.find('.row-total').val(total.toFixed(2));

            // store actual discount for summary
            $row.data('row-discount', totalDiscount);
        }





        setTimeout(function() {
            const $first = $('.productSearch:visible').first();
            if ($first.length) {
                $first.focus();
                // put caret at end (nice UX if there's prefilled value)
                const val = $first.val();
                $first.val('').val(val);
            }
        }, 120);

        let searchTimer = null;


        function closeAllSearchBoxes() {
            $('.searchResults').empty().hide();
        }
        $(document).on('blur', '.productSearch', function() {
            setTimeout(() => {
                $(this).siblings('.searchResults').empty().hide();
            }, 150);
        });


        // On Click Product Suggestion
        $(document).on('click', '.search-result-item', function() {

            const $li = $(this);
            const $row = $li.closest('tr');

            $row.find('.productSearch').val($li.data('product-name'));
            $row.find('.productSearch').prop('readonly', true).addClass('bg-light');
            $row.find('.item_code input').val($li.data('product-code'));
            $row.find('.uom input').val($li.data('product-uom'));
            $row.find('.unit input').val($li.data('product-unit'));
            $row.find('.price').val($li.data('price'));
            $row.find('.product_id').val($li.data('product-id'));
            $row.find('.product-note').val($li.attr('data-note') || '');

            $row.find('.quantity').val(1);
            $row.find('.item_disc').val(0);

            recalcRow($row);
            recalcSummary();

            // 🔥 HIDE SUGGESTIONS PROPERLY
            $row.find('.searchResults').empty().hide();
            $row.attr('data-scanned', '1');
            // 🔥 MOVE TO QTY
            $row.find('.quantity').focus();
        });

        // ✅ Add new row only when Enter is pressed inside Qty input
        $(document).on('keydown', '.quantity', function(e) {
            if (e.key !== 'Enter') return;

            e.preventDefault();

            const $row = $(this).closest('tr');

            if (!isRowComplete($row)) {
                alert('Please complete product, price and quantity first');
                return;
            }

            // Agar blank row already hai → wahi focus
            if (hasEmptyRow()) {
                $('#purchaseItems tr')
                    .filter(function() {
                        return !$(this).find('.product_id').val();
                    })
                    .first()
                    .find('.productSearch')
                    .focus();
                return;
            }

            // ✅ Create next row
            appendBlankRow();

            // ✅ Focus product input of new row
            setTimeout(() => {
                $('#purchaseItems tr:last .productSearch').focus();
            }, 50);
        });

        // Keyboard Enter on suggestion
        $(document).on('keydown', '.searchResults .search-result-item', function(e) {
            if (e.key === 'Enter') {
                $(this).trigger('click');
            }
        });

        // Quantity/Price/Disc Update
        $('#purchaseItems').on('input', '.quantity, .price, .item_disc', function() {
            const $row = $(this).closest('tr');
            recalcRow($row);
            recalcSummary();
        });

        // Remove row
        $('#purchaseItems').on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            recalcSummary();
        });

        // Discount/Extra Cost Update
        $('#overallDiscount, #extraCost').on('input', function() {
            recalcSummary();
        });

        // Initialize first row
        recalcRow($('#purchaseItems tr:first'));
        recalcSummary();
    });
</script>

<script>
    $(document).ready(function() {
        // use specific form selector
        const $salesForm = $('#salesForm');

        // When Book button clicked -> show modal instead of immediate submit
        $(document).on('click', 'button[name="action"][value="booking"]', function(e) {
            e.preventDefault(); // prevent default submit

            // compute current net amount from your inputs (use same logic as recalcSummary)
            const bill = parseFloat($('#billAmount').val()) || 0;
            const extra = parseFloat($('#extraDiscount').val()) || 0;
            const net = (bill - extra);
            $('#modalNetAmount').val(net.toFixed(2));

            // default advance = previously set hidden advance or 0
            const existingAdvance = parseFloat($('#advance_payment').val()) || 0;
            $('#modalAdvance').val(existingAdvance > 0 ? existingAdvance.toFixed(2) : 0);

            // show modal
            var bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'), {
                backdrop: 'static'
            });
            bookingModal.show();
        });

        // Confirm booking inside modal
        $('#confirmBookingBtn').on('click', function() {
            const net = parseFloat($('#modalNetAmount').val()) || 0;
            let advance = parseFloat($('#modalAdvance').val()) || 0;

            if (advance < 0) {
                alert('Advance cannot be negative');
                return;
            }
            if (advance > net) {
                if (!confirm('Advance is more than Net amount. Continue?')) return;
            }

            // set hidden field (already in the sales form)
            $('#advance_payment').val(advance.toFixed(2));

            // ensure the salesForm has a hidden input 'action' = 'booking'
            if ($salesForm.find('input[name="action"]').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'action',
                    value: 'booking'
                }).appendTo($salesForm);
            } else {
                $salesForm.find('input[name="action"]').val('booking');
            }

            // submit the sales form specifically (this avoids submitting any other form e.g. logout)
            $salesForm.submit();
        });

        // Optional: if modal canceled, do nothing
    });

    $(document).on('keydown', function(e) {

        // 🔴 IMPORTANT: agar product search me ho to scanner band
        if ($(e.target).hasClass('productSearch')) {
            return;
        }

        if ($(e.target).is('textarea')) return;

        IS_SCANNING = true;

        if (e.key === 'Enter') {
            e.preventDefault();

            if (scanBuffer.length >= 5) {
                handleSalesBarcode(scanBuffer);
            }

            scanBuffer = '';
            IS_SCANNING = false;
            return;
        }

        if (e.key.length === 1) {
            scanBuffer += e.key;
        }

        clearTimeout(scanTimer);
        scanTimer = setTimeout(() => {
            scanBuffer = '';
            IS_SCANNING = false;
        }, 120);
    });



    let scanBuffer = '';
    let scanTimer = null;

    $(document).on('keydown', function(e) {

        if ($(e.target).is('textarea')) return;

        IS_SCANNING = true;

        if (e.key === 'Enter') {
            e.preventDefault();

            if (scanBuffer.length >= 5) {
                handleSalesBarcode(scanBuffer);
            }

            scanBuffer = '';
            IS_SCANNING = false;
            return;
        }

        if (e.key.length === 1) {
            scanBuffer += e.key;
        }

        clearTimeout(scanTimer);
        scanTimer = setTimeout(() => {
            scanBuffer = '';
            IS_SCANNING = false;
        }, 120);
    });


    function handleSalesBarcode(barcode) {
        $.get("{{ route('search-product-by-barcode') }}", {
            barcode
        }, function(res) {
            console.log('SCANNED BARCODE:', barcode);
            console.log('AJAX RESPONSE:', res);

            if (!res) {
                console.warn('Barcode not found:', barcode);
                return;
            }

            let foundRow = null;

            // Step 1: check if product already exists in table
            $('#purchaseItems tr').each(function() {
                const pid = $(this).find('.product_id').val();
                if (pid && parseInt(pid) === parseInt(res.id)) {
                    foundRow = $(this);
                    return false;
                }
            });

            if (foundRow) {

                const qtyInput = foundRow.find('.quantity');
                let currentQty = parseInt(qtyInput.val()) || 0;
                qtyInput.val(currentQty + 1);

                recalcRow(foundRow);
                recalcSummary();

                // 🔥 VISUAL INDICATION START
                foundRow.addClass('qty-highlight');
                qtyInput.addClass('qty-pulse');

                // 🔥 +1 badge
                const badge = $('<span class="qty-indicator">+1</span>');
                qtyInput.closest('td').css('position', 'relative').append(badge);

                // cleanup
                setTimeout(() => {
                    foundRow.removeClass('qty-highlight');
                    qtyInput.removeClass('qty-pulse');
                    badge.remove();
                }, 1000);

                // 🔥 Optional: auto scroll to that row
                foundRow[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                return;
            }

            // Step 2: find first empty row (product_id empty)
            if (!res || !res.id) {
                alert('Product not found!');
                return;
            }

            let $lastRow = $('#purchaseItems tr:last');

            // If last row already has a product, add a new row first
            if ($lastRow.find('.product_id').val()) {
                appendBlankRow(true);
                $lastRow = $('#purchaseItems tr:last');
            }

            // Fill product info
            $lastRow.find('.productSearch').val(res.name).prop('readonly', true).addClass('bg-light');
            $lastRow.find('.product_id').val(res.id);
            $lastRow.find('.item_code input').val(res.code);
            $lastRow.find('.uom input').val(res.uom);
            $lastRow.find('.unit input').val(res.unit);
            $lastRow.find('.price').val(res.price);
            $lastRow.find('.product-note').val(res.note || '');
            $lastRow.find('.quantity').val(1);
            $lastRow.find('.item_disc').val(0);

            recalcRow($lastRow);
            recalcSummary();

            // Move focus to Qty of this row
            $lastRow.find('.quantity').focus();

            // Add next blank row automatically for the next scan
            appendBlankRow(true);
        });
    }



    $('#openProductModal').on('click', function(e) {
        e.preventDefault();
        openProductModal();
    });
    $(document).on('click', '.modal-product-item', function() {
        $('#productModal').modal('hide');

        // Reset search box
        $('#modalProductSearch').val('');
        $('#modalSearchResults').empty();
    });
    $('#productModal').on('shown.bs.modal', function() {
        const $input = $('#modalProductSearch');
        $input.focus(); // Cursor focus on search input
        activeIndex = 0;
        setActiveItem(activeIndex); // First item active
    });
    $(document).on('keydown', function(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            openProductModal();
        }
    });

    function openProductModal() {
        // Reset search input & results
        $('#modalProductSearch').val('');
        $('#modalSearchResults').empty();

        // Show modal
        $('#productModal').modal('show');

        // Focus input after modal fully shown
        setTimeout(() => {
            const $input = $('#modalProductSearch');
            $input.focus();
            activeIndex = 0;
            setActiveItem(activeIndex); // first item active
        }, 300); // Bootstrap modal animation ke liye
    }

    let modalTimer = null;

    $('#modalProductSearch').on('input', function() {
        clearTimeout(modalTimer);
        let q = $(this).val().trim();

        if (q.length < 2) {
            $('#modalSearchResults').empty();
            return;
        }

        modalTimer = setTimeout(() => {
            $.get("{{ route('search-product-name') }}", {
                q
            }, function(res) {

                let html = '';
                res.forEach(p => {
                    let noteText = (p.note && p.note.trim() !== '') ? p.note : '-';
                    let priceHtml = '';

                    if (p.has_discount) {
                        priceHtml = `
            <span style="text-decoration:line-through;color:#fff;">Rs: ${p.original_price}</span> 
            <span style="color:red;font-weight:bold;">Rs: ${p.price}</span>
            <span class="badge bg-danger">${p.discount_percent}% OFF</span>
        `;
                    } else {
                        priceHtml = `<span>Rs: ${p.price}</span>`;
                    }

                    html += `
<li class="list-group-item modal-product-item"
    data-id="${p.id}"
    data-name="${p.item_name}"
    data-code="${p.item_code}"
    data-price="${p.price}"
    data-original-price="${p.original_price}"
    data-discount="${p.discount_percent}"
    data-unit="${p.unit_id}"
    data-brand="${p.brand ?? ''}"
    data-note="${noteText}">
    <strong>${p.item_name}</strong>
    <br>
    <small>${priceHtml} | ${p.brand ?? '-'}</small>
    <br>
    <strong class="text-dark">Note: ${noteText}</strong>
</li>`;
                });

                $('#modalSearchResults').html(html);
                activeIndex = -1;
                setActiveItem(0);
            });
        }, 250);
    });

    $(document).on('mouseenter', '.modal-product-item', function() {
        const index = $(this).index();
        setActiveItem(index);
    });

    $('#modalProductSearch').on('keydown', function(e) {

        const items = $('#modalSearchResults .modal-product-item');

        if (!items.length) return;

        switch (e.key) {

            case 'ArrowDown':
                e.preventDefault();
                setActiveItem(activeIndex + 1);
                break;

            case 'ArrowUp':
                e.preventDefault();
                setActiveItem(activeIndex - 1);
                break;

            case 'Enter':
                e.preventDefault();
                if (activeIndex >= 0) {
                    items.eq(activeIndex).trigger('click');
                }
                break;
        }
    });

    $(document).on('click', '.modal-product-item', function() {
        const p = $(this);

        let $row = $('#purchaseItems tr').filter(function() {
            return !$(this).find('.product_id').val();
        }).first();

        if (!$row.length) {
            appendBlankRow();
            $row = $('#purchaseItems tr:last');
        }

        // Fill data
        $row.find('.product_id').val(p.data('id'));
        $row.find('.productSearch')
            .val(p.data('name'))
            .prop('readonly', true)
            .addClass('bg-light');

        $row.find('[name="item_code[]"]').val(p.data('code'));
        $row.find('[name="unit[]"]').val(p.data('unit'));
        $row.find('[name="uom[]"]').val(p.data('brand') || '');
        $row.find('.price').val(p.data('price'));
        $row.find('.product-note').val(p.data('note') || '');

        $row.find('.quantity').val(1);
        $row.find('.item_disc').val(0);

        recalcRow($row);
        recalcSummary();

        // ✅ IMPORTANT: focus ONLY qty
        setTimeout(() => {
            $row.find('.quantity').focus().select();
        }, 50);

        // ❌ DO NOT append new row here
        $('#productModal').modal('hide');
    });



    let activeIndex = -1;

    function setActiveItem(index) {
        const items = $('#modalSearchResults .modal-product-item');
        items.removeClass('active');

        if (items.length === 0) return;

        if (index < 0) index = 0;
        if (index >= items.length) index = items.length - 1;

        activeIndex = index;

        const activeItem = items.eq(activeIndex);
        activeItem.addClass('active');

        // 🔥 auto scroll into view
        activeItem[0].scrollIntoView({
            block: 'nearest'
        });
    }


</script>
@endsection
