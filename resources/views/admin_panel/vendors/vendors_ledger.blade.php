@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Vendor Ledger</h4>
                    <h6>Manage Vendors</h6>
                </div>
                <div class="page-btn text-end justify-content-end col-lg-6">
                    <a href="{{ url('vendor') }}" class="btn btn-sm btn-outline-danger">Back</a>

                </div>

            </div>

            <div class="card">
                <div class="card-body">
                    @if (session()->has('success'))
                    <div class="alert alert-success"><strong>Success!</strong> {{ session('success') }}</div>
                    @endif

                    <table class="table datanew">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Party Name</th>
                                <th>Party Address</th>
                                <th>Opening Balance</th>
                                <th>Previous Balance</th>
                                <th>Closing Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($VendorLedgers->isEmpty())
                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    document.getElementById("global-loader").style.display = "none";
                                });
                            </script>
                            @endif
                            @forelse($VendorLedgers as $ledger)
                            <tr>
                                <td>{{ $ledger->vendor_id }}</td>
                                <td>{{ $ledger->updated_at->format('Y-m-d') }}</td>
                                <td>{{ $ledger->vendor->name }}</td>
                                <td>{{ $ledger->vendor->address }}</td>

                                <td>{{ $ledger->opening_balance}}</td>
                                <td>{{ $ledger->previous_balance }}</td>
                                <td id="closing_balance_{{ $ledger->id }}">{{ number_format((float) ($ledger->closing_balance ?? 0), 0) }}</td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>



@endsection

@section('scripts')
<script>
    function clearVendor() {
        $('#vendor_id').val('');
        $('#vname').val('');
        $('#vphone').val('');
        $('#vaddress').val('');
    }

    function editVendor(id, name, phone, address) {
        $('#vendor_id').val(id);
        $('#vname').val(name);
        $('#vphone').val(phone);
        $('#vaddress').val(address);
    }
    $('.datanew').DataTable();
</script>
@endsection