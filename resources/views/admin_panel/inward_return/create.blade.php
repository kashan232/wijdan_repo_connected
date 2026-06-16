@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Inward Return for {{ $gatepass->invoice_no }}</h4>
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
                                    <label>Vendor</label>
                                    <input type="text" class="form-control" value="{{ $gatepass->vendor ? $gatepass->vendor->name : '' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Received In</label>
                                    <input type="text" class="form-control text-capitalize" value="{{ $gatepass->receive_type }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Return Date <span class="text-danger">*</span></label>
                                    <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">Items</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="text-center" style="background:#add8e6;">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Item Code</th>
                                            <th>Unit</th>
                                            <th>Total Inward Qty</th>
                                            <th>Already Returned</th>
                                            <th>Available to Return</th>
                                            <th style="width: 150px;">Return Qty <span class="text-danger">*</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        @foreach($items as $i => $item)
                                        <tr>
                                            <td style="text-align: left;">
                                                {{ $item['item_name'] }}
                                                <input type="hidden" name="product_id[]" value="{{ $item['product_id'] }}">
                                                <input type="hidden" name="receive_type[]" value="{{ $item['receive_type'] }}">
                                            </td>
                                            <td>{{ $item['item_code'] }}</td>
                                            <td>{{ $item['unit'] }}</td>
                                            <td>{{ $item['qty'] }}</td>
                                            <td>{{ $item['returned'] }}</td>
                                            <td>{{ $item['available'] }}</td>
                                            <td>
                                                <input type="number" name="return_qty[]" class="form-control return-qty text-center" 
                                                       min="0" max="{{ $item['available'] }}" step="any" value="0">
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 text-end">
                                <a href="{{ route('InwardGatepass.home') }}" class="btn btn-danger">Cancel</a>
                                <button type="submit" class="btn btn-primary">Process Return</button>
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
        $('.return-qty').on('input', function() {
            var max = parseFloat($(this).attr('max'));
            var val = parseFloat($(this).val());
            if (val > max) {
                alert('Cannot return more than available quantity!');
                $(this).val(max);
            }
            if (val < 0) {
                $(this).val(0);
            }
        });
    });
</script>
@endsection
