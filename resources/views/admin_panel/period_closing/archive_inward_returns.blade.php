@extends('admin_panel.layout.app')
@section('content')
@include('admin_panel.period_closing.partials.styles')
<div class="main-content pc-page"><div class="container-fluid px-3">
@include('admin_panel.period_closing.partials.archive_header')
<div class="pc-card">
<div class="pc-card-body">
<div class="table-responsive pc-table-wrap">
<table id="archiveTable" class="table pc-table table-bordered table-striped">
<thead>
                    <tr>
                        <th>#</th>
                        <th>Return Invoice</th>
                        <th>Gatepass Ref</th>
                        <th>Date</th>
                        <th>Vendor</th>
                        <th>Received In</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inwardReturns as $key => $return)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $return->return_invoice }}</td>
                        <td>{{ $return->inwardGatepass?->invoice_no ?? 'N/A' }}</td>
                        <td>{{ \Carbon\Carbon::parse($return->return_date)->format('d-m-Y') }}</td>
                        <td>{{ $return->vendor?->name ?? 'N/A' }}</td>
                        <td class="text-capitalize">{{ $return->inwardGatepass?->receive_type ?? 'N/A' }}</td>
                        <td>
                            <a href="{{ route('inward-returns.show', $return->id) }}" target="_blank" class="btn btn-sm btn-info text-white">View</a>
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
