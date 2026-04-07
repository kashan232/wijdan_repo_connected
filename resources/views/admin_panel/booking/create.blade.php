@extends('admin_panel.layout.app')
@section('content')
<style>
    .searchResults {
        position: absolute;
        z-index: 9999;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background: #fff;
        /* border: 1px solid #ddd; */
        text-align: start
    }

    .search-result-item.active {
        background: #007bff;
        color: white;
    }

    .table-fixed {
        table-layout: fixed;
    }

    .product-col {
        width: 20%;
        min-width: 320px;
    }

    .product-col input {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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

    .booking-btn {
        font-size: 1.1rem;
        font-weight: 600;
        padding: 10px 28px;
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.4);
        transition: all 0.2s ease-in-out;
    }

    .booking-btn:hover {
        transform: scale(1.05);
        background-color: #007bff !important;
        box-shadow: 0 0 14px rgba(0, 123, 255, 0.6);
    }

    .disabled-row input {
        background-color: #f8f9fa;
        pointer-events: none;
    }
</style>
<div class="container-fluid">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-light text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark">Booking System</h5>
            <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm  text-center">
                Back
            </a>
        </div>
        <form action="{{ route('bookings.store') }}" method="POST">

            @csrf
            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            <div class="card-body">
                {{-- Top Form --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Customer:</label>
                        <select name="customer" class="form-control form-control-sm">
                            <option value="Walk-in Customer">Walk-in Customer</option>
                            @foreach ($Customer as $c)
                            <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Reference #</label>
                        <input type="text" name="reference" class="form-control form-control-sm">
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
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                <th>Color</th>

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



                        </style>
                        <tbody id="purchaseItems" style="max-height: 300px; overflow-y: auto;">
                            <tr>
                                <td class="product-col text-start">
                                    <input type="hidden" name="product_id[]" class="product_id">
                                    <input type="text"
                                        class="form-control productSearch"
                                        placeholder="Select product..."
                                        readonly>
                                </td>

                                <td class="item_code border">
                                    <input type="text" name="item_code[]" class="form-control" readonly>
                                <td class="color border">
                                    <select class="form-control form-control-sm color-dropdown" name="color[]">
                                        <option value="">Select Color</option>
                                    </select>
                                </td>


                                <td class="uom border">
                                    <input type="text" name="uom[]" class="form-control" readonly>
                                </td>

                                <td class="unit border">
                                    <input type="text" name="unit[]" class="form-control" readonly>
                                </td>

                                <!-- Price = price (readonly) -->
                                <td>
                                    <input type="number" step="0.01" name="price[]" class="form-control price"
                                        value="">
                                </td>

                                <!-- Per-item Discount (PKR, editable) -->
                                <td>
                                    <input type="number" step="0.01" name="item_disc[]"
                                        class="form-control item_disc" value="">
                                </td>

                                <td class="qty">
                                    <input type="number" name="qty[]" class="form-control quantity" value=""
                                        min="1">
                                </td>

                                <!-- Row Total (readonly) -->
                                <td class="total border">
                                    <input type="text" name="total[]" class="form-control row-total" readonly>
                                </td>

                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>

                {{-- Amount Summary --}}
                <table class="table table-bordered table-sm mt-4 mb-0 text-center">
                    <tr>
                        <th>Amount In Words : </th>
                        <th>BILL AMOUNT</th>
                        <th>ITEM DISCOUNT</th>
                        <th>EXTRA DISCOUNT</th>
                        <th>NET AMOUNT</th>
                        <th>Cash</th>
                        <th>C/D Card</th>
                        <th>Change</th>
                    </tr>
                    <tr class="align-middle">
                        <td><input type="text" name="total_amount_Words" class="form-control form-control-sm"
                                id="amountInWords" readonly></td>
                        <td><input type="text" name="total_subtotal" class="form-control form-control-sm text-center"
                                id="billAmount" readonly></td>
                        <td><input type="text" name="total_discount" class="form-control form-control-sm text-center"
                                id="itemDiscount" readonly></td>
                        <td><input type="number" name="total_extra_cost"
                                class="form-control form-control-sm text-center" id="extraDiscount" value="0">
                        </td>
                        <td><input type="text" name="total_net" class="form-control form-control-sm text-center"
                                id="netAmount" readonly></td>
                        <td><input type="number" name="cash" class="form-control form-control-sm text-center"
                                id="cash" value="0"></td>
                        <td><input type="number" name="card" class="form-control form-control-sm text-center"
                                id="card" value="0"></td>
                        <td><input type="text" name="change" class="form-control form-control-sm text-center"
                                id="change" readonly></td>
                    </tr>

                </table>


                {{-- Footer Buttons --}}
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        <strong>TOTAL PIECES : </strong> <span>0</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="booking" class="btn btn-success booking-btn">Booking</button>
                        <button type="button" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection


@section('scripts')


<script>
    function num(n) {
        return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
    }

    function numberToWords(num) {
        const a = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
            "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen",
            "Eighteen", "Nineteen"
        ];
        const b = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
        if ((num = num.toString()).length > 9) return "Overflow";
        const n = ("000000000" + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{3})$/);
        if (!n) return '';
        let str = "";
        str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + " " + a[n[1][1]]) + " Crore " : "";
        str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + " " + a[n[2][1]]) + " Lakh " : "";
        str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + " " + a[n[3][1]]) + " Thousand " : "";
        str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + " " + a[n[4][1]]) + " " : "";
        return str.trim() + " Rupees Only";
    }

    function recalcRow($row) {
        const qty = num($row.find('.quantity').val());
        const price = num($row.find('.price').val());
        const disc = num($row.find('.item_disc').val());

        let total = (qty * price) - disc;
        if (total < 0) total = 0;

        $row.find('.row-total').val(total.toFixed(2));
    }

    function recalcSummary() {
        let billAmount = 0;
        let itemDiscount = 0;
        let totalQty = 0;

        $('#purchaseItems tr').each(function() {
            billAmount += num($(this).find('.row-total').val());
            itemDiscount += num($(this).find('.item_disc').val());
            totalQty += num($(this).find('.quantity').val());
        });

        const extraDiscount = num($('#extraDiscount').val());
        const cash = num($('#cash').val());
        const card = num($('#card').val());

        const net = billAmount - itemDiscount - extraDiscount;
        const change = (cash + card) - net;

        $('#billAmount').val(billAmount.toFixed(2));
        $('#itemDiscount').val(itemDiscount.toFixed(2));
        $('#netAmount').val(net.toFixed(2));
        $('#change').val(change.toFixed(2));
        $('#amountInWords').val(numberToWords(Math.round(net)));

        $('strong:contains("TOTAL PIECES")').next().text(totalQty);
    }


    // Events
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
</script>

