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
                    <h4>Vendor Bilties</h4>
                    <h6>Manage Transport & Delivery Records</h6>
                </div>
                <div class="page-btn d-flex justify-content-end col-lg-6">
                    <button class="btn btn-outline-primary mb-2" data-toggle="modal" data-target="#biltyModal" onclick="clearBiltyForm()">Add Bilty</button>
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
                                <th>Vendor</th>
                                <th>Purchase</th>
                                <th>Bilty No</th>
                                <th>Amount</th>
                                <th>Vehicle No</th>
                                <th>Transporter</th>
                                <th>Delivery Date</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bilties as $key => $b)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $b->vendor->name ?? 'N/A' }}</td>
                                <td>{{ $b->purchase->invoice_no ?? 'N/A' }}</td>
                                <td>{{ $b->bilty_no }}</td>
                                <td>{{ number_format($b->amount, 2) }}</td>
                                <td>{{ $b->vehicle_no }}</td>
                                <td>{{ $b->transporter_name }}</td>
                                <td>{{ $b->delivery_date }}</td>
                                <td>{{ $b->note }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bilty Modal -->
<div class="modal fade" id="biltyModal">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('vendor.bilties.store') }}" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">Add Vendor Bilty</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-600">Vendor</label>
                            <select name="vendor_id" id="vendor_select" class="form-control select2" style="width: 100%;" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-600">Related Purchase (optional)</label>
                            <select name="purchase_id" class="form-control">
                                <option value="">None</option>
                                @foreach($purchases as $purchase)
                                    <option value="{{ $purchase->id }}">{{ $purchase->invoice_no }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-600">Bilty No</label>
                            <input class="form-control" name="bilty_no" placeholder="Enter Bilty No">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-600">Bilty Amount</label>
                            <input type="number" step="0.01" class="form-control" name="amount" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-600">Vehicle No</label>
                            <input class="form-control" name="vehicle_no" placeholder="Enter Vehicle No">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-600">Transporter Name</label>
                            <input class="form-control" name="transporter_name" placeholder="Enter Transporter Name">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-600">Delivery Date</label>
                            <input type="date" class="form-control" name="delivery_date" id="bilty_date">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="font-weight-600">Note</label>
                            <textarea class="form-control" name="note" rows="1" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary px-4">Save Bilty Record</button>
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
    $('#vendor_select').select2({
        dropdownParent: $('#biltyModal')
    });
    
    // Auto-date to current
    var today = new Date().toISOString().split('T')[0];
    $('#bilty_date').val(today);
});

function clearBiltyForm() {
    try {
        $('#vendor_select').val('').trigger('change');
        $('#biltyModal select[name="purchase_id"]').val('');
        $('#biltyModal input[name="bilty_no"]').val('');
        $('#biltyModal input[name="amount"]').val('');
        $('#biltyModal input[name="vehicle_no"]').val('');
        $('#biltyModal input[name="transporter_name"]').val('');
        
        var today = new Date().toISOString().split('T')[0];
        $('#bilty_date').val(today);
        
        $('#biltyModal textarea[name="note"]').val('');
    } catch (e) {
        console.error('Error clearing form:', e);
    }
}
</script>
@endsection

