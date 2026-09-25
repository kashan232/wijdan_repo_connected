@extends('admin_panel.layout.app')

@section('title', 'Customers Actual Balances')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Customers Actual Balances</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                            <li class="breadcrumb-item active">Actual Balances</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        @php
            $totalCalculated = 0;
            $totalSaved = 0;
            foreach($results as $row) {
                $totalCalculated += $row->calculated_true_balance;
                $totalSaved += $row->system_saved_balance;
            }
            $totalDiff = $totalCalculated - $totalSaved;
        @endphp

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-2">Total Calculated True Balance</h5>
                        <h3 class="mb-0 text-white">Rs. {{ number_format($totalCalculated, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-secondary text-white shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-2">Total System Saved Balance</h5>
                        <h3 class="mb-0 text-white">Rs. {{ number_format($totalSaved, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card {{ round($totalDiff, 2) != 0 ? 'bg-danger' : 'bg-success' }} text-white shadow-sm">
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
                        <h5 class="card-title mb-0">Calculated True Balances vs Saved Balances</h5>
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">Back to Customers</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="actualBalancesTable" class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer Name</th>
                                        <th>Initial Opening</th>
                                        <th>Total Sales</th>
                                        <th>Sale Cash/Card</th>
                                        <th>Total Returns</th>
                                        <th>Payments</th>
                                        <th>Charges (+)</th>
                                        <th>Deductions (-)</th>
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
                                            <td>{{ $row->customer_id }}</td>
                                            <td class="fw-bold">{{ $row->customer_name }}</td>
                                            <td>{{ number_format($row->initial_opening, 2) }}</td>
                                            <td class="text-primary">{{ number_format($row->total_sales, 2) }}</td>
                                            <td class="text-warning">{{ number_format($row->total_sale_payments, 2) }}</td>
                                            <td class="text-danger">{{ number_format($row->total_returns, 2) }}</td>
                                            <td class="text-success">{{ number_format($row->total_payments, 2) }}</td>
                                            <td class="text-info">{{ number_format($row->total_plus, 2) }}</td>
                                            <td class="text-warning">{{ number_format($row->total_minus, 2) }}</td>
                                            <td class="text-primary fw-bold">{{ number_format($row->calculated_true_balance, 2) }}</td>
                                            <td class="text-secondary fw-bold">{{ number_format($row->system_saved_balance, 2) }}</td>
                                            <td class="{{ $diffClass }}">
                                                {{ number_format($diff, 2) }}
                                                @if(round($diff, 2) == 0)
                                                    <span class="badge bg-success ms-2">OK</span>
                                                @else
                                                    <span class="badge bg-danger ms-2">Mismatch</span>
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
    });
</script>
@endsection
@endsection
