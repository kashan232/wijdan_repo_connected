@extends('admin_panel.layout.app')
@section('content')
@php
    $fmt = function($val) {
        $val = (float)$val;
        return ($val == (int)$val) ? number_format($val, 0) : number_format($val, 2);
    };
@endphp

<div class="container-fluid">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h5 class="mb-0">SALES</h5>
                @if(auth()->user()->hasRole('Cashier'))
                <div class="badge bg-success fs-6 p-2 shadow-sm">
                    Opening Cash: {{ $fmt($openingBalance ?? 0) }}
                </div>
                <div class="badge bg-primary fs-6 p-2 shadow-sm">
                    Today's Sale: {{ $fmt($todaySales ?? 0) }}
                </div>
                <div class="badge bg-dark fs-6 p-2 shadow-sm border border-light">
                    Net Cash: {{ $fmt($netCash ?? 0) }}
                </div>
                <div class="badge bg-info fs-6 p-2 shadow-sm border border-light d-none" id="filteredSaleCard">
                    Sale: <span id="filteredSaleAmount">0</span>
                </div>
                @endif
            </div>
            <div>
                <span class="fw-bold text-dark"><a href="{{ route('sale.add') }}" class="btn btn-primary">Add sale</a></span>
                <span class="fw-bold text-dark"><a href="{{ url('bookings') }}" class="btn btn-primary">All Booking</a></span>
                <span class="fw-bold text-dark"><a href="{{ url('sale-returns') }}" class="btn btn-primary">Sale Return</a></span>
                <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm  text-center">
                    Back
                </a>
            </div>
        </div>

        <div class="card-body">
            
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" id="filterFrom" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" id="filterTo" class="form-control">
                </div>
                @if(auth()->user()->hasRole('Admin'))
                <div class="col-md-3">
                    <label class="form-label">Cashier / User</label>
                    <select id="filterUser" class="form-control">
                        <option value="">All Users</option>
                        @foreach(\App\Models\User::all() as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3 d-flex align-items-end">
                    <button id="btnFilter" class="btn btn-primary w-100">Filter</button>
                    <button id="btnReset" class="btn btn-secondary ms-2">Reset</button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="productTable" class="table table-striped table-bordered align-middle nowrap" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>S.No</th>
                            <th>User</th>
                            <th>Invoice No</th>
                            <th>Customer</th>
                            <th>Reference</th>
                            <th>Barcode</th>
                            <th>Products</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Total Price</th>
                            <th>Total Amount</th>
                            <th>Date | Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via AJAX -->
                    </tbody>
                </table>
            </div>


        </div>

    </div>
</div>

@endsection

@section('scripts')
<style>
    /* Custom Processing Indicator Style */
    div.dataTables_wrapper div.dataTables_processing {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 200px;
        margin-left: -100px; /* Half of width */
        margin-top: -60px;   /* Half of height approx */
        text-align: center;
        padding: 20px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #e0e0e0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        border-radius: 12px;
        z-index: 1000;
        font-size: 1.1rem;
        color: #333;
    }
    
    /* Make search input wider */
    .dataTables_filter input {
        width: 300px !important; /* Increase width */
        font-size: 1.1em;
        padding: 5px 10px;
    }
</style>
<script>
    $(document).ready(function() {
        var table = $('#productTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('Purchase.home') }}".replace('Purchase', 'sale'), // Route is sale.index but URI is /sale/index usually. 
            // Better to use route('sale.index') if named 'sale.index'. 
            // Let's check routes.
            // Route::get('/sale', [SaleController::class, 'index'])->name('sale.home'); ?? 
            // In web.php I saw: Route::get('/sale/index', ...)? No.
            // Let's check routes file again if needed.
            // Found: Route::get('sale', [SaleController::class, 'index'])->name('sale.index'); (Assumed based on pattern)
            // Actually, in the code snippet provided earlier:
            // Route::get('/sale', [SaleController::class, 'index'])->name('sale.index'); matches Controller.
            // But wait, the previous code had `return view('admin_panel.sale.index', compact('sales'));`
            // Let's check routes in web.php again to be sure of the name.
            ajax: {
                url: "{{ url('sale') }}",
                data: function (d) {
                    d.from_date = $('#filterFrom').val();
                    d.to_date = $('#filterTo').val();
                    d.filter_user = $('#filterUser').val();
                }
            },
            columns: [
                { data: 0, orderable: false, searchable: false }, // S.No
                { data: 1 }, // User
                { data: 2 }, // Invoice
                { data: 3 }, // Customer
                { data: 4 }, // Reference
                { data: 5, orderable: false, searchable: false }, // Barcode
                { data: 6, orderable: false, searchable: false }, // Products
                { data: 7, orderable: false, searchable: false }, // Qty
                { data: 8, orderable: false, searchable: false }, // Price
                { data: 9, orderable: false, searchable: false }, // Discount
                { data: 10, orderable: false, searchable: false }, // Total Row
                { data: 11 }, // Bill Amount
                { data: 12 }, // Date
                { data: 13 }, // Status
                { data: 14, orderable: false, searchable: false } // Action
            ],
            responsive: true,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            order: [
                [2, 'desc'] // Order by Invoice No by default
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search Invoice/Customer...",
                processing: '<div class="spinner-border text-primary mb-2" role="status" style="width: 3rem; height: 3rem;"></div><div class="fw-bold">Processing...</div>'
            },
            drawCallback: function(settings) {
                var api = this.api();
                var json = api.ajax.json();
                
                if (json && json.totalFilteredSale !== undefined) {
                    var total = parseFloat(json.totalFilteredSale);
                    var formattedTotal = (total % 1 === 0) ? total.toLocaleString(undefined, {minimumFractionDigits: 0}) : total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    
                    var fromDate = $('#filterFrom').val();
                    var toDate = $('#filterTo').val();
                    
                    if(fromDate || toDate) {
                        $('#filteredSaleAmount').text(formattedTotal);
                        $('#filteredSaleCard').removeClass('d-none');
                    } else {
                        $('#filteredSaleCard').addClass('d-none');
                    }
                }
            }
        });

        $('#btnFilter').on('click', function() {
            table.draw();
        });

        $('#btnReset').on('click', function() {
            $('#filterFrom').val('');
            $('#filterTo').val('');
            $('#filterUser').val('');
            table.draw();
        });
    });
</script>


@endsection