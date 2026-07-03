@extends('admin_panel.layout.app')
@section('content')

<div class="container-fluid">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Sale Returns</h5>
            <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm  text-center">
                Back
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="saleReturnsTable" class="table table-bordered table-hover align-middle mb-0 w-100">
                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Inv</th>
                            <th>Items</th>
                            <th>Customer</th>
                            <th>Total Items</th>
                            <th>Total Net</th>
                            <th>Return Note</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be populated by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@endsection

@section('scripts')
@if(session('print_invoice_url'))
<script>
    $(document).ready(function() {
        window.open("{{ session('print_invoice_url') }}", "_blank", "width=800,height=600");
    });
</script>
@endif

<script>
    $(document).ready(function() {
        $('#saleReturnsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sale.returns.index') }}"
            },
            columns: [
                { data: 0, orderable: false, searchable: false, className: 'text-center' }, // S.No
                { data: 1 }, // Inv
                { data: 2, orderable: false, searchable: false }, // Items
                { data: 3 }, // Customer
                { data: 4, orderable: false, searchable: false, className: 'text-center' }, // Total Items
                { data: 5, className: 'text-end' }, // Total Net
                { data: 6, orderable: false }, // Return Note
                { data: 7, className: 'text-center' }, // Date
                { data: 8, orderable: false, searchable: false, className: 'text-center' }, // Status
                { data: 9, orderable: false, searchable: false, className: 'text-center' }  // Action
            ],
            responsive: false,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            order: [
                [7, 'desc'] // Order by Date desc by default
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search Inv/Customer/Note...",
                processing: '<div class="spinner-border text-primary mb-2" role="status" style="width: 3rem; height: 3rem;"></div><div class="fw-bold">Processing...</div>'
            }
        });
    });
</script>
@endsection
