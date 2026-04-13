@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <!-- Header -->
            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Vendor Payments</h4>
                    <h6>Manage Vendor Payment Records</h6>
                </div>
                <div class="page-btn d-flex justify-content-end col-lg-6">
                    <button class="btn btn-outline-primary mb-2" data-toggle="modal" data-target="#paymentModal" onclick="clearPaymentForm()">Add Payment</button>
                </div>
            </div>

            <!-- Alert -->
            @if (session()->has('success'))
            <div class="alert alert-success"><strong>Success!</strong> {{ session('success') }}</div>
            @endif

            <!-- Table -->
            <div class="card">
                <div class="card-body">
                    <table class="table datanew" id="datatable">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Payment No #</th>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Note</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $key => $pay)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $pay->payment_no ?? 'N/A' }}</td>
                                <td>{{ $pay->vendor->name ?? 'N/A' }}</td>
                                <td>{{ number_format($pay->amount, 2) }}</td>
                                <td>{{ $pay->payment_date }}</td>
                                <td>{{ $pay->payment_method }}</td>
                                <td>{{ $pay->note }}</td>
                                <td>
                                    <a href="{{ route('vendor.payments.edit', $pay->id) }}"
                                        class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <a href="{{ route('vendor.payment.receipt', $pay->id) }}"
                                        target="_blank"
                                        class="btn btn-sm btn-primary">
                                        Print
                                    </a>
                                    <a href="{{ route('vendor.payment.delete', $pay->id) }}"
                                        onclick="return confirm('Are you sure you want to delete this payment? The ledger will be updated.')"
                                        class="btn btn-sm btn-danger">
                                        Delete
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

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal">
    <div class="modal-dialog">
        <form action="{{ route('vendor.payments.store') }}" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Vendor Payment</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Vendor</label>
                        <select name="vendor_id" id="vendor_id" class="form-control" required>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Stock (Closing Balance)</label>
                        <input type="text" id="vendor_stock" class="form-control" readonly>
                    </div>

                    <div class="mb-2">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Type</label>
                        <select name="adjustment_type" class="form-control" required>
                            <option value="minus">Minus (Payment)</option>
                            <option value="plus">Plus (Return / Advance)</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Amount</label>
                        <input type="number"
                            step="0.01"
                            name="amount"
                            id="amount"
                            class="form-control"
                            required>
                    </div>
                    <div class="mb-2">
                        <label>Amount in Words</label>
                        <input type="text" id="amount_in_words"
                            class="form-control"
                            readonly
                            style="background:#f8f9fa; font-weight:600;">
                    </div>
                    <div class="mb-2">
                        <label>Payment Method</label>
                        <input type="text" name="payment_method" class="form-control" placeholder="e.g. Cash, Bank">
                    </div>
                    <div class="mb-2">
                        <label>Note</label>
                        <textarea name="note" class="form-control" placeholder="Optional note"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Submit</button>
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
        $('#vendor_id').on('change', function() {
            var vendorId = $(this).val();
            if (vendorId) {
                $.ajax({
                    url: '/get-vendor-balance/' + vendorId,
                    type: 'GET',
                    success: function(data) {
                        $('#vendor_stock').val(data.closing_balance);
                    },
                    error: function() {
                        $('#vendor_stock').val('0');
                    }
                });
            } else {
                $('#vendor_stock').val('');
            }
        });

        function numberToWords(num) {
            if (!num || num === 0) return 'Zero Rupees Only';

            const a = [
                '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven',
                'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen',
                'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
            ];
            const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty',
                'Sixty', 'Seventy', 'Eighty', 'Ninety'
            ];

            function inWords(n) {
                if (n < 20) return a[n];
                if (n < 100) return b[Math.floor(n / 10)] + (n % 10 ? ' ' + a[n % 10] : '');
                if (n < 1000)
                    return a[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + inWords(n % 100) : '');
                if (n < 100000)
                    return inWords(Math.floor(n / 1000)) + ' Thousand' + (n % 1000 ? ' ' + inWords(n % 1000) : '');
                if (n < 10000000)
                    return inWords(Math.floor(n / 100000)) + ' Lakh' + (n % 100000 ? ' ' + inWords(n % 100000) : '');
                return inWords(Math.floor(n / 10000000)) + ' Crore' + (n % 10000000 ? ' ' + inWords(n % 10000000) : '');
            }

            let parts = num.toString().split('.');
            let rupees = parseInt(parts[0]);
            let paisa = parts[1] ? parseInt(parts[1].substring(0, 2)) : 0;

            let words = inWords(rupees) + ' Rupees';

            if (paisa > 0) {
                words += ' and ' + inWords(paisa) + ' Paisa';
            }

            return words + ' Only';
        }



        $(document).on('input', 'input[name="amount"]', function() {
            let val = $(this).val();
            $('#amount_in_words').val(numberToWords(val));
        });
        $('#paymentModal').on('shown.bs.modal', function() {
            $('#amount').val('');
            $('#amount_in_words').val('');
        });
    });
</script>
@endsection
