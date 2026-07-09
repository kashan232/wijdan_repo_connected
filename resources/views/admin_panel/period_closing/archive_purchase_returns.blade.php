@extends('admin_panel.layout.app')
@section('content')
@include('admin_panel.period_closing.partials.styles')
<div class="main-content pc-page"><div class="container-fluid px-3">
@include('admin_panel.period_closing.partials.archive_header')
<div class="pc-card">
<div class="pc-card-body">
<div class="table-responsive pc-table-wrap">
<table id="archiveTable" class="table pc-table table-bordered">
<thead>
                    <tr>
                        <th>ID</th>
                        <th>Purchase Invoice #</th>
                        <th>Invoice #</th>
                        <th>Vendor</th>
                        <th>Warehouse</th>
                        <th>Return Date</th>
                        <th>Products</th>
                        <th>Qty</th>
                        <th>Bill Amount</th>
                        <th>Item Discount</th>
                        <th>Extra Discount</th>
                        <th>Net Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returns as $return)
                    <tr>
                        <td>{{ $return->id }}</td>
                        <td>{{ $return->purchase->invoice_no ?? 'N/A' }}</td>
                        <td>{{ $return->return_invoice }}</td>
                        <td>{{ $return->vendor->name ?? 'N/A' }}</td>
                        <td>{{ $return->warehouse->warehouse_name ?? 'N/A' }}</td>
                        <td>{{ \Carbon\Carbon::parse($return->return_date)->format('Y-m-d') }}</td>
                        <td class="text-start">
                            @foreach($return->items as $item)
                                <div>{{ $item->product->item_name ?? 'N/A' }}</div>
                            @endforeach
                        </td>
                        <td class="text-center">
                            @foreach($return->items as $item)
                                <div><strong>{{ $item->qty + 0 }}</strong></div>
                            @endforeach
                        </td>
                        <td>{{ number_format($return->bill_amount, 2) }}</td>
                        <td>{{ number_format($return->item_discount, 2) }}</td>
                        <td>{{ number_format($return->extra_discount, 2) }}</td>
                        <td><strong>{{ number_format($return->net_amount, 2) }}</strong></td>
                        <td>
                            <a href="{{ route('purchasereturn.invoice', $return->id) }}" target="_blank" class="btn btn-sm btn-danger text-white">Invoice</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div></div></div>
@endsection
@section('scripts')
<script>$(function(){ $('#archiveTable').DataTable({ pageLength: 25, order: [[0,'desc']] }); });</script>
@endsection
