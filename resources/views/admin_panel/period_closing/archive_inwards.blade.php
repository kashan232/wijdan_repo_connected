@extends('admin_panel.layout.app')
@section('content')
@include('admin_panel.period_closing.partials.styles')
<div class="main-content pc-page"><div class="container-fluid px-3">
@include('admin_panel.period_closing.partials.archive_header')
<div class="pc-card">
<div class="pc-card-body">
<div class="table-responsive pc-table-wrap">
<table id="archiveTable" class="table pc-table table-bordered text-center">
<thead>
                        <tr>
                            <th>ID</th>
                            <th>Inv</th>
                            <th>Builty#</th>
                            <th>Company Inv#</th>
                            <th>Branch</th>
                            <th>ReceivedIn</th>
                            <th>Warehouse</th>
                            <th>Vendor</th>
                            <th>Items</th>
                            <th>Qty</th>
                            <th>Date</th>
                            <th>Note</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gatepasses as $gp)
                        <tr>
                            <td>{{ $gp->id }}</td>
                            <td>{{ $gp->invoice_no }}</td>
                            <td>{{ $gp->gatepass_no }}</td>
                            <td>{{ $gp->company_invoice_no ?? 'Bill Not Generate' }}</td>
                            <td>{{ $gp->branch->name ?? 'N/A' }}</td>
                            <td>{{ $gp->receive_type }}</td>
                            <td>{{ $gp->warehouse->warehouse_name ?? 'N/A' }}</td>
                            <td>{{ $gp->vendor->name ?? 'N/A' }}</td>
                            <td class="text-start">
                                @forelse($gp->items as $item)
                                    <div class="small"><strong>{{ $item->product->item_name ?? 'N/A' }}</strong></div>
                                @empty
                                    <span class="badge bg-danger">Not Added</span>
                                @endforelse
                            </td>
                            <td>
                                @foreach($gp->items as $item)
                                    <div><strong class="text-muted">({{ $item->qty }})</strong></div>
                                @endforeach
                            </td>
                            <td>{{ \Carbon\Carbon::parse($gp->gatepass_date)->format('d-m-Y') }}</td>
                            <td>{{ $gp->remarks ?? 'N/A' }}</td>
                            <td>
                                @if($gp->status == 'pending') <span class="badge bg-warning">Pending</span>
                                @elseif($gp->status == 'linked') <span class="badge bg-success">Linked</span>
                                @elseif($gp->status == 'cancelled') <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                            <td>
                                @if($gp->status == 'linked')
                                    <a href="{{ route('InwardGatepass.inv', $gp->id) }}" target="_blank" class="btn btn-sm btn-success">Invoice</a>
                                @endif
                                <a href="{{ route('InwardGatepass.show', $gp->id) }}" target="_blank" class="btn btn-sm btn-info text-white">View</a>
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
<script>$(function(){ $('#archiveTable').DataTable({ pageLength: 25, order: [[0,'desc']] }); });</script>
@endsection
