@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.period_closing.partials.styles')

<div class="main-content pc-page">
<div class="container-fluid px-3">
    @include('admin_panel.period_closing.partials.archive_header')
    <div class="pc-card">
        <div class="pc-card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="pc-form-label">From Date</label>
                    <input type="date" id="filterFrom" class="form-control pc-form-control">
                </div>
                <div class="col-md-3">
                    <label class="pc-form-label">To Date</label>
                    <input type="date" id="filterTo" class="form-control pc-form-control">
                </div>
                <div class="col-md-3">
                    <label class="pc-form-label">Cashier / User</label>
                    <select id="filterUser" class="form-control pc-form-control">
                        <option value="">All Users</option>
                        @foreach(\App\Models\User::all() as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button id="btnFilter" class="btn btn-primary pc-btn-primary flex-grow-1">Filter</button>
                    <button id="btnReset" class="btn btn-outline-secondary">Reset</button>
                </div>
            </div>

            <div class="pc-alert-soft info mb-4">
                <i class="fas fa-info-circle me-2"></i>Full sales listing — read-only archive view.
            </div>

            <div class="table-responsive pc-table-wrap">
                <table id="archiveSalesTable" class="table pc-table align-middle nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>S.No</th><th>User</th><th>Invoice</th><th>Customer</th><th>Reference</th>
                            <th>Barcode</th><th>Products</th><th>Qty</th><th>Price</th><th>Discount</th>
                            <th>Total</th><th>Extra Disc.</th><th>Net</th><th>Date</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<style>
    div.dataTables_wrapper div.dataTables_processing { border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
    .dataTables_filter input { width:280px!important; border-radius:8px!important; border:1.5px solid #e2e8f0!important; padding:6px 12px!important; }
    .btn-mini { padding:2px 8px!important; font-size:11px!important; font-weight:600!important; border-radius:6px!important; }
</style>
<script>
$(function(){
    var table = $('#archiveSalesTable').DataTable({
        processing:true, serverSide:true,
        ajax:{ url:"{{ route('period.archive.sales.fetch', $period) }}", data:function(d){
            d.from_date=$('#filterFrom').val(); d.to_date=$('#filterTo').val(); d.filter_user=$('#filterUser').val();
        }},
        columns:[
            {data:0,orderable:false,searchable:false},{data:1},{data:2},{data:3},{data:4},
            {data:5,orderable:false,searchable:false},{data:6,orderable:false,searchable:false},
            {data:7,orderable:false,searchable:false},{data:8,orderable:false,searchable:false},
            {data:9,orderable:false,searchable:false},{data:10,orderable:false,searchable:false},
            {data:11,orderable:false,searchable:false},{data:12},{data:13},{data:14},
            {data:15,orderable:false,searchable:false}
        ],
        pageLength:25, order:[[2,'desc']],
        language:{ searchPlaceholder:'Search...' }
    });
    $('#btnFilter').on('click',()=>table.draw());
    $('#btnReset').on('click',function(){ $('#filterFrom,#filterTo').val(''); $('#filterUser').val(''); table.draw(); });
});
</script>
@endsection
