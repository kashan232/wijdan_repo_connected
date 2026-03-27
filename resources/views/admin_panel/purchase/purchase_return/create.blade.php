{{-- Item Row Autocomplete + Add/Remove --}}
@extends('admin_panel.layout.app')

@section('content')
<style>
    /* search dropdown */
    .searchResults {
        position: absolute;
        z-index: 9999;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background: #fff;
    }

    .search-result-item.active {
        background: #007bff;
        color: white;
    }

    /* small layout tweaks */
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

    .table-scroll thead {
        width: calc(100% - 1em);
    }

    .table-scroll .icon-col {
        width: 51px;
        min-width: 51px;
        max-width: 40px;
    }

    .table-scroll {
        max-height: none !important;
        overflow-y: visible !important;
    }

    .disabled-row input {
        background-color: #f8f9fa;
        pointer-events: none;
    }

    .small-muted {
        font-size: 11px;
        color: #6c757d;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

            <div class="body-wrapper">
                <div class="bodywrapper__inner">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-nowrap overflow-auto">
                        <div class="flex-grow-1">
                            <h2 class="page-title m-0">Purchase Return</h2>
                        </div>
                    </div>

                    <div class="row gy-3">
                        <div class="col-lg-12 col-md-12 mb-30">
                            <div class="card">
                                <div class="card-body">

                                    @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @endif

                                    @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <strong>Success!</strong> {{ session('success') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    @endif

                                    <form action="{{ route('purchase.return.store') }}" method="POST">
                                        @csrf
                                        @php $isReturn = isset($purchase); @endphp

                                        <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
                                        <input type="hidden" name="purchase_to" value="{{ $purchase->purchase_to }}">
                                        <div class="row mb-3 g-3 mt-4">
                                            <div class="col-xl-3 col-sm-6 mt-3">
                                                <label><i class="bi bi-calendar-date text-primary me-1"></i> Purchase Date</label>
                                                <input name="purchase_date" type="date" class="form-control" value="{{ $purchase->purchase_date }}">
                                            </div>

                                            <div class="col-xl-3 col-sm-6 mt-3">
                                                <label><i class="bi bi-calendar-date text-primary me-1"></i> Return Date</label>
                                                <input name="return_date" type="date" class="form-control" value="{{ date('Y-m-d') }}">
                                            </div>

                                            <div class="col-xl-3 col-sm-6 mt-3">
                                                <label><i class="bi bi-receipt text-primary me-1"></i> Companies/Vendors</label>
                                                <select name="vendor_id" class="form-control">
                                                    <option disabled selected>Select One</option>
                                                    @foreach ($Vendor as $item)
                                                    <option value="{{ $item->id }}" {{ $isReturn && $purchase->vendor_id == $item->id ? 'selected' : '' }}>
                                                        {{ $item->name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-xl-3 col-sm-6 mt-3">
                                                <label><i class="bi bi-file-earmark-text text-primary me-1"></i> Company Inv #</label>
                                                <input name="purchase_order_no" type="text" class="form-control" value="{{ $purchase->invoice_no }}">
                                            </div>

                                            <div class="col-xl-4 col-sm-6 mt-3">
                                                <label>
                                                    <i class="bi bi-building text-primary me-1"></i>
                                                    Warehouse
                                                </label>

                                                <select name="warehouse_id"
                                                    class="form-control"
                                                    {{ $purchase->purchase_to === 'shop' ? 'disabled' : '' }}>

                                                    <option value="">N/A</option>

                                                    @foreach ($Warehouse as $item)
                                                    <option value="{{ $item->id }}"
                                                        {{ $purchase->warehouse_id == $item->id ? 'selected' : '' }}>
                                                        {{ $item->warehouse_name }}
                                                    </option>
                                                    @endforeach
                                                </select>

                                                @if($purchase->purchase_to === 'shop')
                                                <small class="text-muted">Not required for shop purchase</small>
                                                @endif
                                            </div>

                                            <div class="col-xl-4 col-sm-6 mt-3">
                                                <label><i class="bi bi-card-text text-primary me-1"></i> Job No & Description</label>
                                                <input name="note" type="text" class="form-control" value="{{ $purchase->note }}">
                                            </div>

                                            <div class="col-xl-4 col-sm-6 mt-3">
                                                <label><i class="bi bi-card-text text-primary me-1"></i> Transport Name</label>
                                                <input name="job_description" type="text" class="form-control" value="{{ $isReturn ? $purchase->job_description : '' }}">
                                            </div>
                                        </div>

                                        <!-- ===== PURCHASED ITEMS (upper table) ===== -->
                                        <div class="row">
                                            <div class="col-12">
                                                <h5>Purchased Items</h5>
                                                <div style="max-height: 220px; overflow-y: auto;">
                                                    <table class="table table-sm table-bordered" id="purchasedItemsTable">
                                                        <thead class="text-center">
                                                            <tr>
                                                                <th style="width:45px">Return?</th>
                                                                <th>Product</th>
                                                                <th>Item Note</th>
                                                                <th>Item Code</th>
                                                                <th>Brand</th>
                                                                <th>Unit</th>
                                                                <th class="text-end">Price</th>
                                                                <th class="text-end">Purchased Qty</th>
                                                                <th class="text-end">Available</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($purchase->items as $item)
                                                            <tr
                                                                data-product-id="{{ $item->product_id }}"
                                                                data-price="{{ $item->price }}"
                                                                data-unit="{{ $item->unit }}"
                                                                data-item-disc="{{ $item->item_discount ?? 0 }}"
                                                                data-item-note="{{ $item->note ?? '' }}">
                                                                <td class="text-center">
                                                                    <input type="checkbox"
                                                                        class="select-return-item"
                                                                        {{ ($item->available_qty ?? $item->qty) <= 0 ? 'disabled' : '' }}>
                                                                </td>

                                                                <td>{{ $item->product->item_name ?? '-' }}</td>

                                                                {{-- NOTE (readonly) --}}
                                                                <td>
                                                                    <input type="text"
                                                                        class="form-control form-control-sm"
                                                                        value="{{ $item->note }}"
                                                                        readonly>
                                                                </td>

                                                                <td>{{ $item->product->item_code ?? '-' }}</td>
                                                                <td>{{ $item->product->brand->name ?? '-' }}</td>
                                                                <td>{{ $item->unit }}</td>

                                                                <td class="text-end">{{ number_format($item->price,2) }}</td>
                                                                <td class="text-end">{{ $item->qty }}</td>
                                                                <td class="text-end available-qty">{{ $item->available_qty ?? $item->qty }}</td>
                                                            </tr>
                                                            @endforeach

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- ===== RETURN ITEMS (lower table) ===== -->
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h5>Items Selected for Return</h5>
                                                <div style="max-height: 260px; overflow-y: auto;">
                                                    <table class="table table-sm table-bordered" id="returnItemsTable">
                                                        <thead class="text-center">
                                                            <tr>
                                                                <th>Product</th>
                                                                <th>Note</th>
                                                                <th>Item Code</th>
                                                                <th>Brand</th>
                                                                <th>Unit</th>
                                                                <th>Price</th>
                                                                <th>Discount</th>
                                                                <th>Return Qty</th>
                                                                <th>Total</th>
                                                                <th style="width:85px">Remove</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- JS will append selected return rows here -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ===== SUMMARY ===== -->
                                        <div class="row g-3 mt-3">
                                            <div class="col-md-3">
                                                <label>Subtotal</label>
                                                <input type="text" id="summarySubtotal" name="subtotal" value="0.00" readonly class="form-control">
                                            </div>

                                            <div class="col-md-3">
                                                <label>Discount (Overall)</label>
                                                <input type="number" step="0.01" id="overallDiscount" class="form-control" name="discount" value="0">
                                            </div>

                                            <div class="col-md-3">
                                                <label>Extra Cost</label>
                                                <input type="number" step="0.01" id="extraCost" class="form-control" name="extra_cost" value="0">
                                            </div>

                                            <div class="col-md-3">
                                                <label>Net Amount</label>
                                                <input type="text" id="netAmount" name="net_amount" class="form-control fw-bold" value="0" readonly>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary">Submit Return</button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- bodywrapper__inner end -->
            </div><!-- body-wrapper end -->

        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
    $(document).ready(function() {
        // helper
        function num(v) {
            return isNaN(parseFloat(v)) ? 0 : parseFloat(v);
        }


        function pkrFromPct(pct, price) {
            if (!price) return 0;
            return (price * pct) / 100;
        }

        function pctFromPkr(pkr, price) {
            if (!price) return 0;
            return (pkr / price) * 100;
        }

        function recalcReturnRow($row) {
            const qty = num($row.find('.qty-input').val());
            const price = num($row.find('.price-input').val());
            const discPc = num($row.find('.disc-pkr').val()); // per piece PKR

            let total = (price * qty) - (discPc * qty);
            if (total < 0) total = 0;

            $row.find('.row-total').val(total.toFixed(2));
        }


        // When upper-table checkbox toggles
        $('#purchasedItemsTable').on('change', '.select-return-item', function() {
            const $row = $(this).closest('tr');
            const productId = $row.data('product-id').toString();
            const price = num($row.data('price'));
            const unit = $row.data('unit') || '';
            const itemDisc = num($row.data('item-disc'));
            const productName = $row.find('td').eq(1).text().trim();
            const itemCode = $row.find('td').eq(2).text().trim();
            const brand = $row.find('td').eq(3).text().trim();
            const availableQty = num($row.find('.available-qty').text());

            if (this.checked) {
                if (availableQty <= 0) {
                    this.checked = false;
                    return;
                }
                if ($('#returnItemsTable tbody tr[data-product-id="' + productId + '"]').length) {
                    return;
                }

                // If available < 1 (e.g. 0.5), use that. Else default to 1.
                // Or if user wants to return everything by default, use availableQty.
                // Let's stick to 1 unless available is less.
                let returnQtyDefault = (availableQty < 1) ? availableQty : 1;

                const rowHtml = `
<tr data-product-id="${productId}">
    <td>
        ${productName}
        <input type="hidden" name="product_id[]" value="${productId}">
    </td>

    <!-- NOTE -->
   <td>
    <!-- HIDDEN note (always submitted) -->
    <input type="hidden"
           name="item_note[]"
           class="item-note-hidden"
           value="${$row.data('item-note') || ''}">

    <!-- VISIBLE note (user editable) -->
    <input type="text"
           class="form-control form-control-sm item-note-visible"
           value="${$row.data('item-note') || ''}"
           placeholder="Return note">
</td>
    <td>${itemCode}</td>
    <td>${brand}</td>

    <td>
        ${unit}
        <input type="hidden" name="unit[]" value="${unit}">
    </td>

    <td>
        <input type="number" step="0.01"
               name="price[]"
               class="form-control form-control-sm price-input"
               value="${price}">
    </td>

    <!-- Discount -->
    <td>
        <div class="d-flex gap-1 align-items-center" style="min-width:140px;">
            <input type="number" step="0.01"
                   name="item_disc[]"
                   class="form-control form-control-sm disc-pkr"
                   placeholder="PKR" style="width:60%;">
            <div style="width:40%;display:flex;align-items:center;">
                <input type="number" step="0.01"
                       class="form-control form-control-sm disc-pct"
                       placeholder="%" style="width:70%;">
                <span style="width:30%;text-align:center;">%</span>
            </div>
        </div>
    </td>

    <td>
        <input type="number" step="0.01"
               name="qty[]"
               class="form-control form-control-sm qty-input"
               value="${returnQtyDefault}"
               min="0.01" max="${availableQty}">
        <div class="small-muted">Max: ${availableQty}</div>
    </td>

    <td>
        <input type="text"
               name="total[]"
               class="form-control form-control-sm row-total"
               readonly>
    </td>

    <td class="text-center">
        <button type="button"
                class="btn btn-sm btn-danger remove-return-item">
            Remove
        </button>
    </td>
</tr>
            `;
                $('#returnItemsTable tbody').append(rowHtml);

                // decrease displayed available quantity
                // Use toFixed to avoid floats like 1.40000000001
                const newAvailable = availableQty - returnQtyDefault;
                // Round to 2 decimals if needed, but 'num' handles parsing.
                // Just display clean string
                let displayAvail = parseFloat(newAvailable.toFixed(2));
                
                $row.find('.available-qty').text(displayAvail);
                if (displayAvail <= 0) {
                    $row.find('.select-return-item').prop('disabled', true);
                }
            } else {
                // unchecked -> remove return row and restore available qty
                const $returnRow = $('#returnItemsTable tbody tr[data-product-id="' + productId + '"]');
                if ($returnRow.length) {
                    const prevQty = num($returnRow.find('.qty-input').val());
                    const currentAvailable = num($row.find('.available-qty').text());
                    
                    let restored = currentAvailable + prevQty;
                    restored = parseFloat(restored.toFixed(2));

                    $row.find('.available-qty').text(restored);
                    $row.find('.select-return-item').prop('disabled', false);
                    $returnRow.remove();
                }
            }
            recalcAll();
        });
        $('#returnItemsTable').on('input', '.item-note-visible', function () {
    $(this).closest('td').find('.item-note-hidden').val($(this).val());
});
        // Remove button in return table
        $('#returnItemsTable').on('click', '.remove-return-item', function() {
            const $returnRow = $(this).closest('tr');
            const productId = $returnRow.data('product-id');
            const qtyRemoved = num($returnRow.find('.qty-input').val());

            // restore available qty in upper table
            const $topRow = $('#purchasedItemsTable tbody tr[data-product-id="' + productId + '"]');
            if ($topRow.length) {
                const curAvailable = num($topRow.find('.available-qty').text());
                let restored = curAvailable + qtyRemoved;
                restored = parseFloat(restored.toFixed(2));
                
                $topRow.find('.available-qty').text(restored);
                $topRow.find('.select-return-item').prop('checked', false).prop('disabled', false);
            }
            $returnRow.remove();
            recalcAll();
        });

        // When user edits qty/price/discount in lower table
        $('#returnItemsTable').on('input', '.disc-pkr', function() {
            const $row = $(this).closest('tr');
            if ($row.data('syncing')) return;

            const price = num($row.find('.price-input').val());
            const pkr = num($(this).val());

            const pct = pctFromPkr(pkr, price);

            $row.data('syncing', true);
            $row.find('.disc-pct').val(pct ? pct.toFixed(2) : '');
            $row.data('syncing', false);

            recalcReturnRow($row);
            recalcAll();
        });

        $('#returnItemsTable').on('input', '.disc-pct', function() {
            const $row = $(this).closest('tr');
            if ($row.data('syncing')) return;

            const price = num($row.find('.price-input').val());
            const pct = num($(this).val());

            const pkr = pkrFromPct(pct, price);

            $row.data('syncing', true);
            $row.find('.disc-pkr').val(pkr ? pkr.toFixed(2) : '');
            $row.data('syncing', false);

            recalcReturnRow($row);
            recalcAll();
        });

        $('#returnItemsTable').on('input', '.qty-input, .price-input', function() {
            const $row = $(this).closest('tr');
            recalcReturnRow($row);
            recalcAll();
        });
        // recalc summary totals
        function recalcAll() {
            let subtotal = 0;
            $('#returnItemsTable tbody tr').each(function() {
                subtotal += num($(this).find('.row-total').val());
            });

            $('#summarySubtotal').val(subtotal.toFixed(2));

            const oDisc = num($('#overallDiscount').val());
            const xCost = num($('#extraCost').val());
            const net = Math.max(0, (subtotal - oDisc + xCost));
            $('#netAmount').val(net.toFixed(2));
        }

        // handle summary inputs change
        $('#overallDiscount, #extraCost').on('input', function() {
            recalcAll();
        });

        // initialize (if any)
        recalcAll();
    });
</script>
@endsection