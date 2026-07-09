@extends('admin_panel.layout.app')
@section('content')
@include('admin_panel.period_closing.partials.styles')
<div class="main-content pc-page"><div class="container-fluid px-3">
@include('admin_panel.period_closing.partials.archive_header')
<div class="pc-card">
<div class="pc-card-body">
<div class="table-responsive pc-table-wrap">
<table id="archiveSaleReturnsTable" class="table pc-table table-bordered w-100">
<thead class="text-center"><tr>
<th>#</th><th>Inv</th><th>Items</th><th>Customer</th><th>Items</th><th>Net</th><th>Note</th><th>Date</th><th>Status</th><th>Action</th>
</tr></thead><tbody></tbody></table>
</div></div></div></div></div>
@endsection
@section('scripts')
<script>
$(function(){
$('#archiveSaleReturnsTable').DataTable({
processing:true,serverSide:true,
ajax:"{{ route('period.archive.sales-returns.fetch', $period) }}",
columns:[
{data:0,orderable:false,searchable:false},{data:1},{data:2,orderable:false,searchable:false},{data:3},
{data:4,orderable:false,searchable:false},{data:5,className:'text-end'},{data:6,orderable:false},
{data:7},{data:8,orderable:false,searchable:false},{data:9,orderable:false,searchable:false}
], pageLength:25, order:[[7,'desc']]
});
});
</script>
@endsection
