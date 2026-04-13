@extends('admin_panel.layout.app')
@section('content')
<style>
    .btn-sm i.fa-toggle-on {
        color: green;
        font-size: 20px;
    }

    .btn-sm i.fa-toggle-off {
        color: gray;
        font-size: 20px;
    }

    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
    }

    .table td {
        font-size: 13px;
    }

    .btn-group .btn {
        padding: 4px 8px;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0 fw-bold">Customers</h4>
                    <small class="text-muted">Manage your customers & balances</small>
                    <div class="mt-2 text-primary fw-bold">
                        Total Receivables: Rs. {{ number_format($totalClosingBalance ?? 0, 2) }}
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('customers.inactive') }}" class="btn btn-outline-secondary btn-sm">
                        Inactive
                    </a>
                    <a href="{{ route('customers.ledger') }}" class="btn btn-outline-primary btn-sm">
                        Ledger
                    </a>
                    <a href="{{ route('customer.payments') }}" class="btn btn-outline-success btn-sm">
                        Payments
                    </a>
                    <a href="{{ route('customer.charges') }}" class="btn btn-outline-warning btn-sm">
                        Charges
                    </a>
                    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                        + Add Customer
                    </a>
                </div>
            </div>
            @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow-sm border-0 p-2">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="customerTable" class="table table-hover align-middle mb-0" style="width:100%">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4">S.No</th>
                                    <th>Date</th>
                                    <th>ID</th>
                                    <th>Customer Name</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Mobile</th>
                                    <th class="text-end">Closing Balance</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customers as $customer)
                                <tr>
                                    <td class="ps-4 fw-bold text-muted">{{ $loop->iteration }}</td>
                                    <td class="text-muted">{{ $customer->created_at->format('d M, Y') }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $customer->customer_id }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px; height:32px; font-weight:bold;">
                                                {{ strtoupper(substr($customer->customer_name, 0, 1)) }}
                                            </div>
                                            <span class="fw-semibold text-dark">{{ $customer->customer_name }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $customer->customer_type }}</span></td>
                                    <td><span class="badge {{ $customer->customer_category == 'Wholesaler' ? 'bg-info' : 'bg-warning' }} text-dark">{{ $customer->customer_category ?? '-' }}</span></td>
                                    <td>{{ $customer->mobile ?? '-' }}</td>
                                    <td class="text-end fw-bold 
    {{ $customer->closing_balance > 0 ? 'text-success' : 'text-danger' }}">
                                        Rs. {{ number_format($customer->closing_balance, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if($customer->status === 'active')
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">Active</span>
                                        @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('customers.toggleStatus', $customer->id) }}" class="btn btn-sm btn-outline-warning" title="Toggle Status">
                                                <i class="fas {{ $customer->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            </a>
                                            <a href="{{ route('customers.destroy', $customer->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
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
@endsection

@section('scripts')

<script>
    $(document).ready(function() {
        $('#customerTable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [], // Disable default ordering to keep latest first as per controller
            language: {
                search: "",
                searchPlaceholder: "Search customer..."
            }
        });
    });
</script>
@endsection
