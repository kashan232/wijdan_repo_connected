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

        <!-- Date Filter Card -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('vendor.actual_balances') }}" class="row g-3 align-items-end">
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label fw-bold mb-1">From Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label fw-bold mb-1">To Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-4 col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fa fa-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('vendor.actual_balances') }}" class="btn btn-secondary flex-fill">
                            <i class="fa fa-refresh me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $totalCalculated = 0;
            $totalSaved = 0;
            $totalPayments = 0;
            foreach($results as $row) {
                $totalCalculated += $row->calculated_true_balance;
                $totalSaved += $row->system_saved_balance;
                $totalPayments += $row->total_payments;
            }
            $totalDiff = $totalCalculated - $totalSaved;
        @endphp

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                <div class="card bg-primary text-white shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-2">Total Calculated True Balance</h5>
                        <h3 class="mb-0 text-white">Rs. {{ number_format($totalCalculated, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                <div class="card text-white shadow-sm h-100" style="background-color: #d97706 !important;">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-2">Total Payments</h5>
                        <h3 class="mb-0 text-white">Rs. {{ number_format($totalPayments, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                <div class="card bg-secondary text-white shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-2">Total System Saved Balance</h5>
                        <h3 class="mb-0 text-white">Rs. {{ number_format($totalSaved, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                <div class="card {{ round($totalDiff, 2) != 0 ? 'bg-danger' : 'bg-success' }} text-white shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-2">Total Difference</h5>
                        <h3 class="mb-0 text-white">Rs. {{ number_format($totalDiff, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            Calculated True Balances vs Saved Balances
                            @if(request('start_date') || request('end_date'))
                                <span class="badge bg-info ms-2">
                                    Date: {{ request('start_date') ? date('d-m-Y', strtotime(request('start_date'))) : 'Start' }} to {{ request('end_date') ? date('d-m-Y', strtotime(request('end_date'))) : 'Today' }}
                                </span>
                            @endif
                        </h5>
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
                                            <td class="text-warning fw-bold">{{ number_format($row->total_payments, 2) }}</td>
                                            <td class="text-primary fw-bold">{{ number_format($row->calculated_true_balance, 2) }}</td>
                                            <td class="text-secondary fw-bold">{{ number_format($row->system_saved_balance, 2) }}</td>
                                            <td class="{{ $diffClass }}">{{ number_format($diff, 2) }}</td>
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

        // Fix Auto button disabled
    });
</script>
@endsection
@endsection
