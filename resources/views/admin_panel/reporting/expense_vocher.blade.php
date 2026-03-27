@extends('admin_panel.layout.app')

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Extra spacing for Select2 inside bootstrap cards */
    .select2-container--default .select2-selection--multiple {
        min-height: 38px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #5897fb !important;
        border: 1px solid #aaa;
        border-radius: 4px;
        cursor: default;
        float: left;
        margin-right: 5px;
        margin-top: 5px;
        padding: 0px 22px !important;
        color: #fff !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #999;
        cursor: pointer;
        display: inline-block;
        font-weight: bold;
        margin-right: 2px;
        color: #fff !important;
    }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>Expense Report</h4>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">

                        {{-- Account Head --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Account Head</label>
                            <select name="account_heads[]"
                                id="account_heads"
                                class="form-control form-control-sm select2"
                                multiple>
                                <option value="all">All</option>
                                @foreach ($accountHeads as $head)
                                <option value="{{ $head->id }}">{{ $head->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Account --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Account</label>
                            <select name="accounts[]"
                                id="accounts"
                                class="form-control form-control-sm select2"
                                multiple>
                                @foreach ($accounts as $account)
                                <option value="{{ $account->id }}"
                                    data-head="{{ $account->head_id }}">
                                    {{ $account->title }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Start Date --}}
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Start Date</label>
                            <input type="date"
                                name="start_date"
                                class="form-control form-control-sm"
                                value="{{ date('Y-m-d') }}">
                        </div>

                        {{-- End Date --}}
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">End Date</label>
                            <input type="date"
                                name="end_date"
                                class="form-control form-control-sm"
                                value="{{ date('Y-m-d') }}">
                        </div>

                        {{-- Search Button --}}
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                🔍 Search
                            </button>
                        </div>

                    </div>

                </div>
            </div>
            <div class="card d-none" id="resultCard">
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Voucher</th>
                                    <th>Date</th>
                                    <th>Account Head</th>
                                    <th>Remarks</th>
                                    <th>Account</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="expenseRows"></tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th colspan="6" class="text-end">Total Expense</th>
                                    <th class="text-end" id="grandTotal">0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>



        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            placeholder: "Select option",
            allowClear: true,
            width: '100%'
        });

        // Account Head -> Account filter
        $('#account_heads').on('change', function() {
            const selectedHeads = $(this).val() || [];
            const $accounts = $('#accounts option');

            if (selectedHeads.includes('all')) {
                $accounts.prop('selected', true).show();
                $('#accounts').trigger('change');
                return;
            }

            $accounts.each(function() {
                const headId = $(this).data('head').toString();
                if (selectedHeads.includes(headId)) {
                    $(this).show();
                } else {
                    $(this).prop('selected', false).hide();
                }
            });
            $('#accounts').trigger('change');
        });
    });

    $(document).ready(function() {

        $('.select2').select2({
            width: '100%'
        });

        $('.btn-primary').on('click', function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('expense.voucher.ajax') }}",
                type: "GET",
                data: {
                    account_heads: $('#account_heads').val(),
                    accounts: $('#accounts').val(),
                    start_date: $('input[name="start_date"]').val(),
                    end_date: $('input[name="end_date"]').val(),
                },
                beforeSend() {
                    $('#expenseRows').html(
                        `<tr><td colspan="7" class="text-center">Loading...</td></tr>`
                    );
                },
                success(res) {
                    let rows = '';

                    if (res.rows.length === 0) {
                        rows = `<tr>
                        <td colspan="7" class="text-center text-muted">
    No expense found
</td>
                    </tr>`;
                    } else {
                        res.rows.forEach((row, i) => {
                            rows += `
<tr>
    <td>${i + 1}</td>
    <td>${row.evid}</td>
    <td>${row.date}</td>
    <td>${row.head ?? '-'}</td>
    <td>${row.remarks ?? '-'}</td>
    <td>${row.account ?? '-'}</td>
    <td class="text-end">${row.amount}</td>
</tr>`;
                        });
                    }

                    $('#expenseRows').html(rows);
                    $('#grandTotal').text(res.total);
                    $('#resultCard').removeClass('d-none');
                }
            });
        });

    });
</script>
@endsection