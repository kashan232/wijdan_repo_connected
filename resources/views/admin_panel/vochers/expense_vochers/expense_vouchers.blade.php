@extends('admin_panel.layout.app')
@section('content')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="main-content">
    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold">Expense Voucher</h3>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Errors:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif

                @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
                @endif

                <form action="{{ route('expense.vochers.store') }}" method="POST">
                    @csrf

                    {{-- Top Info --}}
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">EVID</label>
                            <input type="text" class="form-control form-control-sm"
                                name="evid" value="{{ $nextRvid }}" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Account Head</label>
                            <select name="vendor_type" class="form-select form-select-sm">
                                <option value="">Select</option>
                                @foreach($AccountHeads as $head)
                                <option value="{{ $head->id }}">{{ $head->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Account</label>
                            <select name="vendor_id" class="form-select form-select-sm">
                                <option disabled selected>Select</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Date</label>
                            <input type="date" class="form-control form-control-sm"
                                name="date" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    {{-- Voucher Table --}}
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="voucherTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Remarks</th>
                                    <th class="text-end" style="width:180px">Amount</th>
                                    <th style="width:60px"></th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td>
                                        <input type="text" name="remarks[]" class="form-control form-control-sm remark-input"
                                            placeholder="Enter remarks">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01"
                                            name="amount[]" class="form-control form-control-sm text-end amount-input"
                                            placeholder="0.00">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger removeRow">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th class="text-end fw-bold">Total</th>
                                    <th>
                                        <input type="text" id="totalAmount"
                                            class="form-control form-control-sm text-end fw-bold"
                                            readonly value="0.00">
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-4">
                        <button class="btn btn-primary px-4">Save</button>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary px-4">Exit</a>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

@endsection

@section('scripts')
<script>
    // 🔢 Calculate Total
    function calculateTotal() {
        let total = 0;
        $('.amount-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#totalAmount').val(total.toFixed(2));
    }

    // ➕ Add new row
    function addRow() {
        let row = `
        <tr>
            <td>
                <input type="text" name="remarks[]" class="form-control form-control-sm remark-input"
                       placeholder="Enter remarks">
            </td>
            <td>
                <input type="number" step="0.01"
                       name="amount[]" class="form-control form-control-sm text-end amount-input"
                       placeholder="0.00">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger removeRow">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;
        $('#voucherTable tbody').append(row);
        $('#voucherTable tbody tr:last .remark-input').focus();
    }

    // ⌨ Enter on Amount → new row
    $(document).on('keypress', '.amount-input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            calculateTotal();
            addRow();
        }
    });

    // 🔄 Recalculate on typing
    $(document).on('input', '.amount-input', function() {
        calculateTotal();
    });

    // ❌ Remove row
    $(document).on('click', '.removeRow', function() {
        $(this).closest('tr').remove();
        calculateTotal();
    });

    // 🔁 Load accounts on head change
    $(document).on('change', 'select[name="vendor_type"]', function() {
        let headId = $(this).val();
        let $account = $('select[name="vendor_id"]');

        $account.html('<option disabled selected>Loading...</option>');

        if (!headId) {
            $account.html('<option disabled selected>Select</option>');
            return;
        }

        $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
            let html = '<option disabled selected>Select</option>';
            res.forEach(acc => {
                html += `<option value="${acc.id}">${acc.title}</option>`;
            });
            $account.html(html);
        });
    });
</script>
@endsection