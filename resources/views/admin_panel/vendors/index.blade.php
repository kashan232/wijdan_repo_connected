@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0 fw-bold">Vendors</h4>
                    <small class="text-muted">Manage your vendors & balances</small>
                    @php
                        $totalColor = $totalClosingBalance < 0 ? 'text-danger' : 'text-success';
                    @endphp
                    <div class="mt-2 {{ $totalColor }} fw-bold">
                        Total Balance: Rs. {{ number_format($totalClosingBalance, 2) }}
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ url('vendors-ledger') }}" class="btn btn-outline-primary btn-sm">
                        Vendor Ledger
                    </a>
                    <a href="{{ route('vendor.payments') }}" class="btn btn-outline-success btn-sm">
                        Payments
                    </a>
                    <a href="{{ url('vendor/bilties') }}" class="btn btn-outline-info btn-sm">
                        Bilty
                    </a>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#vendorModal" onclick="clearVendor()">
                        + Add Vendor
                    </button>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow-sm border-0 p-2">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="vendorTable" class="table table-hover align-middle mb-0" style="width:100%">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4">S.No</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Opening Balance</th>
                                    <th>Closing Balance</th>
                                    <th>Address</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendors as $key => $v)
                                <tr>
                                    <td class="ps-4 fw-bold text-muted">{{ $key + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px; height:32px; font-weight:bold;">
                                                {{ strtoupper(substr($v->name, 0, 1)) }}
                                            </div>
                                            <span class="fw-semibold text-dark">{{ $v->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $v->phone }}</td>
                                    <td class="text-muted">{{ number_format((float)$v->opening_balance, 2) }}</td>
                                    @php
                                        $balance = (float)($v->ledger->closing_balance ?? 0);
                                        $color = $balance < 0 ? 'text-danger' : 'text-success';
                                    @endphp
                                    <td class="fw-bold {{ $color }}">{{ number_format($balance, 2) }}</td>
                                    <td class="text-muted small">{{ Str::limit($v->address, 30) }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary btn-edit-vendor" 
                                                data-id="{{ $v->id }}" 
                                                data-name="{{ $v->name }}"
                                                data-phone="{{ $v->phone }}"
                                                data-opening="{{ $v->opening_balance }}"
                                                data-address="{{ $v->address }}"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="{{ url('vendor/delete/'.$v->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')" title="Delete">
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

<!-- Modal for Add/Edit Vendor -->
<div class="modal fade" id="vendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="vendorModalLabel">Add/Edit Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('vendor/store') }}" method="POST">
                @csrf
                <input type="hidden" id="vendor_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="name" id="vname" placeholder="Enter vendor name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opening Balance</label>
                        <input type="number" step="any" class="form-control" name="opening_balance" id="opening_balance" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone" id="vphone" placeholder="Enter phone number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" id="vaddress" placeholder="Enter address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#vendorTable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [], // Disable default sorting to respect controller's latest-first order
            language: {
                search: "",
                searchPlaceholder: "Search vendor..."
            }
        });

        // Clear modal fields
        window.clearVendor = function() {
            $('#vendor_id').val('');
            $('#vname').val('');
            $('#opening_balance').val('').prop('readonly', false);
            $('#vphone').val('');
            $('#vaddress').val('');
        };

        // ✅ Use event delegation for dynamically generated buttons
        $(document).on('click', '.btn-edit-vendor', function() {
            var row = $(this).closest('tr');
            var id = $(this).data('id');
            var name = $(this).data('name');
            var phone = $(this).data('phone');
            var opening = $(this).data('opening'); // Get RAW opening balance
            var address = $(this).data('address');

            // Populate modal
            $('#vendor_id').val(id);
            $('#vname').val(name);
            $('#vphone').val(phone);
            $('#opening_balance').val(opening).prop('readonly', false);
            $('#vaddress').val(address);

            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('vendorModal'));
            modal.show();
        });
    });
</script>

@endsection