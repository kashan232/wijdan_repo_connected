@extends('admin_panel.layout.app')



@section('content')

    @include('hr.partials.hr-styles')

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />



    <style>

        .leave-card {

            background: var(--hr-card);

            border: 1px solid var(--hr-border);

            border-radius: 14px;

            padding: 20px;

            transition: all 0.2s;

        }



        .leave-card:hover {

            transform: translateY(-2px);

            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);

        }



        .leave-card.pending {

            border-left: 4px solid #f59e0b;

        }



        .leave-card.approved {

            border-left: 4px solid #22c55e;

        }



        .leave-card.rejected {

            border-left: 4px solid #ef4444;

        }



        .leave-dates {

            background: #f8fafc;

            border-radius: 10px;

            padding: 12px;

            text-align: center;

            margin-top: 12px;

        }



        .leave-dates .date-range {

            font-weight: 600;

            color: var(--hr-text);

        }



        .leave-dates .days-count {

            font-size: 0.85rem;

            color: var(--hr-muted);

        }



        .action-buttons {

            display: flex;

            gap: 8px;

            margin-top: 12px;

        }



        .action-buttons .btn {

            flex: 1;

            padding: 10px;

            border-radius: 8px;

            font-weight: 600;

        }

    </style>



    <div class="container-fluid py-4">


                <!-- Page Header -->

                <div class="page-header d-flex justify-content-between align-items-start">

                    <div>

                        <h1 class="page-title"><i class="fa fa-calendar-minus"></i> Leave Management</h1>

                        <p class="page-subtitle">Manage employee leave requests</p>

                    </div>

                    @can('hr.leaves.create')

                        <button type="button" class="btn btn-create" id="createBtn">

                            <i class="fa fa-plus"></i> Request Leave

                        </button>

                    @endcan

                </div>



                <!-- Stats Row -->

                @php

                    $pendingCount = \App\Models\Hr\Leave::where('status', 'pending')->count();

                    $approvedCount = \App\Models\Hr\Leave::where('status', 'approved')->count();

                    $rejectedCount = \App\Models\Hr\Leave::where('status', 'rejected')->count();

                @endphp

                <div class="stats-row">

                    <div class="stat-card primary">

                        <div class="stat-icon"><i class="fa fa-calendar-minus"></i></div>

                        <div class="stat-value">{{ $leaves->total() }}</div>

                        <div class="stat-label">Total Requests</div>

                    </div>

                    <div class="stat-card warning">

                        <div class="stat-icon"><i class="fa fa-hourglass-half"></i></div>

                        <div class="stat-value">{{ $pendingCount }}</div>

                        <div class="stat-label">Pending</div>

                    </div>

                    <div class="stat-card success">

                        <div class="stat-icon"><i class="fa fa-check-circle"></i></div>

                        <div class="stat-value">{{ $approvedCount }}</div>

                        <div class="stat-label">Approved</div>

                    </div>

                    <div class="stat-card danger">

                        <div class="stat-icon"><i class="fa fa-times-circle"></i></div>

                        <div class="stat-value">{{ $rejectedCount }}</div>

                        <div class="stat-label">Rejected</div>

                    </div>

                </div>



                <!-- Leaves Card -->

                <div class="hr-card">

                    <div class="hr-header">

                        <div class="d-flex align-items-center gap-3">

                            <div class="search-box">

                                <i class="fa fa-search"></i>

                                <input type="search" id="leaveSearch" placeholder="Search leaves...">

                            </div>

                            <div class="btn-group">

                                <button class="btn btn-outline-secondary btn-sm active" data-filter="all">All</button>

                                <button class="btn btn-outline-warning btn-sm" data-filter="pending">Pending</button>

                                <button class="btn btn-outline-success btn-sm" data-filter="approved">Approved</button>

                                <button class="btn btn-outline-danger btn-sm" data-filter="rejected">Rejected</button>

                            </div>

                        </div>

                        <span class="text-muted small" id="leaveCount">{{ $leaves->total() }} requests</span>

                    </div>



                    <div class="hr-grid" id="leaveGrid">

                        @forelse($leaves as $leave)

                            @php

                                $startDate = \Carbon\Carbon::parse($leave->start_date);

                                $endDate = \Carbon\Carbon::parse($leave->end_date);

                                $days = $startDate->diffInDays($endDate) + 1;

                            @endphp

                            <div class="leave-card {{ $leave->status }}" data-id="{{ $leave->id }}"

                                data-name="{{ strtolower($leave->employee->full_name ?? '') }}"

                                data-status="{{ $leave->status }}">

                                <div class="hr-item-header">

                                    <div class="d-flex align-items-center">

                                        <div class="hr-avatar">

                                            {{ strtoupper(substr($leave->employee->first_name ?? 'U', 0, 1) . substr($leave->employee->last_name ?? 'N', 0, 1)) }}

                                        </div>

                                        <div class="hr-item-info">

                                            <h4 class="hr-item-name">{{ $leave->employee->full_name ?? 'Unknown' }}</h4>

                                            <div class="hr-item-subtitle">{{ $leave->leave_type }} Leave</div>

                                        </div>

                                    </div>

                                    <span

                                        class="hr-tag {{ $leave->status == 'approved' ? 'success' : ($leave->status == 'rejected' ? 'danger' : 'warning') }}">

                                        {{ ucfirst($leave->status) }}

                                    </span>

                                </div>



                                <div class="leave-dates">

                                    <div class="date-range">

                                        {{ $startDate->format('M d') }} - {{ $endDate->format('M d, Y') }}

                                    </div>

                                    <div class="days-count">{{ $days }} day{{ $days > 1 ? 's' : '' }}</div>

                                </div>



                                @if ($leave->reason)

                                    <p class="text-muted small mt-3 mb-0"><i

                                            class="fa fa-comment-alt me-2"></i>{{ $leave->reason }}</p>

                                @endif



                                <!-- Salary Deduction Status -->

                                <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">

                                    @if ($leave->deduct_salary)

                                        <span class="badge bg-danger">

                                            <i class="fa fa-minus-circle"></i> Salary Will Be Deducted

                                        </span>

                                    @else

                                        <span class="badge bg-success">

                                            <i class="fa fa-check-circle"></i> No Salary Deduction

                                        </span>

                                    @endif



                                    @can('hr.leaves.approve')

                                        <button class="btn btn-sm btn-outline-secondary toggle-deduction-btn"

                                            data-id="{{ $leave->id }}" style="font-size:0.75rem; padding:2px 10px;">

                                            <i

                                                class="fa fa-toggle-{{ $leave->deduct_salary ? 'on text-danger' : 'off text-success' }}"></i>

                                            Override

                                        </button>

                                    @endcan

                                </div>



                                @if ($leave->status == 'pending')

                                    @can('hr.leaves.approve')

                                        <div class="action-buttons">

                                            <button class="btn btn-success approve-btn"

                                                data-url="{{ route('hr.leaves.update-status', $leave->id) }}">

                                                <i class="fa fa-check me-1"></i> Approve

                                            </button>

                                            <button class="btn btn-danger reject-btn"

                                                data-url="{{ route('hr.leaves.update-status', $leave->id) }}">

                                                <i class="fa fa-times me-1"></i> Reject

                                            </button>

                                        </div>

                                    @endcan

                                @endif

                            </div>

                        @empty

                            <div class="empty-state" style="grid-column: 1/-1;">

                                <i class="fa fa-calendar-minus"></i>

                                <p>No leave requests found.</p>

                            </div>

                        @endforelse

                    </div>

                    <div class="px-4 py-3 border-top">

                        {{ $leaves->links() }}

                    </div>

                </div>

    </div>






    <!-- Modal -->

    <div class="modal fade" id="leaveModal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header gradient">

                    <h5 class="modal-title" id="modalLabel">

                        <i class="fa fa-calendar-minus"></i>

                        <span>Request Leave</span>

                    </h5>

                    <button type="button" class="btn-close" data-dismiss="modal"></button>

                </div>

                <form id="leaveForm" action="{{ route('hr.leaves.store') }}" method="POST" data-ajax-validate="true">

                    @csrf

                    <div class="modal-body">

                        <div class="form-group-modern">

                            <label class="form-label"><i class="fa fa-user"></i> Employee</label>

                            <select name="employee_id" id="employee_id" class="form-select select2" style="width: 100%;"

                                required>

                                <option value="">Select Employee</option>

                                @foreach ($employees as $emp)

                                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>

                                @endforeach

                            </select>

                        </div>

                        <div class="form-group-modern">

                            <label class="form-label"><i class="fa fa-tag"></i> Leave Type</label>

                            <select name="leave_type" id="leave_type" class="form-select" required>

                                <option value="Sick">Sick Leave</option>

                                <option value="Casual">Casual Leave</option>

                                <option value="Annual">Annual Leave</option>

                            </select>

                        </div>

                        <div id="leave_balance_display" class="alert alert-info py-2"

                            style="display:none; font-size: 0.9rem;">

                            <!-- Dynamically populated -->

                        </div>

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group-modern">

                                    <label class="form-label"><i class="fa fa-calendar"></i> Start Date</label>

                                    <input type="date" name="start_date" class="form-control" required>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="form-group-modern">

                                    <label class="form-label"><i class="fa fa-calendar"></i> End Date</label>

                                    <input type="date" name="end_date" class="form-control" required>

                                </div>

                            </div>

                        </div>

                        <div class="form-group-modern">

                            <label class="form-label"><i class="fa fa-align-left"></i> Reason</label>

                            <textarea name="reason" class="form-control" rows="3" placeholder="Optional reason for leave"></textarea>

                        </div>



                        <!-- Salary Deduction Toggle -->

                        <div id="deductToggleBox" class="alert alert-warning py-2 mt-3"

                            style="display:none;font-size: 0.9rem;">

                            <div class="form-check">

                                <input class="form-check-input" type="checkbox" id="deductCheckbox" name="deduct_salary"

                                    value="1">

                                <label class="form-check-label text-dark fw-bold" for="deductCheckbox">

                                    <i class="fa fa-minus-circle text-danger me-1"></i> Deduct salary for this leave?

                                </label>

                                <div id="forceDeductBadge" class="badge bg-danger ms-2" style="display:none;">QUOTA

                                    EXHAUSTED (Forced)</div>

                            </div>

                        </div>

                    </div>

                    <div class="modal-footer-modern">

                        <button type="button" class="btn btn-cancel" data-dismiss="modal">

                            <i class="fa fa-times me-2"></i>Cancel

                        </button>

                        <button type="submit" class="btn btn-save">

                            <i class="fa fa-check"></i>

                            <span>Submit Request</span>

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>



