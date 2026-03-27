@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <!-- Header -->
            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Edit Vendor Payment</h4>
                    <h6>Update Payment Details</h6>
                </div>
                <div class="page-btn d-flex justify-content-end col-lg-6">
                    <a href="{{ route('vendor.payments') }}" class="btn btn-secondary">Back to Payments</a>
                </div>
            </div>

            <!-- Alert -->
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Edit Form -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('vendor.payments.update', $payment->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Payment No #</label>
                                <input type="text" class="form-control" value="{{ $payment->payment_no }}" readonly style="background:#f8f9fa;">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Vendor</label>
                                <select name="vendor_id" class="form-control select2">
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ $payment->vendor_id == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Stock (Original Closing Balance)</label>
                                <input type="text" id="vendor_stock" class="form-control" readonly style="background:#f8f9fa;" value="{{ $original_balance }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', $payment->payment_date) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Type</label>
                                <select name="adjustment_type" class="form-control" required>
                                    <option value="minus" {{ old('adjustment_type', $adjustment_type) == 'minus' ? 'selected' : '' }}>Minus (Payment)</option>
                                    <option value="plus" {{ old('adjustment_type', $adjustment_type) == 'plus' ? 'selected' : '' }}>Plus (Return / Advance)</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Amount <span class="text-danger">*</span></label>
                                <input type="number"
                                    step="0.01"
                                    name="amount"
                                    id="amount"
                                    class="form-control"
                                    value="{{ old('amount', $payment->amount) }}"
                                    required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Amount in Words</label>
                                <input type="text" id="amount_in_words"
                                    class="form-control"
                                    readonly
                                    style="background:#f8f9fa; font-weight:600;">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Payment Method</label>
                                <input type="text" name="payment_method" class="form-control" placeholder="e.g. Cash, Bank" value="{{ old('payment_method', $payment->payment_method) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Note</label>
                                <textarea name="note" class="form-control" placeholder="Optional note">{{ old('note', $payment->note) }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary w-100">Update Payment</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
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

        // Initialize amount in words on page load
        let initialAmount = $('#amount').val();
        if (initialAmount) {
            $('#amount_in_words').val(numberToWords(initialAmount));
        }

        // Update amount in words on input
        $(document).on('input', '#amount', function() {
            let val = $(this).val();
            $('#amount_in_words').val(numberToWords(val));
        });
    });
</script>
@endsection
