@extends('admin_panel.layout.app')
@section('content')
@include('admin_panel.period_closing.partials.styles')
<div class="main-content pc-page"><div class="container-fluid px-3">
@include('admin_panel.period_closing.partials.archive_header')
<div class="pc-card">
<div class="pc-card-body">
<div class="table-responsive pc-table-wrap">
<table id="archiveTable" class="table pc-table table-bordered">
<thead><tr>
<th>ID</th><th>Customer</th><th>Reference</th><th>Product</th><th>Qty</th><th>Price</th><th>Discount</th>
<th>Total</th><th>Advance</th><th>Remaining</th><th>Date</th><th>Action</th>
</tr></thead>
<tbody>
@foreach($bookings as $booking)
@php
$totalNet=floatval($booking->total_net??0);
$advance=floatval($booking->advance_payment??$booking->cash??0);
$remaining=$totalNet-$advance;
@endphp
<tr>
<td>{{ $booking->id }}</td>
<td>{{ $booking->customer_relation->customer_name??'Walk-in' }}</td>
<td>{{ $booking->reference }}</td>
<td>@php $names=[]; foreach(explode(',',$booking->product??'') as $pid){ $p=\App\Models\Product::find(trim($pid)); if($p)$names[]=e($p->item_name);} @endphp{!! implode('<br>',$names) !!}</td>
<td>{!! implode('<br>',explode(',',$booking->qty??'')) !!}</td>
<td>@php $p=array_map(fn($x)=>number_format((float)$x,2),explode(',',$booking->per_price??'')); @endphp{!! implode('<br>',$p) !!}</td>
<td>@php $d=array_map(fn($x)=>number_format((float)$x,2),explode(',',$booking->per_discount??'')); @endphp{!! implode('<br>',$d) !!}</td>
<td>{{ number_format($totalNet,2) }}</td>
<td>{{ number_format($advance,2) }}</td>
<td class="{{ $remaining<=0?'text-success':'text-danger' }}">{{ number_format($remaining,2) }}</td>
<td>{{ $booking->booking_date?\Carbon\Carbon::parse($booking->booking_date)->format('d-m-Y'):$booking->created_at->format('d-m-Y') }}</td>
<td><a href="{{ route('booking.receipt',$booking->id) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">Receipt</a></td>
</tr>
@endforeach
</tbody></table></div></div></div></div></div>
@endsection
@section('scripts')<script>$(function(){ $('#archiveTable').DataTable({pageLength:25,order:[[0,'desc']]}); });</script>@endsection
