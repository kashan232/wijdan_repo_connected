@extends('admin_panel.layout.app')
@section('content')
<style>
    /* small helpers */
    .searchResults {
        position: absolute;
        z-index: 9999;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background: #fff;
        text-align: start;
    }

    .search-result-item.active {
        background: #007bff;
        color: #fff;
    }

    .small-muted {
        font-size: 11px;
        color: #6c757d;
    }

    .table-scroll tbody {
        display: block;
        max-height: calc(60px * 5);
        overflow-y: auto;
    }

    .table-scroll thead,
    .table-scroll tbody tr {
        display: table;
        width: 100%;
        table-layout: fixed;
    }

    .disabled-row input {
        background-color: #f8f9fa;
        pointer-events: none;
    }
</style>

<div class="container-fluid">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header ">
            <h5 class="mb-0 text-dark">SALES RETURN</h5>
            <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm">Back</a>
        </div>

        <form action="{{ route('sales.return.store') }}" method="POST">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale->id }}">

            <div class="card-body">
                @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Customer:</label>
                        <select name="customer" class="form-control form-control-sm">
                            @php
                            // Optional: make a placeholder Walk-in customer with ID = 0
                            $walkInId = 0;
                            @endphp
                            <option value="{{ $walkInId }}" {{ $sale->customer == $walkInId ? 'selected' : '' }}>Walk-in Customer</option>
                            @foreach ($Customer as $c)
                            <option value="{{ $c->id }}" {{ $sale->customer == $c->id ? 'selected' : '' }}>
                                {{ $c->customer_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Reference #</label>
                        <input type="text" name="reference" class="form-control form-control-sm" value="{{ $sale->reference }}">
                    </div>
                </div>

                <!-- ===== UPPER: Sale Items with checkboxes ===== -->
                <h6>Sold Items</h6>
                <div class="table-responsive" style="max-height:260px; overflow:auto;">
                    <table class="table table-bordered table-sm align-middle text-center" id="soldItemsTable">
                        <thead>
                            <tr>
                                <th style="width:60px">Return?</th>
                                <th>Product</th>
                                <th>Item Code</th>
                                <th>Note</th>
                                <th>Brand</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Sold Qty</th>
                                <th>Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($saleItems as $item)
                            <tr data-product-id="{{ $item['product_id'] }}"
                                data-price="{{ $item['price'] }}"
                                data-unit="{{ $item['unit'] }}"
                                data-item-disc="{{ $item['discount'] }}"
                                data-note="@json($item['note'] ?? '')">
                                <td>
                                    <input type="checkbox" class="select-return-item" {{ ($item['available_qty'] ?? 0) <= 0 ? 'disabled' : '' }}>
                                </td>
                                <td class="text-start">{{ $item['item_name'] }}</td>
                                <td>{{ $item['item_code'] }}</td>
                                <td class="text-start">
                                    @if(!empty($item['note']))
                                    <small class="small-muted">{!! nl2br(e($item['note'])) !!}</small>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>{{ $item['brand'] }}</td>
                                <td>{{ $item['unit'] }}</td>
                                <td>{{ number_format($item['price'],2) }}</td>
                                <td>{{ $item['qty'] }}</td>
                                <td class="available-qty">{{ $item['available_qty'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <hr>

                <!-- ===== LOWER: Selected items to return (form inputs) ===== -->
                <h6>Items Selected for Return</h6>
                <div class="table-responsive" style="max-height:320px; overflow:auto;">
                    <table class="table table-bordered table-sm align-middle text-center" id="returnItemsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Item Code</th>
                                <th>Note</th>
                                <th>Brand</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Discount</th>
                                <th>Return Qty</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JS will append selected rows here -->
                        </tbody>
                    </table>
                </div>

                <!-- ===== Summary ===== -->
                <table class="table table-bordered table-sm mt-3 text-center">
                    <tr>
                        <th>Amount In Words</th>
                        <th>BILL AMOUNT</th>
                        <th>ITEM DISCOUNT</th>
                        <th>EXTRA DISCOUNT</th>
                        <th>NET AMOUNT</th>
                        <th>Cash</th>
                        <th>Card</th>
                        <th>Change</th>
                    </tr>
                    <tr>
                        <td><input type="text" id="amountInWords" class="form-control form-control-sm" name="total_amount_Words" readonly></td>
                        <td><input type="text" id="billAmount" class="form-control form-control-sm text-center" name="total_subtotal" readonly></td>
                        <td><input type="text" id="itemDiscount" class="form-control form-control-sm text-center" name="total_discount" readonly></td>
                        <td><input type="number" id="extraDiscount" name="total_extra_cost" class="form-control form-control-sm text-center" value="0"></td>
                        <td><input type="text" id="netAmount" name="total_net" class="form-control form-control-sm text-center" readonly></td>
                        <td><input type="number" id="cash" name="cash" class="form-control form-control-sm text-center" value="0"></td>
                        <td><input type="number" id="card" name="card" class="form-control form-control-sm text-center" value="0"></td>
                        <td><input type="text" id="change" name="change" class="form-control form-control-sm text-center" readonly></td>
                    </tr>
                </table>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div><strong>TOTAL PIECES : </strong> <span id="totalPieces">0</span></div>
                    <div>
                        <button type="submit" class="btn btn-success">Return Sale</button>
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">Close</a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Prevent Double Submission
        let isSubmitting = false;
        $('form').on('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            isSubmitting = true;
            $(this).find('button[type="submit"]').prop('disabled', true).text('Processing...');
        });


        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function num(v) {
            return isNaN(parseFloat(v)) ? 0 : parseFloat(v);
        }

        // When checkbox toggles in upper table
        $('#soldItemsTable').on('change', '.select-return-item', function() {
            const $row = $(this).closest('tr');
            const productId = String($row.data('product-id') ?? '');
            const price = num($row.data('price'));
            const unit = $row.data('unit') || '';
            const itemDisc = num($row.data('item-disc'));
            const productName = $row.find('td').eq(1).text().trim();
            const itemCode = $row.find('td').eq(2).text().trim();
            const brand = $row.find('td').eq(4).text().trim();
            const availableQty = num($row.find('.available-qty').text());
            const rawNote = $row.attr('data-note') || '';

            let note = '';
            try {
                const parsed = JSON.parse(rawNote);
                if (Array.isArray(parsed)) {
                    note = parsed.join('\n');
                } else {
                    note = String(parsed);
                }
            } catch (e) {
                const txt = document.createElement('textarea');
                txt.innerHTML = rawNote;
                note = txt.value;
            }

            if (this.checked) {
                if (availableQty <= 0) {
                    this.checked = false;
                    return;
                }
                if ($('#returnItemsTable tbody tr[data-product-id="' + productId + '"]').length) return;

                const returnQtyDefault = Math.min(availableQty, 1);

                // Build return row with textarea for note (and keep name color[] for backend)
                const rowHtml = `
<tr data-product-id="${escapeHtml(productId)}">
    <td class="text-start">
        ${escapeHtml(productName)}
        <input type="hidden" name="product[]" value="${escapeHtml(productName)}">
        <input type="hidden" name="product_id[]" value="${escapeHtml(productId)}">
        <input type="hidden" name="item_code[]" value="${escapeHtml(itemCode)}">
    </td>
    <td>${escapeHtml(itemCode)}</td>
    <td>
        <textarea name="color[]" class="form-control form-control-sm note-textarea" rows="2" readonly>${escapeHtml(note)}</textarea>
    </td>
    <td>
        ${escapeHtml(brand)}
        <input type="hidden" name="brand[]" value="${escapeHtml(brand)}">
    </td>
    <td>
        ${escapeHtml(unit)}
        <input type="hidden" name="unit[]" value="${escapeHtml(unit)}">
    </td>
    <td>
        <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price-input" value="${price}">
    </td>
    <td>
        <input type="number" step="0.01" name="item_disc[]" class="form-control form-control-sm disc-input" value="${itemDisc}">
    </td>
    <td>
        <input type="number" step="0.01" name="qty[]" class="form-control form-control-sm qty-input" value="${returnQtyDefault}" min="0.01" max="${availableQty}">
        <div class="small-muted">Max: <span class="max-qty">${availableQty}</span></div>
    </td>
    <td>
        <input type="text" name="total[]" class="form-control form-control-sm row-total" value="${(price*returnQtyDefault - itemDisc).toFixed(2)}" readonly>
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-danger remove-return-item">X</button>
    </td>
</tr>
`;
                $('#returnItemsTable tbody').append(rowHtml);

                // reduce available shown in top row
                const newAvailable = availableQty - returnQtyDefault;
                $row.find('.available-qty').text(newAvailable);
                if (newAvailable <= 0) $row.find('.select-return-item').prop('disabled', true);
            } else {
                // unchecked: remove from return table and restore available qty
                const $returnRow = $('#returnItemsTable tbody tr[data-product-id="' + productId + '"]');
                if ($returnRow.length) {
                    const prevQty = num($returnRow.find('.qty-input').val());
                    const currentAvailable = num($row.find('.available-qty').text());
                    $row.find('.available-qty').text((currentAvailable + prevQty).toString());
                    $row.find('.select-return-item').prop('disabled', false);
                    $returnRow.remove();
                }
            }
            recalcAll();
        });

        // Remove button in return table
        $('#returnItemsTable').on('click', '.remove-return-item', function() {
            const $returnRow = $(this).closest('tr');
            const productId = $returnRow.data('product-id');
            const qtyRemoved = num($returnRow.find('.qty-input').val());

            const $topRow = $('#soldItemsTable tbody tr[data-product-id="' + productId + '"]');
            if ($topRow.length) {
                const curAvailable = num($topRow.find('.available-qty').text());
                $topRow.find('.available-qty').text(curAvailable + qtyRemoved);
                $topRow.find('.select-return-item').prop('checked', false).prop('disabled', false);
            }

            $returnRow.remove();
            recalcAll();
        });

        // When user edits qty/price/discount in lower table
        $('#returnItemsTable').on('input', '.qty-input, .price-input, .disc-input', function() {
            const $row = $(this).closest('tr');
            const productId = $row.data('product-id');
            let qty = num($row.find('.qty-input').val());
            const price = num($row.find('.price-input').val());
            const disc = num($row.find('.disc-input').val());
            const max = num($row.find('.qty-input').attr('max'));

            if (qty > max) {
                qty = max;
                $row.find('.qty-input').val(max);
            } else if (qty < 0.01) {
                qty = 0.01;
                $row.find('.qty-input').val(0.01);
            }

            const newTotal = Math.max(0, (price * qty) - disc);
            $row.find('.row-total').val(newTotal.toFixed(2));

            // update available = max - qty
            const topNew = num($row.find('.qty-input').attr('max')) - qty;
            const $topRow = $('#soldItemsTable tbody tr[data-product-id="' + productId + '"]');
            if ($topRow.length) {
                $topRow.find('.available-qty').text(topNew);
                if (topNew <= 0) $topRow.find('.select-return-item').prop('disabled', true);
                else $topRow.find('.select-return-item').prop('disabled', false).prop('checked', true);
            }

            recalcAll();
        });

        // recalc summary totals & pieces
        function recalcAll() {
            let billAmount = 0,
                itemDiscount = 0,
                totalQty = 0;
            $('#returnItemsTable tbody tr').each(function() {
                billAmount += num($(this).find('.row-total').val());
                itemDiscount += num($(this).find('.disc-input').val());
                totalQty += num($(this).find('.qty-input').val());
            });

            const extraDiscount = num($('#extraDiscount').val());
            const cash = num($('#cash').val());
            const card = num($('#card').val());
            const net = Math.max(0, billAmount - itemDiscount - extraDiscount);
            const change = (cash + card) - net;

            $('#billAmount').val(billAmount.toFixed(2));
            $('#itemDiscount').val(itemDiscount.toFixed(2));
            $('#netAmount').val(net.toFixed(2));
            $('#change').val(change.toFixed(2));
            $('#amountInWords').val(numberToWords(Math.round(net)));
            $('#totalPieces').text(totalQty);
        }

        // numberToWords function for invoice words
        function numberToWords(num) {
            const a = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
                "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"
            ];
            const b = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
            if ((num = num.toString()).length > 9) return "Overflow";
            const n = ("000000000" + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{3})$/);
            if (!n) return;
            let str = "";
            str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + " " + a[n[1][1]]) + " Crore " : "";
            str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + " " + a[n[2][1]]) + " Lakh " : "";
            str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + " " + a[n[3][1]]) + " Thousand " : "";
            str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + " " + a[n[4][1]]) + " " : "";
            return str.trim() + " Rupees Only";
        }

        // init any pre-calcs
        recalcAll();

        // update recalc when summary inputs change
        $('#extraDiscount, #cash, #card').on('input', function() {
            recalcAll();
        });

    });
</script>
@endsection