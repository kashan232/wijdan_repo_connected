@extends('admin_panel.layout.app')
@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="main-content">
    <div class="container-fluid">
        <div class="card-header mt-2 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Expense Vouchers</h4>
            <a class="btn btn-primary" href="{{ route('expense-vochers') }}">Add Expense Voucher</a>
        </div>

        @if($isAdmin)
        <div class="d-flex justify-content-end mb-2">
            <button id="toggleExpenseTotalBtn" class="btn btn-info text-white">
                <i class="fas fa-eye"></i> Show Total Expense
            </button>
        </div>
        <div id="expenseTotalCard" class="card shadow-sm mt-3 mb-3 border-0" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); display: none;">
            <div class="card-body py-3 d-flex justify-content-between align-items-center text-white">
                <div>
                    <div class="small text-white-50">Total Expense</div>
                    <div class="fs-4 fw-bold">Rs. {{ number_format($totalExpense, 2) }}</div>
                </div>
                <div class="text-end">
                    <div class="small text-white-50">Total Vouchers</div>
                    <div class="fs-5 fw-semibold">{{ $vouchers->count() }}</div>
                </div>
            </div>
        </div>
        @endif

        <div class="card shadow mt-2 mb-5">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="productTable" class="table table-striped table-bordered align-middle nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Voucher No</th>
                                <th>Account Head</th>
                                <th>Account</th>
                                <th style="min-width:260px;">Remarks</th>
                                <th>Total Amount</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vouchers as $voucher)
                            <tr>
                                <td>{{ $voucher->id }}</td>
                                <td>{{ $voucher->evid }}</td>
                                <td>{{ $voucher->type_name }}</td>
                                <td>{{ $voucher->party_name }}</td>
                                <td>
                                    @php
                                    $remarks = is_array($voucher->remarks)
                                    ? $voucher->remarks
                                    : json_decode($voucher->remarks, true) ?? [];

                                    $amounts = is_array($voucher->amount)
                                    ? $voucher->amount
                                    : json_decode($voucher->amount, true) ?? [];
                                    @endphp

                                    @foreach ($remarks as $i => $remark)
                                    <div class="d-flex justify-content-between align-items-center mb-1 p-2 rounded bg-light border">
                                        <span class="text-dark fw-medium">
                                            {{ $remark }}
                                        </span>
                                        <span class="badge bg-primary">
                                            Rs {{ number_format($amounts[$i] ?? 0, 2) }}
                                        </span>
                                    </div>
                                    @endforeach
                                </td>
                                <td class="text-end fw-bold text-success">
                                    Rs {{ number_format($voucher->total_amount, 2) }}
                                </td>
                                <td>{{ \Carbon\Carbon::parse($voucher->date)->format('d-m-Y') }}</td>
                                <td>
                                    <a href="{{ route('expense.voucher.edit', $voucher->id) }}"
                                        class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="{{ route('expenseVoucher.print', $voucher->id) }}"
                                        target="_blank"
                                        class="btn btn-sm btn-danger">
                                        <i class="bi bi-printer"></i>
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

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#productTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            order: [
                [1, 'desc'] // ✅ Invoice No / ID column ke basis pe latest pehle
            ],
            columnDefs: [{
                    targets: 0,
                    orderable: false
                } // S.No column sortable nahi
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search Sale..."
            }
        });

        $('#toggleExpenseTotalBtn').on('click', function() {
            var $card = $('#expenseTotalCard');
            var btn = $(this);
            if ($card.is(':hidden')) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Confirm Access',
                        text: 'Please enter your password to view total expense',
                        input: 'password',
                        inputAttributes: {
                            autocapitalize: 'off',
                            placeholder: 'Admin Password'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Verify & Show',
                        showLoaderOnConfirm: true,
                        preConfirm: (password) => {
                            if (!password) {
                                Swal.showValidationMessage('Password is required');
                                return false;
                            }
                            return fetch("{{ route('warehouse_stocks.verify_password') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ password: password })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (!data.success) {
                                    throw new Error(data.message || 'Incorrect password');
                                }
                                return data;
                            })
                            .catch(error => {
                                Swal.showValidationMessage(error.message);
                            });
                        },
                        allowOutsideClick: () => !Swal.isLoading()
                    }).then((result) => {
                        if (result.isConfirmed && result.value.success) {
                            $card.slideDown(300);
                            btn.html('<i class="fas fa-eye-slash"></i> Hide Total Expense');
                            btn.removeClass('btn-info').addClass('btn-secondary');
                            Swal.fire({
                                icon: 'success',
                                title: 'Access Granted',
                                timer: 1000,
                                showConfirmButton: false
                            });
                        }
                    });
                } else {
                    var pwd = prompt("Enter Admin Password to view total expense:");
                    if (pwd) {
                        $.post("{{ route('warehouse_stocks.verify_password') }}", {
                            _token: '{{ csrf_token() }}',
                            password: pwd
                        }, function(res) {
                            if (res.success) {
                                $card.slideDown(300);
                                btn.html('<i class="fas fa-eye-slash"></i> Hide Total Expense');
                                btn.removeClass('btn-info').addClass('btn-secondary');
                            } else {
                                alert("Incorrect password");
                            }
                        });
                    }
                }
            } else {
                $card.slideUp(300);
                $(this).html('<i class="fas fa-eye"></i> Show Total Expense');
                $(this).removeClass('btn-secondary').addClass('btn-info');
            }
        });
    });
</script>

@endsection
