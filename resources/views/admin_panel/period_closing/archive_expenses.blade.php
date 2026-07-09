@extends('admin_panel.layout.app')
@section('content')
@include('admin_panel.period_closing.partials.styles')
<div class="main-content pc-page"><div class="container-fluid px-3">
@include('admin_panel.period_closing.partials.archive_header')
<div class="pc-card">
<div class="pc-card-body">

<div class="row mb-4 g-3">
    <div class="col-md-6">
        <div class="pc-snapshot-card snap-expense">
            <div class="snap-label">Total Expense (Archive)</div>
            <div class="snap-total" style="font-size:1.35rem;color:#dc2626;">Rs. {{ number_format($totalExpense, 2) }}</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="pc-snapshot-card">
            <div class="snap-label">Total Vouchers</div>
            <div class="snap-count">{{ $vouchers->count() }}</div>
        </div>
    </div>
</div>

<div class="table-responsive pc-table-wrap">
<table id="archiveTable" class="table pc-table table-striped align-middle">
<thead>
<tr><th>ID</th><th>Voucher No</th><th>Account Head</th><th>Account</th><th style="min-width:260px;">Remarks</th><th>Total</th><th>Date</th><th>Action</th></tr>
</thead>
<tbody>
@foreach($vouchers as $voucher)
<tr>
<td>{{ $voucher->id }}</td>
<td class="fw-semibold">{{ $voucher->evid }}</td>
<td>{{ $voucher->type_name }}</td>
<td>{{ $voucher->party_name }}</td>
<td>
@php $remarks=is_array($voucher->remarks)?$voucher->remarks:(json_decode($voucher->remarks,true)??[]); $amounts=is_array($voucher->amount)?$voucher->amount:(json_decode($voucher->amount,true)??[]); @endphp
@foreach($remarks as $i=>$remark)
<div class="d-flex justify-content-between align-items-center mb-1 p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
<span class="small fw-medium">{{ $remark }}</span>
<span class="badge bg-primary">Rs {{ number_format($amounts[$i]??0,2) }}</span>
</div>
@endforeach
</td>
<td class="text-end fw-bold text-success">Rs {{ number_format($voucher->total_amount,2) }}</td>
<td>{{ \Carbon\Carbon::parse($voucher->date??$voucher->entry_date??$voucher->created_at)->format('d-m-Y') }}</td>
<td><a href="{{ route('expenseVoucher.print',$voucher->id) }}" target="_blank" class="btn btn-sm btn-danger rounded-pill px-3"><i class="fas fa-print me-1"></i>Print</a></td>
</tr>
@endforeach
</tbody></table>
</div>
</div></div></div></div>
@endsection
@section('scripts')<script>$(function(){ $('#archiveTable').DataTable({pageLength:25,order:[[1,'desc']]}); });</script>@endsection