@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>



    <script>

        $(document).ready(function() {

            $('.select2').select2({

                dropdownParent: $('#leaveModal')

            });



            $('#createBtn').click(function() {

                $('#leaveForm')[0].reset();

                $('#employee_id').val('').trigger('change');

                $('#leave_balance_display').hide();

                $('#leaveModal').modal('show');

            });



            // var days (optional logic)

            function checkLeaveBalance() {

                var empId = $('#employee_id').val();

                var leaveType = $('#leave_type').val();



                // Get dates if available to calculate requested days

                var start = $('input[name="start_date"]').val();

                var end = $('input[name="end_date"]').val();

                var reqDays = 1;

                if (start && end) {

                    var d1 = new Date(start),

                        d2 = new Date(end);

                    if (d2 >= d1) reqDays = Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;

                }



                if (empId && (leaveType === 'Casual' || leaveType === 'Sick')) {

                    $('#leave_balance_display').show().html(

                        '<i class="fa fa-spinner fa-spin"></i> Checking balance...');

                    $.ajax({

                        url: '{{ route('hr.leaves.balance') }}',

                        type: 'GET',

                        dataType: 'json',

                        data: {

                            employee_id: empId,

                            leave_type: leaveType,

                            days: reqDays

                        },

                        success: function(res) {

                            var s = res.summary || {};

                            var force = res.force_deduct || false;



                            var data = null;

                            if (leaveType === 'Casual') {

                                data = s.casual;

                            } else if (leaveType === 'Sick') {

                                data = s.sick;

                            }



                            if (!data) {

                                $('#leave_balance_display').html(

                                    '<span class="text-danger">Failed to parse balance or no data available.</span>'

                                    );

                                return;

                            }



                            var allocated = data.allocated !== undefined ? data.allocated : 0;

                            var used = data.used !== undefined ? data.used : 0;

                            var remaining = data.remaining !== undefined ? data.remaining : 0;



                            var badgeClass = remaining > 0 ? 'success' : 'danger';

                            $('#leave_balance_display').html(

                                '<strong>' + leaveType + ' Leave Balance:</strong><br>' +

                                'Allocated: <span class="badge bg-primary">' + allocated +

                                '</span> ' +

                                'Used: <span class="badge bg-warning text-dark">' + used +

                                '</span> ' +

                                'Remaining: <span class="badge bg-' + badgeClass + '">' +

                                remaining + '</span>'

                            );



                            // Toggle Deduction Checkbox Logic

                            $('#deductToggleBox').show();

                            if (force) {

                                $('#deductToggleBox').addClass('bg-danger text-white');

                                $('#deductCheckbox').prop('checked', true).prop('disabled', true);

                                $('#forceDeductBadge').show();

                            } else {

                                $('#deductToggleBox').removeClass('bg-danger text-white');

                                $('#deductCheckbox').prop('checked', false).prop('disabled', false);

                                $('#forceDeductBadge').hide();

                            }

                        },

                        error: function() {

                            $('#leave_balance_display').html(

                                '<span class="text-danger">Failed to load balance.</span>');

                            $('#deductToggleBox').hide();

                        }

                    });

                } else {

                    $('#leave_balance_display').hide();

                    if (leaveType) {

                        $('#deductToggleBox').hide();

                        $('#deductCheckbox').prop('checked', true).prop('disabled', false);

                    } else {

                        $('#deductToggleBox').hide();

                    }

                }

            }



            $('#employee_id, #leave_type, input[name="start_date"], input[name="end_date"]').on('change',

                checkLeaveBalance);



            // Filter buttons

            $('[data-filter]').click(function() {

                $(this).addClass('active').siblings().removeClass('active');

                var filter = $(this).data('filter');



                $('.leave-card').each(function() {

                    if (filter === 'all') {

                        $(this).show();

                    } else {

                        $(this).toggle($(this).data('status') === filter);

                    }

                });

                updateCount();

            });



            function updateCount() {

                $('#leaveCount').text($('.leave-card:visible').length + ' requests');

            }



            $('#leaveSearch').on('input', function() {

                var q = $(this).val().toLowerCase();

                $('.leave-card').each(function() {

                    var name = $(this).data('name') || '';

                    $(this).toggle(name.indexOf(q) !== -1);

                });

                updateCount();

            });



            $(document).on('click', '.approve-btn, .reject-btn', function() {

                var url = $(this).data('url');

                var status = $(this).hasClass('approve-btn') ? 'approved' : 'rejected';

                var btn = $(this);



                Swal.fire({

                    title: status === 'approved' ? 'Approve Leave?' : 'Reject Leave?',

                    icon: 'question',

                    showCancelButton: true,

                    confirmButtonColor: status === 'approved' ? '#22c55e' : '#ef4444',

                    confirmButtonText: 'Yes, ' + status + '!'

                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({

                            url: url,

                            type: 'PATCH',

                            data: {

                                _token: '{{ csrf_token() }}',

                                status: status

                            },

                            success: function(response) {

                                if (response.success === false) {

                                    Swal.fire('Error', response.message, 'error');

                                } else if (response.success) {

                                    Swal.fire('Success', response.success, 'success')

                                        .then(() => location.reload());

                                }

                            },

                            error: function(xhr) {

                                let msg = 'Something went wrong.';

                                if (xhr.responseJSON && xhr.responseJSON.message) {

                                    msg = xhr.responseJSON.message;

                                }

                                Swal.fire('Error', msg, 'error');

                            }

                        });

                    }

                });

            });



            // ─── Toggle Deduction on existing leave cards ───

            $(document).on('click', '.toggle-deduction-btn', function() {

                var leaveId = $(this).data('id');

                $.ajax({

                    url: '/hr/leaves/' + leaveId + '/toggle-deduction',

                    method: 'PATCH',

                    data: {

                        _token: '{{ csrf_token() }}'

                    },

                    success: function(res) {

                        if (res.success) location.reload();

                    },

                    error: function(xhr) {

                        Swal.fire('Error', xhr.responseJSON?.error || 'Failed', 'error');

                    }

                });

            });



            // Re-enable disabled checkbox before form submit if data-ajax-validate is submitting it natively or doing ajax

            $('#leaveForm').on('submit', function() {

                $('#deductCheckbox').prop('disabled', false);

            });

        });

    </script>

@endsection

