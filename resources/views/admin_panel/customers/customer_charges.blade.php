@extends('admin_panel.layout.app')

@section('content')
<style>
    .font-weight-600 { font-weight: 600; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Customer Charges / Expenses</h4>
                    <h6>Manage additional charges added to customer ledger</h6>
                </div>
                <div class="page-btn d-flex justify-content-end col-lg-6">
                    <button class="btn btn-outline-primary mb-2" data-toggle="modal" data-target="#chargeModal" onclick="clearChargeForm()">Add Customer Charge</button>
                </div>
            </div>

            @if (session()->has('success'))
            <div class="alert alert-success"><strong>Success!</strong> {{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <table class="table datanew">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Reason / Description</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($charges as $key => $c)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $c->date }}</td>
                                <td>{{ $c->customer->customer_name ?? 'N/A' }}</td>
                                <td>{{ number_format($c->amount, 2) }}</td>
                                <td>{{ $c->note }}</td>
                                <td class="text-center">
                                    <a href="{{ route('customer.charges.destroy', $c->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this charge?')" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
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

<!-- Charge Modal -->
<div class="modal fade" id="chargeModal">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('customer.charges.store') }}" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">Add Customer Charge</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-600">Customer</label>
                            <select name="customer_id" id="customer_select" class="form-control select2" style="width: 100%;" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->customer_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-600">Amount</label>
                            <input type="number" step="0.01" class="form-control" name="amount" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-600">Date</label>
                            <input type="date" class="form-control" name="date" id="charge_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-600">Reason / Description</label>
                            <input type="text" class="form-control" name="note" placeholder="Enter reason for charge..." required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary px-4">Save Charge</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.datanew').DataTable();
    
    // Initialize Select2
    $('#customer_select').select2({
        dropdownParent: $('#chargeModal')
    });
    
    // Auto-date to current
    var today = new Date().toISOString().split('T')[0];
    $('#charge_date').val(today);
});

function clearChargeForm() {
    try {
        $('#customer_select').val('').trigger('change');
        $('#chargeModal input[name="amount"]').val('');
        
        var today = new Date().toISOString().split('T')[0];
        $('#charge_date').val(today);
        
        $('#chargeModal input[name="note"]').val('');
    } catch (e) {
        console.error('Error clearing form:', e);
    }
}
</script>
@endsection
