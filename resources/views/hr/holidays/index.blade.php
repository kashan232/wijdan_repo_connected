@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        .holiday-card {
            background: var(--hr-card);
            border: 1px solid var(--hr-border);
            border-radius: 14px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .holiday-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .holiday-header {
            padding: 16px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .holiday-header.public {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .holiday-header.company {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
        }

        .holiday-header.optional {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .holiday-body {
            padding: 24px;
            text-align: center;
        }

        .holiday-date-big {
            font-size: 3rem;
            font-weight: 800;
            color: var(--hr-text);
            line-height: 1;
        }

        .holiday-month {
            font-size: 1.1rem;
            color: var(--hr-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .holiday-day {
            font-size: 0.9rem;
            color: var(--hr-muted);
            margin-top: 4px;
        }

        .year-select-modern {
            border: 2px solid var(--hr-border);
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 600;
            background: white;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-calendar-alt"></i> Holiday Management</h1>
                        <p class="page-subtitle">Manage public and company holidays</p>
                    </div>
                    <div class="d-flex gap-3">
                        <select id="yearSelect" class="year-select-modern">
                            @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}
                                </option>
                            @endfor
                        </select>
                        @can('hr.holidays.create')
                            <button type="button" class="btn btn-create" id="createBtn">
                                <i class="fa fa-plus"></i> Add Holiday
                            </button>
                        @endcan
                    </div>
                </div>

                <!-- Stats Row -->
                @php
                    $allHolidays = \App\Models\Hr\Holiday::whereYear('date', $year)->get();
                    $publicCount = $allHolidays->where('type', 'public')->count();
                    $companyCount = $allHolidays->where('type', 'company')->count();
                    $optionalCount = $allHolidays->where('type', 'optional')->count();
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-calendar-alt"></i></div>
                        <div class="stat-value">{{ $holidays->total() }}</div>
                        <div class="stat-label">Total Holidays</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-icon"><i class="fa fa-flag"></i></div>
                        <div class="stat-value">{{ $publicCount }}</div>
                        <div class="stat-label">Public</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-building"></i></div>
                        <div class="stat-value">{{ $companyCount }}</div>
                        <div class="stat-label">Company</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-question-circle"></i></div>
                        <div class="stat-value">{{ $optionalCount }}</div>
                        <div class="stat-label">Optional</div>
                    </div>
                </div>

                <!-- Holidays Card -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="holidaySearch" placeholder="Search holidays...">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm" id="refreshBtn"><i
                                        class="fa fa-sync"></i></button>
                            </div>
                        </div>
                        <span class="text-muted small" id="holidayCount">{{ $holidays->total() }} holidays in
                            {{ $year }}</span>
                    </div>

                    <div class="hr-grid" id="holidayGrid">
                        @forelse($holidays as $holiday)
                            <div class="holiday-card" data-id="{{ $holiday->id }}"
                                data-name="{{ strtolower($holiday->name) }}">
                                <div class="holiday-header {{ $holiday->type }}">
                                    <strong>{{ $holiday->name }}</strong>
                                    <div class="hr-actions">
                                        @can('hr.holidays.edit')
                                            <button class="btn btn-sm text-white assign-btn"
                                                style="background: linear-gradient(135deg, #0ea5e9, #3b82f6); border: none; margin-right: 4px;"
                                                data-id="{{ $holiday->id }}" data-name="{{ $holiday->name }}"
                                                data-employees="{{ json_encode($holiday->employees->pluck('id')) }}"
                                                title="Assign Employees">
                                                <i class="fa fa-users"></i>
                                            </button>
                                            <button class="btn btn-sm text-white edit-btn"
                                                style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; margin-right: 4px;"
                                                data-id="{{ $holiday->id }}" data-name="{{ $holiday->name }}"
                                                data-date="{{ $holiday->date->format('Y-m-d') }}"
                                                data-end_date="{{ $holiday->end_date ? $holiday->end_date->format('Y-m-d') : '' }}"
                                                data-type="{{ $holiday->type }}"
                                                data-description="{{ $holiday->description }}">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                        @endcan
                                        @can('hr.holidays.delete')
                                            <button class="btn btn-sm text-white delete-btn"
                                                style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none;"
                                                data-id="{{ $holiday->id }}" title="Delete Holiday">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                                <div class="holiday-body">
                                    <div class="holiday-date-big">{{ $holiday->date->format('d') }}
                                        {{ $holiday->end_date && $holiday->end_date->format('Y-m-d') != $holiday->date->format('Y-m-d') ? '- ' . $holiday->end_date->format('d') : '' }}
                                    </div>
                                    <div class="holiday-month">{{ $holiday->date->format('F') }}</div>
                                    <div class="holiday-day">{{ $holiday->date->format('l') }}</div>
                                    @if ($holiday->description)
                                        <p class="text-muted small mt-3 mb-0">{{ $holiday->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-calendar-times"></i>
                                <p>No holidays defined for {{ $year }}. Add your first!</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 border-top">
                        {{ $holidays->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="holidayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #ef4444, #dc2626) !important;">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fa fa-calendar-alt"></i>
                        <span>Add Holiday</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="holidayForm" action="{{ route('hr.holidays.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-tag"></i> Holiday Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                placeholder="e.g., Eid ul Fitr" required>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-calendar"></i> Start Date</label>
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-calendar"></i> End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                            <small class="text-muted">Leave blank for a single day holiday.</small>
                        </div>

                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-tag"></i> Type</label>
                            <select name="type" id="type" class="form-select" required>
                                <option value="public">Public Holiday</option>
                                <option value="company">Company Holiday</option>
                                <option value="optional">Optional Holiday</option>
                            </select>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-align-left"></i> Description</label>
                            <textarea name="description" id="description" class="form-control" rows="2"
                                placeholder="Optional description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save" id="saveHolidayBtn"
                            style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="fa fa-check"></i>
                            <span>Save Holiday</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #0ea5e9, #3b82f6) !important;">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="fa fa-users"></i>
                        <span>Assign Employees to <span id="assignHolidayName"></span></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <!-- We do NOT use data-ajax-validate="true" here specifically if we want manual handling or just rely on generic, but let's use standard ajax handling if generic applies -->
                <form id="assignForm" action="#" method="POST">
                    @csrf
                    <div class="modal-body bg-light p-4">
                        <div class="row mb-3 g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Search Employee</label>
                                <input type="text" id="employeeSearch" class="form-control"
                                    placeholder="Name, Email...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Department</label>
                                <select id="filterDepartment" class="form-select">
                                    <option value="">All Departments</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ strtolower($dept->name) }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Designation</label>
                                <select id="filterDesignation" class="form-select">
                                    <option value="">All Designations</option>
                                    @foreach ($designations as $desig)
                                        <option value="{{ strtolower($desig->name) }}">{{ $desig->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive bg-white rounded shadow-sm border"
                            style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" id="employeeTable">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">
                                            <input type="checkbox" id="selectAllEmployees" class="form-check-input">
                                        </th>
                                        <th>Employee Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employees as $emp)
                                        <tr class="employee-row"
                                            data-name="{{ strtolower($emp->first_name . ' ' . $emp->last_name) }}"
                                            data-email="{{ strtolower($emp->email) }}"
                                            data-department="{{ strtolower($emp->department->name ?? '') }}"
                                            data-designation="{{ strtolower($emp->designation->name ?? '') }}">
                                            <td class="text-center">
                                                <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                                    class="form-check-input emp-checkbox">
                                            </td>
                                            <td class="fw-bold">{{ $emp->first_name }} {{ $emp->last_name }}</td>
                                            <td class="text-muted">{{ $emp->email }}</td>
                                            <td><span
                                                    class="badge bg-info text-dark rounded-pill">{{ $emp->department->name ?? 'N/A' }}</span>
                                            </td>
                                            <td><span
                                                    class="badge bg-secondary rounded-pill">{{ $emp->designation->name ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr id="noEmployeeRow" style="display: none;">
                                        <td colspan="5" class="text-center text-muted py-4">No employees matching the
                                            criteria</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 text-muted small">
                            <i class="fa fa-info-circle me-1"></i> Leave all unchecked to apply universally to all
                            employees.
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save" id="saveAssignBtn"
                            style="background: linear-gradient(135deg, #0ea5e9, #3b82f6);">
                            <i class="fa fa-check"></i>
                            <span>Save Assignments</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {

            $('#yearSelect').change(function() {
                window.location.href = '{{ route('hr.holidays.index') }}?year=' + $(this).val();
            });

            $('#createBtn').click(function() {
                $('#holidayForm')[0].reset();
                $('#edit_id').val('');
                $('#modalTitle').html('<i class="fa fa-calendar-alt"></i><span>Add Holiday</span>');
                $('#holidayModal').modal('show');
            });

            $(document).on('click', '.edit-btn', function() {
                $('#edit_id').val($(this).data('id'));
                $('#name').val($(this).data('name'));
                $('#date').val($(this).data('date'));
                $('#end_date').val($(this).data('end_date'));
                $('#type').val($(this).data('type'));
                $('#description').val($(this).data('description'));
                $('#modalTitle').html('<i class="fa fa-pen"></i><span>Edit Holiday</span>');
                $('#holidayModal').modal('show');
            });

            $(document).on('click', '.assign-btn', function() {
                var holidayId = $(this).data('id');
                var holidayName = $(this).data('name');
                var assignedEmpIds = $(this).data('employees');

                $('#assignHolidayName').text(holidayName);

                // Uncheck all first
                $('.emp-checkbox').prop('checked', false);

                // Check previously assigned
                if (assignedEmpIds && assignedEmpIds.length > 0) {
                    assignedEmpIds.forEach(function(id) {
                        $('.emp-checkbox[value="' + id + '"]').prop('checked', true);
                    });
                }

                updateSelectAllCheckbox();
                $('#assignForm').attr('action', '/hr/holidays/' + holidayId + '/assign-employees');
                $('#employeeSearch').val('');
                $('#filterDepartment').val('');
                $('#filterDesignation').val('');
                filterEmployees(); // resetting filters
                $('#assignModal').modal('show');
            });

            function filterEmployees() {
                var search = $('#employeeSearch').val().toLowerCase();
                var dept = $('#filterDepartment').val();
                var desig = $('#filterDesignation').val();
                var visibleCount = 0;

                $('.employee-row').each(function() {
                    var matchName = $(this).data('name').indexOf(search) > -1;
                    var matchEmail = $(this).data('email').indexOf(search) > -1;
                    var matchSearch = search === '' || matchName || matchEmail;

                    var matchDept = dept === '' || $(this).data('department') === dept;
                    var matchDesig = desig === '' || $(this).data('designation') === desig;

                    if (matchSearch && matchDept && matchDesig) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                if (visibleCount === 0) {
                    $('#noEmployeeRow').show();
                } else {
                    $('#noEmployeeRow').hide();
                }

                updateSelectAllCheckbox();
            }

            $('#employeeSearch').on('input', filterEmployees);
            $('#filterDepartment, #filterDesignation').on('change', filterEmployees);

            $('#selectAllEmployees').change(function() {
                var isChecked = $(this).prop('checked');
                $('.employee-row:visible .emp-checkbox').prop('checked', isChecked);
            });

            $('.emp-checkbox').change(function() {
                updateSelectAllCheckbox();
            });

            function updateSelectAllCheckbox() {
                var visibleRows = $('.employee-row:visible').length;
                var checkedVisibleRows = $('.employee-row:visible .emp-checkbox:checked').length;

                if (visibleRows > 0 && visibleRows === checkedVisibleRows) {
                    $('#selectAllEmployees').prop('checked', true);
                } else {
                    $('#selectAllEmployees').prop('checked', false);
                }
            }

            $(document).on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Holiday?',
                    text: 'This cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/hr/holidays/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: response.success,
                                        icon: 'success',
                                        confirmButtonColor: '#3b82f6'
                                    }).then(() => location.reload());
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'There was a problem deleting the holiday.',
                                    icon: 'error',
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                        });
                    }
                });
            });

            $('#holidaySearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('.holiday-card').each(function() {
                    var name = $(this).data('name') || '';
                    $(this).toggle(name.indexOf(q) !== -1);
                });
                $('#holidayCount').text($('.holiday-card:visible').length + ' holidays');
            });

            $('#refreshBtn').click(() => location.reload());

            // AJAX Submit for Holiday Form
            $('#holidayForm').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = $('#saveHolidayBtn');
                var originalHtml = submitBtn.html();

                submitBtn.html('<i class="fa fa-spinner fa-spin"></i> <span>Saving...</span>').prop(
                    'disabled', true);

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: form.serialize(),
                    success: function(response) {
                        submitBtn.html(originalHtml).prop('disabled', false);

                        if (response.success) {
                            $('#holidayModal').modal('hide');
                            Swal.fire({
                                title: 'Success!',
                                text: response.success,
                                icon: 'success',
                                confirmButtonColor: '#10b981',
                                iconColor: '#10b981',
                                backdrop: `rgba(0,0,0,0.4)`
                            }).then(() => {
                                if (response.reload) location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        submitBtn.html(originalHtml).prop('disabled', false);

                        var errorMsg = 'An unexpected error occurred.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var firstError = Object.values(xhr.responseJSON.errors)[0][0];
                            errorMsg = firstError;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: 'Failed to Save',
                            text: errorMsg,
                            icon: 'error',
                            confirmButtonColor: '#ef4444',
                            iconColor: '#ef4444',
                            backdrop: `rgba(0,0,0,0.4)`
                        });
                    }
                });
            });

            // AJAX Submit for Assign Employees Form
            $('#assignForm').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = $('#saveAssignBtn');
                var originalHtml = submitBtn.html();

                submitBtn.html('<i class="fa fa-spinner fa-spin"></i> <span>Assigning...</span>').prop(
                    'disabled', true);

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: form.serialize(),
                    success: function(response) {
                        submitBtn.html(originalHtml).prop('disabled', false);

                        if (response.success) {
                            $('#assignModal').modal('hide');
                            Swal.fire({
                                title: 'Assigned Successfully!',
                                text: response.success,
                                icon: 'success',
                                confirmButtonColor: '#3b82f6',
                                iconColor: '#3b82f6',
                                backdrop: `rgba(0,0,0,0.4)`
                            }).then(() => {
                                if (response.reload) location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        submitBtn.html(originalHtml).prop('disabled', false);

                        var errorMsg = 'An unexpected error occurred.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var firstError = Object.values(xhr.responseJSON.errors)[0][0];
                            errorMsg = firstError;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: 'Assignment Failed',
                            text: errorMsg,
                            icon: 'error',
                            confirmButtonColor: '#ef4444',
                            iconColor: '#ef4444',
                            backdrop: `rgba(0,0,0,0.4)`
                        });
                    }
                });
            });
        });
    </script>
@endsection
