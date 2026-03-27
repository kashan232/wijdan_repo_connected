@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Edit Bill For Good received note #{{ $gatepass->id }}</h3>
                    <a href="{{ route('InwardGatepass.home') }}" class="btn btn-secondary">Back</a>
                </div>

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

                            <form action="{{ route('update.bill', $gatepass->id) }}" method="POST">
                                @csrf

                                <!-- Gatepass Info -->
                                <div class="row g-3 mb-4">
                                    <!-- Vendor -->
                                    <div class="col-md-3">
                                        <label>Vendor</label>
                                        <input type="text" class="form-control" value="{{ $gatepass->vendor->name ?? '-' }}" readonly>
                                        <input type="hidden" name="vendor_id" value="{{ $gatepass->vendor_id }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Received In Type</label>
                                        <select name="received_in" class="form-select" id="receivedIn">
                                            <option value="warehouse" {{ old('received_in', $gatepass->receive_type ?? 'warehouse') == 'warehouse' ? 'selected' : '' }}>
                                                Warehouse
                                            </option>
                                            <option value="shop" {{ old('received_in', $gatepass->receive_type ?? '') == 'shop' ? 'selected' : '' }}>
                                                Shop
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Warehouse -->
                                    <div class="col-md-3" id="warehouseBox">
                                        <label>Warehouse</label>
                                        <input type="text" class="form-control"
                                            value="{{ $gatepass->warehouse->warehouse_name ?? '-' }}" readonly>
                                        <input type="hidden" name="warehouse_id"
                                            value="{{ old('warehouse_id', $gatepass->warehouse_id) }}">
                                    </div>
                                    <!-- Purchase Date -->
                                    <div class="col-md-3">
                                        <label>Purchase Date</label>
                                        <input type="date" name="purchase_date" class="form-control" value="{{ old('gatepass_date', $gatepass->gatepass_date) }}">
                                    </div>

                                    <div class="col-md-3">
                                        <label>Company Inv # <span class="text-danger">*</span></label>
                                        <input type="text" name="company_invoice_no" class="form-control"
                                            placeholder="Enter Company Invoice No"
                                            value="{{ old('company_invoice_no', $gatepass->company_invoice_no) }}" required>
                                    </div>
                                </div>

                                <!-- Product Table -->
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered mb-0">
                                        <thead>
                                            <tr class="text-center">
                                                <th>Product</th>
                                                <th>Note</th>
                                                <th>Item Code</th>
                                                <th>Qty</th>
                                                <th>Price <span class="text-danger">*</span></th>
                                                <th>Discount</th>
                                                <th>Type</th>
                                                <th>Discount Amount</th>
                                                <th>Unit</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="gatepassItems">
                                            @foreach($gatepass->items as $item)
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control" value="{{ $item->product->item_name ?? '-' }}" readonly>
                                                    <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                                                    <input type="hidden" name="note[{{ $item->id }}]" value="{{ $item->note }}">
                                                </td>
                                                <td>
                                                    <input type="text" name="note" class="form-control" value="{{ $item->note ?? '' }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" name="item_code[]" class="form-control" value="{{ $item->product->item_code ?? '-' }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" name="qty[]" class="form-control qty" min="1" value="{{ $item->qty }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" name="price[{{ $item->id }}]" class="form-control price" value="{{ old('price.' . $item->id, $item->product->wholesale_price) }}" min="0" step="0.01" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="item_discount[{{ $item->id }}]" class="form-control item_discount" value="{{ old('item_discount.' . $item->id, $item->discount_value ?? 0) }}" step="0.01">

                                                </td>
                                                <td>
                                                    <select name="discount_type[{{ $item->id }}]" class="form-select discount_type">
                                                        <option value="pkr" {{ old('discount_type.' . $item->id, $item->discount_type ?? 'pkr') == 'pkr' ? 'selected' : '' }}>PKR</option>
                                                        <option value="percent" {{ old('discount_type.' . $item->id, $item->discount_type ?? 'pkr') == 'percent' ? 'selected' : '' }}>%</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control discount_amount" readonly value="0.00">
                                                </td>

                                                <td>
                                                    <input type="text" name="unit[{{ $item->id }}]" class="form-control unit" value="{{ $item->product->unit_id ?? '-' }}" readonly>
                                                </td>

                                                <td>
                                                    <input type="text" name="total[{{ $item->id }}]" value="{{ $item->total }}" class="form-control row-total" readonly>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row g-3 mt-3">
                                    <div class="col-md-2">
                                        <label>Subtotal</label>
                                        <input type="text" id="subtotal" name="subtotal" class="form-control" value="0" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Discount</label>
                                        <input type="number" step="0.01" id="discount" name="discount" class="form-control" value="{{ old('discount', $gatepass->discount ?? 0) }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Overall Discount</label>
                                        <input type="text" id="overall_discount" name="overall_discount" class="form-control" value="0" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Extra Cost</label>
                                        <input type="number" step="0.01" id="extra_cost" name="extra_cost" class="form-control" value="{{ old('extra_cost', $gatepass->extra_cost ?? 0) }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Net Amount</label>
                                        <input type="text" id="net_amount" name="net_amount" class="form-control" value="0" readonly>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 mt-4">Update Bill</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // ===== PREVENT ENTER KEY FROM SUBMITTING FORM =====
        $('form').on('keypress', function(e) {
            // Check if Enter key was pressed (keyCode 13)
            if (e.which === 13 || e.keyCode === 13) {
                // Only allow submitting if user is on the submit button
                if ($(e.target).is('button[type="submit"]')) {
                    return true; // Allow submission
                }
                // Prevent form submission on Enter key
                e.preventDefault();
                return false;
            }
        });

        // ===== ALTERNATIVE: Prevent Enter on specific input fields =====
        $('input, select').on('keydown', function(e) {
            if (e.which === 13 || e.keyCode === 13) {
                e.preventDefault();
                return false;
            }
        });

        function toggleWarehouse() {
            let type = $('#receivedIn').val();
            if (type === 'shop') {
                $('#warehouseBox').hide();
            } else {
                $('#warehouseBox').show();
            }
        }

        $('#receivedIn').on('change', toggleWarehouse);
        toggleWarehouse(); // init
    });

    $(document).ready(function() {

        // --- Row Calculation ---
        function recalcRow($row) {
            const qty = parseFloat($row.find('.qty').val()) || 0;
            const price = parseFloat($row.find('.price').val()) || 0;
            const discountValue = parseFloat($row.find('.item_discount').val()) || 0;
            const type = $row.find('.discount_type').val();
            let discountAmount = 0;

            if (type === 'pkr') {
                discountAmount = discountValue * qty;
            } else {
                discountAmount = ((price * qty) * discountValue) / 100;
            }

            $row.find('.discount_amount').val(discountAmount.toFixed(2));

            const total = (price * qty) - discountAmount;
            $row.find('.row-total').val(total.toFixed(2));
        }

        // --- Summary Calculation ---
        function recalcSummary() {
            let subtotal = 0;
            let itemDiscounts = 0;

            $('tbody tr').each(function() {
                const rowTotal = parseFloat($(this).find('.row-total').val()) || 0;
                const itemDiscount = parseFloat($(this).find('.discount_amount').val()) || 0;

                subtotal += rowTotal; // ✅ already net of inline discount
                itemDiscounts += itemDiscount; // ✅ for display only
            });

            $('#subtotal').val(subtotal.toFixed(2));

            const manualDiscount = parseFloat($('#discount').val()) || 0;
            const extraCost = parseFloat($('#extra_cost').val()) || 0;

            // ✅ sirf display ke liye
            const overallDiscount = itemDiscounts + manualDiscount;

            // ✅ IMPORTANT: net se sirf manual discount minus hoga
            const netAmount = subtotal - manualDiscount + extraCost;

            $('#overall_discount').val(overallDiscount.toFixed(2));
            $('#net_amount').val(netAmount.toFixed(2));
        }

        // --- Events ---
        $(document).on('input change', '.qty, .price, .item_discount, .discount_type', function() {
            const $row = $(this).closest('tr');
            recalcRow($row);
            recalcSummary();
        });

        $('#discount, #extra_cost').on('input', recalcSummary);

        // --- Initialize ---
        $('tbody tr').each(function() {
            recalcRow($(this));
        });
        recalcSummary();
    });
</script>


@endsection