<script>
    function appendBlankRow() {
        const newRow = `
<tr>
    <td class="product-col text-start">
        <input type="hidden" name="product_id[]" class="product_id">
        <input type="text" class="form-control productSearch" placeholder="Select product..." readonly>
        <td class="color border">
    <select class="form-control form-control-sm color-dropdown" name="color[]">
        <option value="">Select Color</option>
    </select>
</td>
    <td class="item_code border">
                                    <input type="text" name="item_code[]" class="form-control" readonly>
    <td class="uom border"><input type="text" name="uom[]" class="form-control" readonly></td>
    <td class="unit border"><input type="text" name="unit[]" class="form-control" readonly></td>
    <td><input type="number" step="0.01" name="price[]" class="form-control price" value="1" ></td>
    <td><input type="number" step="0.01" name="item_disc[]" class="form-control item_disc" value=""></td>
    <td class="qty"><input type="number" name="qty[]" class="form-control quantity" value="" min="1"></td>
    <td class="total border"><input type="text" name="total[]" class="form-control row-total" readonly></td>
    <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
</tr>`;
        $('#purchaseItems').append(newRow);
    }

    $(document).ready(function() {

        // ---------- Helper Functions ----------
        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        function recalcRow($row) {
            const qty = num($row.find('.quantity').val());
            const price = num($row.find('.price').val());
            const disc = num($row.find('.item_disc').val());
            let total = (qty * price) - disc;
            if (total < 0) total = 0;
            $row.find('.row-total').val(total.toFixed(2));
        }

        function recalcSummary() {
            let sub = 0;
            $('#purchaseItems .row-total').each(function() {
                sub += num($(this).val());
            });
            $('#subtotal').val(sub.toFixed(2));

            const oDisc = num($('#overallDiscount').val());
            const xCost = num($('#extraCost').val());
            const net = (sub - oDisc + xCost);
            $('#netAmount').val(net.toFixed(2));
        }



        // ---------- Product Search ----------
        $(document).on('keyup', '.productSearch', function(e) {
            const $input = $(this);
            const q = $input.val().trim();
            const $row = $input.closest('tr');
            const $box = $row.find('.searchResults');

            if (q.length === 0) {
                $box.empty();
                return;
            }

            // Keyboard Navigation
            const isNavKey = ['ArrowDown', 'ArrowUp', 'Enter'].includes(e.key);
            if (isNavKey && $box.children('.search-result-item').length) {
                const $items = $box.children('.search-result-item');
                let idx = $items.index($items.filter('.active'));
                if (e.key === 'ArrowDown') {
                    idx = (idx + 1) % $items.length;
                    $items.removeClass('active');
                    $items.eq(idx).addClass('active');
                    e.preventDefault();
                    return;
                }
                if (e.key === 'ArrowUp') {
                    idx = (idx <= 0 ? $items.length - 1 : idx - 1);
                    $items.removeClass('active');
                    $items.eq(idx).addClass('active');
                    e.preventDefault();
                    return;
                }
                if (e.key === 'Enter') {
                    if (idx >= 0) {
                        $items.eq(idx).trigger('click');
                    } else if ($items.length === 1) {
                        $items.eq(0).trigger('click');
                    }
                    e.preventDefault();
                    return;
                }
            }

            // AJAX call to search
            $.ajax({
                url: "{{ route('search-product-name') }}",
                type: 'GET',
                data: {
                    q
                },
                success: function(data) {
                    let html = '';
                    (data || []).forEach(p => {
                        const brand = (p.brand && p.brand.name) ? p.brand.name : '';
                        const unit = (p.unit_id ?? '');
                        const price = (p.price ?? 0);
                        const code = (p.item_code ?? '');
                        const name = (p.item_name ?? '');
                        const id = (p.id ?? '');
                        const colors = p.color ? p.color : '[]';

                        html += `
<li class="list-group-item search-result-item"
    tabindex="0"
    data-product-id="${id}"
    data-product-name="${name}"
    data-product-uom="${brand}"
    data-product-unit="${unit}"
    data-product-code="${code}"
    data-price="${price}"
    data-colors='${colors}'>
  ${name}
</li>`;
                    });
                    $box.html(html);
                    $box.children('.search-result-item').first().addClass('active');
                },
                error: function() {
                    $box.empty();
                }
            });
        });

        // On Click Product Suggestion
        $(document).on('click', '.search-result-item', function() {

            const $li = $(this);
            const $row = $li.closest('tr');

            $row.find('.productSearch')
                .val($li.data('product-name'))
                .prop('readonly', true)
                .addClass('bg-light');

            $row.find('.item_code input').val($li.data('product-code'));
            $row.find('.uom input').val($li.data('product-uom'));
            $row.find('.unit input').val($li.data('product-unit'));
            $row.find('.price').val($li.data('price'));
            $row.find('.product_id').val($li.data('product-id'));

            $row.find('.item_disc').val(0);
            $row.find('.quantity').val(1);

            // Colors
            const colors = JSON.parse($li.attr('data-colors') || '[]');
            const $colorSelect = $row.find('.color-dropdown');
            $colorSelect.empty().append('<option value="">Select Color</option>');
            colors.forEach(c => $colorSelect.append(`<option value="${c}">${c}</option>`));

            recalcRow($row);
            recalcSummary();

            // 🔥 CLEANUP
            row.find('.searchResults').empty().hide();
            row.attr('data-scanned', '1');

            // 🔥 MOVE TO QTY
            $row.find('.quantity').focus();
        });

        $(document).on('keydown', function(e) {

            // 🔥 SEARCH input me scanner DISABLE
            if ($(e.target).hasClass('productSearch')) {
                return;
            }

            if (e.key === 'Enter') {
                if (scanBuffer && scanBuffer.length >= 5) {
                    handleSalesBarcode(scanBuffer);
                }
                scanBuffer = '';
                return;
            }

        });

        // Keyboard Enter on suggestion
        $(document).on('keydown', '.searchResults .search-result-item', function(e) {
            if (e.key === 'Enter') {
                $(this).trigger('click');
            }
        });

        $(document).on('keydown', '.quantity', function(e) {

            if (e.key === 'Enter') {
                e.preventDefault();

                const $row = $(this).closest('tr');
                const qty = parseFloat($(this).val());

                if (!qty || qty <= 0) {
                    alert('Please enter valid quantity');
                    return;
                }

                recalcRow($row);
                recalcSummary();

                appendBlankRow();

                $('#purchaseItems tr:last .productSearch').focus();
            }
        });

        $(document).on('keydown', '.productSearch', function(e) {

            const $row = $(this).closest('tr');
            const $results = $row.find('.searchResults:visible');
            if (!$results.length) return;

            const $items = $results.children('.search-result-item');
            let $active = $items.filter('.active');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                let $next = $active.next();
                if ($next.length) {
                    $active.removeClass('active');
                    $next.addClass('active');

                    $results.scrollTop(
                        $results.scrollTop() + $next.position().top - $results.height() / 2
                    );
                }
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                let $prev = $active.prev();
                if ($prev.length) {
                    $active.removeClass('active');
                    $prev.addClass('active');

                    $results.scrollTop(
                        $results.scrollTop() + $prev.position().top - $results.height() / 2
                    );
                }
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                if ($active.length) {
                    $active.trigger('click');
                }
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

        // Select2 Color Init on focus

    });

    function handleSalesBarcode(barcode) {

        $.get("{{ route('search-product-by-barcode') }}", {
            barcode
        }, function(res) {

            if (!res) {
                alert('Barcode not found');
                return;
            }

            let foundRow = null;

            // 🔍 STEP 1: Check existing rows (SAME AS SALE)
            $('#purchaseItems tr').each(function() {
                const pid = $(this).find('.product_id').val();
                if (pid && parseInt(pid) === parseInt(res.id)) {
                    foundRow = $(this);
                    return false; // break
                }
            });

            // ✅ STEP 2: If already exists → QTY +1
            if (foundRow) {
                const qtyInput = foundRow.find('.quantity');
                const currentQty = parseFloat(qtyInput.val()) || 0;

                qtyInput.val(currentQty + 1);

                recalcRow(foundRow);
                recalcSummary();

                // Highlight the updated row
                foundRow.addClass('table-success');
                setTimeout(() => foundRow.removeClass('table-success'), 300);

                return; // Stop adding new row
            }

            // 🆕 STEP 3: New product → new row
            let row = $('#purchaseItems tr:last');

            if (row.find('.product_id').val()) {
                appendBlankRow();
                row = $('#purchaseItems tr:last');
            }

            row.find('.product_id').val(res.id);

            row.find('.productSearch')
                .val(res.name)
                .prop('readonly', true)
                .addClass('bg-light');

            row.find('[name="item_code[]"]').val(res.code);
            row.find('[name="uom[]"]').val(res.brand ?? '');
            row.find('[name="unit[]"]').val(res.unit);
            row.find('.price').val(res.price ?? 0);
            row.find('.quantity').val(1);
            row.find('.item_disc').val(0);
            row.find('.product-note').val(res.note ?? '');

            row.find('.searchResults').empty().hide();
            row.attr('data-scanned', '1'); // ✅ FIXED

            recalcRow(row);
            recalcSummary();

        });
    }


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

            if (!res) {
                alert('Barcode not found');
                return;
            }

            let foundRow = null;

            // 🔍 STEP 1: Check existing rows
            $('#purchaseItems tr').each(function() {
                const pid = $(this).find('.product_id').val();
                if (pid && parseInt(pid) === parseInt(res.id)) {
                    foundRow = $(this);
                    return false; // break loop
                }
            });

            // ✅ STEP 2: If product already exists → qty +1
            if (foundRow) {
                let qtyInput = foundRow.find('.quantity');
                let currentQty = parseInt(qtyInput.val()) || 0;
                qtyInput.val(currentQty + 1);

                recalcRow(foundRow);
                recalcSummary();

                return; // ⛔ STOP here → no new row
            }

            // 🆕 STEP 3: Product not found → add new row
            let row = $('#purchaseItems tr:last');

            if (row.find('.product_id').val()) {
                appendBlankRow();
                row = $('#purchaseItems tr:last');
            }

            row.find('.product_id').val(res.id);
            row.find('.productSearch')
                .val(res.name)
                .prop('readonly', true);

            row.find('[name="item_code[]"]').val(res.code);
            row.find('[name="uom[]"]').val(res.brand ?? '');
            row.find('[name="unit[]"]').val(res.unit);
            row.find('.price').val(res.price ?? 0);
            row.find('.quantity').val(1);
            row.find('.item_disc').val(0);
            row.find('.product-note').val(res.note ?? '');

            row.find('.searchResults').empty().hide();
            row.attr('data-scanned', '1');
            recalcRow(row);
            recalcSummary();

            appendBlankRow();
            $('#purchaseItems tr:last .productSearch').focus();
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

                    html += `
<li class="list-group-item modal-product-item"
    data-id="${p.id}"
    data-name="${p.item_name}"
    data-code="${p.item_code}"
    data-price="${p.price}"
    data-unit="${p.unit_id}"
    data-brand="${p.brand ?? ''}"
    data-note="${noteText}">
    <strong>${p.item_name}</strong>
    <br>
    <small>Rs: ${p.price} | ${p.brand ?? '-'}</small>
    <br>
    <strong class="text-Dark">Note: ${noteText}</strong>

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
        let row = $('#purchaseItems tr:last');

        if (row.find('.product_id').val()) {
            appendBlankRow();
            row = $('#purchaseItems tr:last');
        }

        row.find('.product_id').val($(this).data('id'));
        row.find('.productSearch').val($(this).data('name')).prop('readonly', true);
        row.find('[name="item_code[]"]').val($(this).data('code'));
        row.find('[name="uom[]"]').val($(this).data('brand')); // ✅ BRAND
        row.find('[name="unit[]"]').val($(this).data('unit')); // ✅ UNIT
        row.find('.price').val($(this).data('price'));
        row.find('.quantity').val(1);
        row.find('.item_disc').val(0);
        row.find('.product-note').val($(this).data('note'));

        recalcRow(row);
        recalcSummary();

        // Hide modal
        $('#productModal').modal('hide');

        // Reset search input & results so next time modal opens clean
        $('#modalProductSearch').val('');
        $('#modalSearchResults').empty();

        // Focus quantity input in new row
        setTimeout(() => {
            row.find('.quantity').focus();
        }, 200);
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