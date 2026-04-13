@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Customer Payments & Recoveries</h4>
                    <h6>Manage Customer Receivables</h6>
                </div>
                <div class="page-btn d-flex justify-content-end col-lg-6">
                    <button class="btn btn-outline-primary mb-2" data-toggle="modal" data-target="#paymentModal" onclick="clearPaymentForm()">Add Payment</button>
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
                                <th>S.No</th>
                                <th>Received No #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Note</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $key => $p)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $p->received_no ?? 'N/A' }}</td>
                                <td>{{ $p->customer->customer_name ?? 'N/A' }}</td>
                                <td>{{ number_format($p->amount, 2) }}</td>
                                <td>{{ $p->payment_date }}</td>
                                <td>{{ $p->payment_method }}</td>
                                <td>{{ $p->note }}</td>
                                <td>
                                    <a href="{{ route('customer.payments.edit', $p->id) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <a href="{{ route('customer.payments.receipt', $p->id) }}" class="btn btn-sm btn-success">
                                        Receipt
                                    </a>

                                    <form action="{{ route('customer.payments.destroy', $p->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this payment?')">
                                            Delete
                                        </button>
                                    </form>
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
        <form action="{{ route('customer.payments.store') }}" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer Payment</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Customer</label>
                        <select name="customer_id" class="form-control" required onchange="fetchCustomerBalance(this.value)">
                            <option value="">Select Customer</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Outstanding Balance</label>
                        <input type="text" id="customer_balance" class="form-control" readonly>
                    </div>

                    <!-- Adjustment Type -->
                    <div class="mb-2">
                        <label>Adjustment Type</label>
                        <select name="adjustment_type" class="form-control" required>
                            <option value="minus">- Minus (Payment Received)</option>
                            <option value="plus">+ Plus (Outstanding Increased)</option>
                        </select>
                    </div>
                    <div class="mb-2"><label>Payment Date</label><input type="date" name="payment_date" class="form-control" required></div>
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
                    <div class="mb-2"><label>Payment Method</label><input type="text" name="payment_method" class="form-control" placeholder="e.g. Cash, Bank"></div>
                    <div class="mb-2"><label>Note</label><textarea name="note" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')

<script>
    function clearPaymentForm() {
        $('#paymentModal select[name="customer_id"]').val('');
        $('#paymentModal input[name="payment_date"]').val('');
        $('#paymentModal input[name="amount"]').val('');
        $('#paymentModal input[name="payment_method"]').val('');
        $('#paymentModal textarea[name="note"]').val('');
        $('#customer_balance').val('');
    }



    $(document).ready(function() {
        $('.datanew').DataTable();
    });
</script>
<script>
    function fetchCustomerBalance(customerId) {
        $.ajax({
            url: '/customer/ledger/' + customerId,
            method: 'GET',
            success: function(response) {
                if (response.closing_balance !== undefined) {
                    $('#customer_balance').val(parseFloat(response.closing_balance).toFixed(2));
                } else {
                    $('#customer_balance').val('0.00');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                $('#customer_balance').val('Error');
            }
        });
    }

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
</script>
@endsection
