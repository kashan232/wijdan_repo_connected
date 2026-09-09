@extends('admin_panel.layout.app')

@section('title', 'Vendors Actual Balances')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Vendors Actual Balances</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('vendors') }}">Vendors</a></li>
                            <li class="breadcrumb-item active">Actual Balances</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Calculated True Balances vs Saved Balances</h5>
                        <a href="{{ route('vendors') }}" class="btn btn-secondary btn-sm">Back to Vendors</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="actualBalancesTable" class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Vendor Name</th>
                                        <th>Initial Opening</th>
                                        <th>Total Purchases</th>
                                        <th>Total Inwards</th>
                                        <th>Total Returns</th>
                                        <th>Total Payments</th>
                                        <th class="text-primary">Calculated True Balance</th>
                                        <th class="text-secondary">System Saved Balance</th>
                                        <th>Difference</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $row)
                                        @php
                                            $diff = $row->calculated_true_balance - $row->system_saved_balance;
                                            $diffClass = round($diff, 2) != 0 ? 'text-danger fw-bold' : 'text-success';
                                        @endphp
                                        <tr>
                                            <td>{{ $row->vendor_id }}</td>
                                            <td class="fw-bold">{{ $row->vendor_name }}</td>
                                            <td>{{ number_format($row->initial_opening, 2) }}</td>
                                            <td>{{ number_format($row->total_purchases, 2) }}</td>
                                            <td>{{ number_format($row->total_inwards, 2) }}</td>
                                            <td class="text-danger">{{ number_format($row->total_returns, 2) }}</td>
                                            <td class="text-warning">{{ number_format($row->total_payments, 2) }}</td>
                                            <td class="text-primary fw-bold">{{ number_format($row->calculated_true_balance, 2) }}</td>
                                            <td class="text-secondary fw-bold">{{ number_format($row->system_saved_balance, 2) }}</td>
                                            <td class="{{ $diffClass }}">{{ number_format($diff, 2) }}</td>
                                            <td>
                                                @if(round($diff, 2) != 0)
                                                    <button class="btn btn-sm btn-danger fix-btn" data-id="{{ $row->vendor_id }}" data-diff="{{ $diff }}">Fix Auto</button>
                                                @else
                                                    <span class="badge bg-success">OK</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

@section('scripts')
<script>
    $(document).ready(function() {
        $('#actualBalancesTable').DataTable({
            "order": [],
            "pageLength": 25
        });

        $('#actualBalancesTable tbody').on('click', '.fix-btn', function() {
            var btn = $(this);
            var vendor_id = btn.data('id');
            var diff = btn.data('diff');

            if (confirm('Are you sure you want to fix the calculated balance to match the system saved balance by adjusting the opening balance by ' + diff + '?')) {
                btn.prop('disabled', true).text('Fixing...');
                $.ajax({
                    url: '{{ route('vendor.fix_balance') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        vendor_id: vendor_id,
                        difference: diff
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Something went wrong!');
                            btn.prop('disabled', false).text('Fix Auto');
                        }
                    },
                    error: function() {
                        alert('Error processing request.');
                        btn.prop('disabled', false).text('Fix Auto');
                    }
                });
            }
        });
    });
</script>
@endsection
@endsection
