@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Inward Return for {{ $gatepass->invoice_no }} (GP#: {{ $gatepass->gatepass_no }})</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form action="{{ route('inward-returns.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="inward_gatepass_id" value="{{ $gatepass->id }}">
                            <input type="hidden" name="vendor_id" value="{{ $gatepass->vendor_id }}">
                            <input type="hidden" name="warehouse_id" value="{{ $gatepass->warehouse_id }}">

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Vendor</label>
                                    <input type="text" class="form-control" value="{{ $gatepass->vendor ? $gatepass->vendor->name : '' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Received In</label>
                                    <input type="text" class="form-control text-capitalize" value="{{ $gatepass->receive_type }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Return Date <span class="text-danger">*</span></label>
                                    <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Return Reason</label>
                                    <input type="text" name="return_reason" class="form-control" placeholder="e.g. Defective / Damage / Excess">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Transport Name</label>
                                    <input type="text" name="transport" class="form-control" value="{{ $gatepass->transport_name }}" placeholder="Transport Name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Vehicle No</label>
                                    <input type="text" name="vehicle_no" class="form-control" placeholder="Vehicle No">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Driver Name</label>
                                    <input type="text" name="driver_name" class="form-control" placeholder="Driver Name">
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">Return Items</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle" id="returnItemsTable">
                                    <thead class="text-center" style="background:#add8e6;">
                                        <tr>
                                            <th>#</th>
                                            <th>Item Details</th>
                                            <th>Available Qty</th>
                                            <th style="width: 120px;">Return Qty <span class="text-danger">*</span></th>
                                            <th style="width: 130px;">Price</th>
                                            <th style="width: 180px;">Item Discount</th>
                                            <th style="width: 130px;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        @foreach($items as $i => $item)
                                        <tr class="item-row">
                                            <td>{{ $i + 1 }}</td>
                                            <td style="text-align: left;">
                                                <strong>{{ $item['item_name'] }}</strong>
                                                <div class="small text-muted">Code: {{ $item['item_code'] }} | Unit: {{ $item['unit'] }}</div>
                                                <input type="hidden" name="product_id[]" value="{{ $item['product_id'] }}">
                                                <input type="hidden" name="receive_type[]" value="{{ $item['receive_type'] }}">
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark font-size-12">{{ $item['available'] }}</span>
                                                <div class="small text-muted">(In: {{ $item['qty'] }}, Ret: {{ $item['returned'] }})</div>
                                            </td>
                                            <td>
                                                <input type="number" name="return_qty[]" class="form-control return-qty text-center" 
                                                       min="0" max="{{ $item['available'] }}" step="any" value="0">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="price[]" class="form-control item-price text-end" value="{{ $item['price'] }}">
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" name="discount_value[]" class="form-control item-discount-val text-end" value="{{ $item['discount_value'] }}">
                                                    <select name="discount_type[]" class="form-select item-discount-type" style="max-width: 80px;">
                                                        <option value="pkr" {{ $item['discount_type'] == 'pkr' ? 'selected' : '' }}>PKR</option>
                                                        <option value="percent" {{ $item['discount_type'] == 'percent' ? 'selected' : '' }}>%</option>
                                                    </select>
                                                </div>
                                                <input type="hidden" class="row-discount-amount" value="0">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control row-total text-end fw-bold" value="0.00" readonly>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Financial Summary Section (Tareeqa 2: Manual Discount Box) -->
                            <div class="row justify-content-end mt-3">
                                <div class="col-md-6 col-lg-5">
                                    <div class="card bg-light border">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="fw-bold">Subtotal (Gross):</span>
                                                <span>Rs. <span id="lbl_subtotal">0.00</span></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Item-wise Discount:</span>
                                                <span class="text-danger">- Rs. <span id="lbl_item_discount">0.00</span></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold mb-1">Manual / Bottom Discount: <span class="text-muted small">(As in Add Bill)</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rs.</span>
                                                    <input type="number" step="0.01" min="0" name="discount" id="manual_discount" class="form-control text-end fw-bold" value="0">
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                                <span class="fw-bold">Overall Discount:</span>
                                                <span class="text-danger fw-bold">- Rs. <span id="lbl_overall_discount">0.00</span></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0 text-primary">Net Return Amount:</h5>
                                                <h4 class="mb-0 text-primary fw-bold">Rs. <span id="lbl_net_amount">0.00</span></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label class="form-label">Remarks / Note</label>
                                    <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes for this return..."></textarea>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <a href="{{ route('InwardGatepass.home') }}" class="btn btn-danger px-4">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">Process Return</button>
                            </div>
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
        // Prevent accidental form submission on Enter
        $('form').on('keypress', function(e) {
            if (e.which === 13 || e.keyCode === 13) {
                if ($(e.target).is('button[type="submit"]')) return true;
                e.preventDefault();
                return false;
            }
        });

        function recalcRow($row) {
            const max = parseFloat($row.find('.return-qty').attr('max')) || 0;
            let qty = parseFloat($row.find('.return-qty').val()) || 0;
            if (qty > max) {
                alert('Cannot return more than available quantity (' + max + ')!');
                qty = max;
                $row.find('.return-qty').val(max);
            }
            if (qty < 0) {
                qty = 0;
                $row.find('.return-qty').val(0);
            }

            const price = parseFloat($row.find('.item-price').val()) || 0;
            const discVal = parseFloat($row.find('.item-discount-val').val()) || 0;
            const discType = $row.find('.item-discount-type').val();

            const gross = price * qty;
            let lineDisc = 0;
            if (discType === 'percent') {
                lineDisc = (gross * discVal) / 100;
            } else {
                lineDisc = discVal * qty;
            }

            if (lineDisc > gross) lineDisc = gross;
            const lineTotal = gross - lineDisc;

            $row.find('.row-discount-amount').val(lineDisc.toFixed(2));
            $row.find('.row-total').val(lineTotal.toFixed(2));
        }

        function recalcAll() {
            let totalGross = 0;
            let totalItemDisc = 0;

            $('.item-row').each(function() {
                const $row = $(this);
                const qty = parseFloat($row.find('.return-qty').val()) || 0;
                const price = parseFloat($row.find('.item-price').val()) || 0;
                const lineDisc = parseFloat($row.find('.row-discount-amount').val()) || 0;

                totalGross += (price * qty);
                totalItemDisc += lineDisc;
            });

            const manualDisc = parseFloat($('#manual_discount').val()) || 0;
            const overallDisc = totalItemDisc + manualDisc;
            let netAmount = (totalGross - totalItemDisc) - manualDisc;
            if (netAmount < 0) netAmount = 0;

            $('#lbl_subtotal').text(totalGross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#lbl_item_discount').text(totalItemDisc.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#lbl_overall_discount').text(overallDisc.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#lbl_net_amount').text(netAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }

        // Row change triggers
        $('#returnItemsTable').on('input change', '.return-qty, .item-price, .item-discount-val, .item-discount-type', function() {
            const $row = $(this).closest('tr');
            recalcRow($row);
            recalcAll();
        });

        $('#manual_discount').on('input change', function() {
            recalcAll();
        });

        // Initialize calculation
        $('.item-row').each(function() {
            recalcRow($(this));
        });
        recalcAll();

        // Prevent double submit
        let isSubmitting = false;
        $('form').on('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
            let submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true);
            submitBtn.html('Processing Return...');
        });
    });
</script>
@endsection
