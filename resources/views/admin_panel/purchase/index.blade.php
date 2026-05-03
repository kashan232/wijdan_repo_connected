@extends('admin_panel.layout.app')
<style>
    /* Highlight returned purchase rows in light red */
    tr.returned-row {
        background-color: #f8d7da !important;
        /* light red background */
        color: #721c24 !important;
        /* dark red text */
        font-weight: 600;
        /* make text bold for visibility */
    }

    /* Ensure DataTables doesn't override the background color */
    table.dataTable tbody tr.returned-row>* {
        background-color: #f8d7da !important;
    }
</style>


@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <h4 class="mb-0">List Of Purchases</h4>
                            <div>
                                <a href="{{ route('add_purchase') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add New Purchase
                                </a>
                                <a href="{{ url('/purchase/return') }}" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-arrow-repeat"></i> View Purchase Return
                                </a>
                            </div>
                        </div>
                        <div class="mt-3">
                            <form action="{{ route('Purchase.home') }}" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Start Date</label>
                                    <input type="date" name="start_date" value="{{ $start_date ?? '' }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">End Date</label>
                                    <input type="date" name="end_date" value="{{ $end_date ?? '' }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-filter"></i> Filter
                                    </button>
                                    <a href="{{ route('Purchase.home') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                        @if(request('start_date') && request('end_date'))
                        <div class="alert alert-info mt-3">
                            Showing purchases from <strong>{{ request('start_date') }}</strong> to <strong>{{ request('end_date') }}</strong>.
                        </div>
                        @endif
                        <div class="card-body">
                            <div class="col-lg-12">
                                @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show"
                                    role="alert">
                                    <strong>Success!</strong> {{ session('success') }}
                                    <button type="button" class="btn-close" data-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                                @endif
                                <div class="table-responsive mt-5 mb-5">
                                    <table id="purchase-table" class="table">
                                        <thead class="text-center">
                                            <tr>
                                                <th>ID</th>
                                                <th>Invoice No</th>
                                                <th>Company Inv</th>
                                                <th>Branch</th>
                                                <th>Type</th>
                                                <th>Warehouse</th>
                                                <th>Vendor</th>
                                                <th>Products</th>
                                                <th>Qty</th>
                                                <th>Note</th>
                                                <th>Subtotal</th>
                                                <th>Discount</th>
                                                <th>Extra Cost</th>
                                                <th>Net Amount</th>
                                                <th>Paid</th>
                                                <th>Due</th>
                                                <th>Purchase Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-center">
                                            @foreach ($Purchase as $purchase)
                                            <tr @if($purchase->return) class="returned-row" @endif>

                                                <td>{{ $purchase->id }}</td>
                                                <td>
                                                    @if($purchase instanceof \App\Models\InwardGatepass)
                                                    {{ $purchase->invoice_no }}
                                                    <span class="badge bg-success ms-1">Inward</span>
                                                    @else
                                                    {{ $purchase->invoice_no }}
                                                    @endif
                                                </td>
                                                <td>{{ $purchase->company_invoice_no ?? '-' }}</td>

                                                <td>{{ $purchase->branch->name ?? 'N/A' }}</td>
                                                <td class="fw-semibold text-capitalize">
                                                    @if($purchase instanceof \App\Models\InwardGatepass)
                                                    {{ $purchase->receive_type }}
                                                    @else
                                                    {{ $purchase->purchase_to }}
                                                    @endif
                                                </td>


                                                {{-- ✅ WAREHOUSE --}}
                                                <td>
                                                    @if($purchase instanceof \App\Models\InwardGatepass)
                                                    {{ $purchase->warehouse->warehouse_name ?? 'Shop' }}
                                                    @else
                                                    {{ $purchase->purchase_to === 'warehouse'
        ? ($purchase->warehouse->warehouse_name ?? '-')
        : 'Shop' }}
                                                    @endif
                                                </td>

                                                <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                                                <td class="text-start align-top">
                                                    @php
                                                    // prepare arrays of product names and qtys
                                                    $prodNames = [];
                                                    $qtys = [];
                                                    @endphp

                                                    @foreach($purchase->items as $item)
                                                    @php
                                                    $prodNames[] = $item->product->item_name ?? 'N/A';
                                                    $qtys[] = $item->qty ?? 0;
                                                    @endphp
                                                    @endforeach

                                                    {{-- Print product names stacked --}}
                                                    @if(count($prodNames))
                                                    @foreach($prodNames as $pName)
                                                    <div style="line-height:1.4;">{{ $pName }}</div>
                                                    @endforeach
                                                    @else
                                                    <div>-</div>
                                                    @endif
                                                </td>

                                                {{-- Qty column: show corresponding quantities line-by-line --}}
                                                <td class="text-center align-top">
                                                    @if(count($qtys))
                                                    @foreach($qtys as $q)
                                                    <div style="line-height:1.4;"><strong>{{ $q }}</strong></div>
                                                    @endforeach
                                                    @else
                                                    <div>0</div>
                                                    @endif
                                                </td>
                                                <td>{{ $purchase->note }}</td>
                                                <td>{{ number_format($purchase->subtotal ?? 0, 2) }}</td>
                                                <td>{{ number_format($purchase->discount ?? 0, 2) }}</td>
                                                <td>{{ number_format($purchase->extra_cost ?? 0, 2) }}</td>
                                                <td><b>{{ number_format($purchase->net_amount ?? 0, 2) }}</b></td>
                                                <td>{{ number_format($purchase->paid_amount ?? 0, 2) }}</td>
                                                <td>{{ number_format($purchase->due_amount ?? 0, 2) }}</td>

                                                <td>
                                                    {{ \Carbon\Carbon::parse(
                                                        $purchase instanceof \App\Models\InwardGatepass
                                                            ? $purchase->gatepass_date
                                                            : $purchase->purchase_date
                                                    )->format('d-m-Y') }}
                                                </td>

                                                <td>
                                                    <div class="btn-group" role="group" aria-label="Actions">
                                                        @if($purchase instanceof \App\Models\InwardGatepass)
                                                            <a href="{{ route('InwardGatepass.inv', $purchase->id) }}"
                                                                class="btn btn-sm btn-info text-white">Invoice</a>
                                                        @else
                                                            <a href="{{ route('purchase.edit', $purchase->id) }}" 
                                                                class="btn btn-sm btn-primary">Edit</a>
                                                            <a href="{{ route('purchase.return.show', $purchase->id) }}"
                                                                class="btn btn-sm btn-danger">Return</a>
                                                            <a href="{{ route('purchase.invoice', $purchase->id) }}"
                                                                class="btn btn-sm btn-info text-white">Invoice</a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                </div>
                            </div>

                            <div class="modal fade" id="purchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="purchaseModalLabel">Add Purchase</h5>
                                        </div>
                                        <div class="modal-body">
                                            <form class="myform" action="{{ route('store.Purchase') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="edit_id" id="id" />
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Invoice No</label>
                                                        <input type="text" name="invoice_no" class="form-control" id="invoice_no" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Supplier</label>
                                                        <input type="text" name="supplier" class="form-control" id="supplier" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Purchase Date</label>
                                                        <input type="date" name="purchase_date" class="form-control" id="purchase_date" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Warehouse</label>
                                                        <input type="text" name="warehouse_id" class="form-control" id="warehouse_id" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Item Category</label>
                                                        <input type="text" name="item_category" class="form-control" id="item_category">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Item Name</label>
                                                        <input type="text" name="item_name" class="form-control" id="item_name">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Quantity</label>
                                                        <input type="number" name="quantity" class="form-control" id="quantity">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <input type="submit" class="btn btn-primary save-btn" value="Save">
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
    $(document).on('submit', '.myform', function(e) {
        e.preventDefault();
        var formdata = new FormData(this);
        url = $(this).attr('action');
        method = $(this).attr('method');
        $(this).find(':submit').attr('disabled', true);
        myAjax(url, formdata, method);
    });

    $(document).on('click', '.edit-btn', function() {
        var tr = $(this).closest("tr");
        $('#id').val(tr.find(".id").text());
        $('#invoice_no').val(tr.find(".invoice_no").text());
        $('#supplier').val(tr.find(".supplier").text());
        $('#purchase_date').val(tr.find(".purchase_date").text());
        $('#warehouse_id').val(tr.find(".warehouse_id").text());
        $("#purchaseModal").modal("show");
    });

    $(document).ready(function() {
        $('#purchase-table').DataTable({
            "pageLength": 10,
            "lengthMenu": [5, 10, 25, 50, 100],
            "ordering": false, // 👈 VERY IMPORTANT
            "language": {
                "search": "Search Purchase:",
                "lengthMenu": "Show _MENU_ entries"
            }
        });
    });
</script>
@endsection
