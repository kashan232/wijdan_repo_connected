@extends('admin_panel.layout.app')
@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="main-content">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="fw-bold mt-2">Payment Voucher</h2>
        </div>
        <div class="card shadow">
            <div class="card-body">
                @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form action="{{ route('Payment.vochers.store') }}" method="POST">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label fw-bold">PVID</label>
                            <input type="text" class="form-control" name="pvid" value="{{ $nextPVID }}" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Payment Date</label>
                            <input type="date" name="receipt_date" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Entry Date</label>
                            <input type="date" name="entry_date" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Account Head</label>
                            <select name="row_account_head[]" class="form-select rowAccountHead">
                                <option value="">Select</option>
                                @foreach($AccountHeads as $head)
                                <option value="{{ $head->id }}">{{ $head->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Account</label>
                            <select name="row_account_id[]" class="form-select rowAccountSub">
                                <option value="">Select Account</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Remarks</label>
                            <input type="text" name="remarks" class="form-control" id="remarks">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle" id="voucherTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Narration</th>
                                    <th>Reference#</th>
                                    <th>Type</th>
                                    <th>Party</th>
                                    <th>Code</th>
                                    <th>Discount</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="narration_id[]" class="form-select narrationSelect">
                                            <option value="">Select</option>
                                            @foreach($narrations as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input name="reference_no[]" type="text" class="form-control"></td>
                                    <td>
                                        <select name="vendor_type" class="form-select">
                                            <option disabled selected>Select</option>
                                            {{-- Account Head Options --}}
                                            @foreach($AccountHeads as $head)
                                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                                            @endforeach
                                            <option value="vendor">Vendor</option>
                                            <option value="customer">Customer</option>
                                            <option value="walkin">Walkin Customer</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="vendor_id" class="form-select">
                                            <option disabled selected>Select</option>
                                        </select>
                                    </td>

                                    <td>
                                        <input type="text" name="tel" id="tel" class="form-control" readonly>
                                    </td>
                                    <td><input name="discount_value[]" type="number" class="form-control discountValue" value="0"></td>
                                    <td><input name="amount[]" type="text" class="form-control text-end amount"></td>
                                    <td><button class="btn btn-danger btn-sm removeRow"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-end">Total:</th>
                                    <th><input type="text" name="total_amount" class="form-control text-end fw-bold" id="totalAmount" readonly></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    {{-- Footer Buttons --}}
                    <div class="d-flex  mt-4">
                        <div>
                            <button class="btn btn-primary">Save</button>
                            <button class="btn btn-outline-secondary">Exit</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Type change -> fetch parties
    $(document).on('change', 'select[name="vendor_type"]', function() {
        let type = $(this).val();
        let $vendorSelect = $('select[name="vendor_id"]');

        // ✅ Tel aur remarks reset kar do
        $('input[name="tel"]').val('');
        $('#remarks').val('');

        $vendorSelect.empty().append('<option disabled selected>Loading...</option>');

        if (type === 'vendor' || type === 'customer' || type === 'walkin') {
            // Vendors/Customers/Walkin
            $.get('{{ route("party.list") }}?type=' + type, function(data) {
                $vendorSelect.empty().append('<option disabled selected>Select</option>');
                data.forEach(function(item) {
                    $vendorSelect.append('<option value="' + item.id + '">' + item.text + '</option>');
                });
            });
        } else if (type) {
            // Agar Account Head select hua hai to uska ID bhejo
            let headId = type;
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(data) {
                $vendorSelect.empty().append('<option disabled selected>Select</option>');
                data.forEach(function(acc) {
                    $vendorSelect.append(
                        '<option value="' + acc.id + '" data-code="' + acc.account_code + '">' +
                        acc.title + ' (' + acc.account_code + ')' +
                        '</option>'
                    );
                });
            });
        }
    });

    $(document).on('change', 'select[name="vendor_id"]', function() {
        let $selected = $(this).find(':selected');
        let id = $selected.val();
        let type = $('select[name="vendor_type"]').val().toLowerCase();

        if (!id) return;

        // Agar Head select hai (subhead ke option me account_code hota hai)
        let accountCode = $selected.data('code');
        if (accountCode) {
            $('input[name="tel"]').val(accountCode);
            $('#remarks').val('');
            return;
        }

        // Otherwise Vendor/Customer case
        $.get('{{ route("customers.show", ["id" => "__ID__"]) }}'.replace('__ID__', id) + '?type=' + type, function(d) {
            $('input[name="tel"]').val(d.mobile || ''); // ✅ ab value set hogi
            $('#remarks').val(d.remarks || '');
        });
    });

    // ✅ Row Calculation
    function calculateRow(row, manual = false) {
        let discount = parseFloat(row.find('.discountValue').val()) || 0;
        let baseAmount = parseFloat(row.find('.amount').val()) || 0;

        if (row.find('.baseAmount').length === 0) {
            row.append('<input type="hidden" class="baseAmount" value="0">');
        }

        row.find('.baseAmount').val(baseAmount);
        let finalAmount = baseAmount - discount;
        if (finalAmount < 0) finalAmount = 0;

        if (!manual) {
            row.find('.amount').val(finalAmount.toFixed(2));
        }
    }



    function calculateTotals() {
        let total = 0;
        $('#voucherTable tbody tr').each(function() {
            total += parseFloat($(this).find('.amount').val()) || 0;
        });
        $('#totalAmount').val(total.toFixed(2));
    }


    // Auto calc
    $(document).on('input', '.discountValue, .amount', function() {
        let row = $(this).closest('tr');
        calculateRow(row, true);
        calculateTotals();
    });

    // Manual amount entry
    $(document).on('input', '.amount', function() {
        let row = $(this).closest('tr');
        calculateRow(row, true);
        calculateTotals();
    });


    // Add new row on Enter
    $(document).on('keypress', '.amount', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            let newRow = `<tr>
               <tr>
                                    <td>
                                        <select name="narration_id[]" class="form-select narrationSelect">
                                            <option value="">Select</option>
                                            @foreach($narrations as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input name="reference_no[]" type="text" class="form-control"></td>
                                    <td>
                                        <select name="vendor_type" class="form-select">
                                            <option value="">Select</option>
                                            <option value="vendor">Vendor</option>
                                            <option value="customer">Customer</option>
                                            <option value="walkin">Walkin Customer</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="vendor_id" class="form-select">
                                            <option disabled selected>Select</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="tel" id="tel" class="form-control" readonly>
                                    </td>
                                    <td><input name="discount_value[]" type="number" class="form-control discountValue" value="0"></td>
                                    <td><input name="kg[]" type="number" class="form-control kg"></td>
                                    <td><input name="rate[]" type="number" class="form-control rate"></td>
<td><input name="amount[]" type="text" class="form-control text-end amount"></td>
                                    <td><button class="btn btn-danger btn-sm removeRow"><i class="bi bi-trash"></i></button></td>
                                </tr>`;
            $('#voucherTable tbody').append(newRow);
        }
    });

    // Delete row
    $(document).on('click', '.removeRow', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Next button refresh
    $('#nextBtn').on('click', function() {
        location.reload();
    });

    $(document).on('change', '.accountHead', function() {
        let headId = $(this).val();
        let $subSelect = $(this).closest('tr').find('.accountSub');

        if (!headId) {
            $subSelect.html('<option value="" disabled selected>Select Account</option>');
            return;
        }

        $.ajax({
            url: "{{ url('/get-accounts-by-head') }}/" + headId,
            type: "GET",
            success: function(res) {
                let html = '<option value="" disabled selected>Select Account</option>';
                res.forEach(acc => {
                    html += `<option value="${acc.id}">${acc.title}</option>`;
                });
                $subSelect.html(html);
            }
        });
    });

    $(document).on('change', '.rowAccountHead', function() {
        let headId = $(this).val();

        // row ke andar rowAccountSub select ko dhoondo
        let $subSelect = $(this).closest('.row').find('.rowAccountSub');

        if (!headId) {
            $subSelect.html('<option value="">Select Account</option>');
            return;
        }

        $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
            let html = '<option value="">Select Account</option>';
            res.forEach(acc => {
                html += `<option value="${acc.id}">${acc.title}</option>`;
            });
            $subSelect.html(html);
        });
    });

    function calculateAccountsTotal() {
        let total = 0;
        $('.accountAmount').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#accountsTotal').val(total.toFixed(2));
    }

    // Trigger when account amount changes
    $(document).on('input', '.accountAmount', function() {
        calculateAccountsTotal();
    });

    // Trigger when account row removed
    $(document).on('click', '.removeAccountRow', function() {
        $(this).closest('tr').remove();
        calculateAccountsTotal();
    });

    // Trigger after adding new row
    $('#addAccountRow').on('click', function() {
        let newRow = `
        <tr>
            <td>
                <select name="account_head_id[]" class="form-control form-control-sm accountHead">
                    <option value="" disabled selected>Select Head</option>
                    @foreach ($AccountHeads as $head)
                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="account_id[]" class="form-control form-control-sm accountSub">
                    <option value="" disabled selected>Select Account</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="0">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
            </td>
        </tr>`;
        $('#accountsTable tbody').append(newRow);

        // recalc after adding
        calculateAccountsTotal();
    });
</script>

@endsection
