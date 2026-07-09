@extends('admin_panel.layout.app')
@section('content')
@include('admin_panel.period_closing.partials.styles')
<style>tr.returned-row{background:#fef2f2!important;color:#991b1b!important;font-weight:600;}</style>
<div class="main-content pc-page"><div class="container-fluid px-3">
@include('admin_panel.period_closing.partials.archive_header')
<div class="pc-card">
<div class="pc-card-body">
<div class="table-responsive pc-table-wrap">
<table id="archivePurchaseTable" class="table pc-table table-bordered text-center">
<thead>
                        <tr>
                            <th>ID</th>
                            <th>Invoice No</th>
                            <th>Company Inv</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Warehouse</th>
                            <th>Vendor</th>
                            <th>Items (Qty)</th>
                            <th>Note</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Extra Cost</th>
                            <th>Net Amount</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $purchase)
                        <tr @if(!($purchase instanceof \App\Models\InwardGatepass) && $purchase->return) class="returned-row" @endif>
                            <td>{{ $purchase->id }}</td>
                            <td>
                                {{ $purchase->invoice_no }}
                                @if($purchase instanceof \App\Models\InwardGatepass)
                                    <span class="badge bg-success ms-1">Inward</span>
                                @endif
                            </td>
                            <td>{{ $purchase->company_invoice_no ?? '-' }}</td>
                            <td>{{ $purchase->branch->name ?? 'N/A' }}</td>
                            <td class="text-capitalize">
                                {{ $purchase instanceof \App\Models\InwardGatepass ? $purchase->receive_type : $purchase->purchase_to }}
                            </td>
                            <td>
                                @if($purchase instanceof \App\Models\InwardGatepass)
                                    {{ $purchase->warehouse->warehouse_name ?? 'Shop' }}
                                @else
                                    {{ $purchase->purchase_to === 'warehouse' ? ($purchase->warehouse->warehouse_name ?? '-') : 'Shop' }}
                                @endif
                            </td>
                            <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                            <td class="text-start align-top">
                                @if($purchase->items && count($purchase->items))
                                    @foreach($purchase->items as $item)
                                        <div class="small">{{ $item->product->item_name ?? 'N/A' }} <strong>({{ $item->qty }})</strong></div>
                                    @endforeach
                                @else - @endif
                            </td>
                            <td>{{ $purchase->note ?? $purchase->remarks ?? '-' }}</td>
                            <td>{{ number_format($purchase->subtotal ?? 0, 2) }}</td>
                            <td>{{ number_format($purchase->discount ?? 0, 2) }}</td>
                            <td>{{ number_format($purchase->extra_cost ?? 0, 2) }}</td>
                            <td><b>{{ number_format($purchase->net_amount ?? 0, 2) }}</b></td>
                            <td>{{ number_format($purchase->paid_amount ?? 0, 2) }}</td>
                            <td>{{ number_format($purchase->due_amount ?? 0, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($purchase instanceof \App\Models\InwardGatepass ? $purchase->gatepass_date : $purchase->purchase_date)->format('d-m-Y') }}</td>
                            <td>
                                @if($purchase instanceof \App\Models\InwardGatepass)
                                    <a href="{{ route('InwardGatepass.inv', $purchase->id) }}" target="_blank" class="btn btn-sm btn-info text-white">Invoice</a>
                                @else
                                    <a href="{{ route('purchase.invoice', $purchase->id) }}" target="_blank" class="btn btn-sm btn-info text-white">Invoice</a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div></div>
@endsection
@section('scripts')
<script>$(function(){ $('#archivePurchaseTable').DataTable({ pageLength: 25, order: [[0,'desc']] }); });</script>
@endsection
