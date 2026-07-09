@extends('admin_panel.layout.app')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--multiple {
        min-height: 38px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #5897fb !important;
        border: 1px solid #aaa;
        border-radius: 4px;
        margin-right: 5px;
        margin-top: 5px;
        padding: 0 22px !important;
        color: #fff !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff !important;
    }
    .account-group-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 1.25rem;
        overflow: hidden;
    }
    .account-group-header {
        background: #f8f9fa;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .account-group-header .acc-title {
        font-weight: 700;
        color: #212529;
    }
    .account-group-header .acc-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .account-group-total {
        font-weight: 700;
        color: #0d6efd;
        font-size: 1.05rem;
    }
    .line-item {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.25rem 0;
        font-size: 0.85rem;
        border-bottom: 1px dashed #eee;
    }
    .line-item:last-child { border-bottom: none; }
    .summary-bar {
        background: linear-gradient(135deg, #0d6efd, #0a58ca);
        color: #fff;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>Expense Report — Account Wise</h4>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Account Head</label>
                            <select name="account_heads[]" id="account_heads" class="form-control form-control-sm select2" multiple>
                                <option value="all">All</option>
                                @foreach ($accountHeads as $head)
                                <option value="{{ $head->id }}">{{ $head->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Account</label>
                            <select name="accounts[]" id="accounts" class="form-control form-control-sm select2" multiple>
                                @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" data-head="{{ $account->head_id }}">
                                    {{ $account->title }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-none" id="resultCard">
                <div class="summary-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="small opacity-75">Grand Total Expense</div>
                        <div class="fs-4 fw-bold" id="grandTotal">0.00</div>
                    </div>
                    <div class="text-end">
                        <div class="small opacity-75">Accounts / Vouchers</div>
                        <div class="fw-semibold"><span id="accountCount">0</span> accounts · <span id="voucherCount">0</span> vouchers</div>
                    </div>
                </div>

                <div id="accountGroups"></div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ placeholder: "Select option", allowClear: true, width: '100%' });

    $('#account_heads').on('change', function() {
        const selectedHeads = $(this).val() || [];
        const $accounts = $('#accounts option');

        if (selectedHeads.includes('all')) {
            $accounts.prop('selected', false).show();
            $('#accounts').trigger('change');
            return;
        }

        $accounts.each(function() {
            const headId = $(this).data('head')?.toString();
            if (!selectedHeads.length || selectedHeads.includes(headId)) {
                $(this).show();
            } else {
                $(this).prop('selected', false).hide();
            }
        });
        $('#accounts').trigger('change');
    });

    $('#btnSearch').on('click', function() {
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
                $('#accountGroups').html('<div class="text-center py-4 text-muted">Loading...</div>');
                $('#resultCard').removeClass('d-none');
            },
            success(res) {
                $('#grandTotal').text('Rs. ' + res.grand_total);
                $('#accountCount').text(res.account_count);
                $('#voucherCount').text(res.voucher_count);

                if (!res.groups || res.groups.length === 0) {
                    $('#accountGroups').html('<div class="alert alert-light text-center">No expense found for selected filters.</div>');
                    return;
                }

                let html = '';
                res.groups.forEach(function(group, gi) {
                    let detailRows = '';
                    group.vouchers.forEach(function(v, vi) {
                        let linesHtml = '';
                        if (v.lines && v.lines.length) {
                            v.lines.forEach(function(line) {
                                linesHtml += `<div class="line-item"><span>${line.remark}</span><span class="fw-semibold">Rs ${line.amount}</span></div>`;
                            });
                        } else {
                            linesHtml = '<span class="text-muted">-</span>';
                        }

                        detailRows += `
                            <tr>
                                <td>${vi + 1}</td>
                                <td class="fw-semibold">${v.evid}</td>
                                <td>${v.date}</td>
                                <td>${v.head}</td>
                                <td>${linesHtml}</td>
                                <td class="text-end fw-bold">Rs ${v.amount}</td>
                            </tr>`;
                    });

                    html += `
                        <div class="account-group-card">
                            <div class="account-group-header">
                                <div>
                                    <div class="acc-title">${group.account}</div>
                                    <div class="acc-meta">Account Head: ${group.head} · ${group.voucher_count} voucher(s)</div>
                                </div>
                                <div class="account-group-total">Total: Rs ${group.total}</div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Voucher</th>
                                            <th>Date</th>
                                            <th>Account Head</th>
                                            <th>Details (Remarks)</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>${detailRows}</tbody>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <th colspan="5" class="text-end">Account Subtotal — ${group.account}</th>
                                            <th class="text-end">Rs ${group.total}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>`;
                });

                $('#accountGroups').html(html);
            },
            error() {
                $('#accountGroups').html('<div class="alert alert-danger">Error loading report. Please try again.</div>');
            }
        });
    });
});
</script>
@endsection
