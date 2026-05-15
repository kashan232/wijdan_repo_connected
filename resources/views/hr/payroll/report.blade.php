@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        .report-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            display: block;
        }

        .filter-input, .select2-container--bootstrap4 .select2-selection {
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            height: 42px !important;
            color: #475569 !important;
            font-weight: 500 !important;
        }

        .payroll-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.875rem;
        }

        .payroll-table th {
            background-color: #f8fafc;
            color: #1e293b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }

        .payroll-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .payroll-table tr:hover {
            background-color: #f8fafc;
        }

        .employee-info {
            display: flex;
            flex-direction: column;
        }

        .employee-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .employee-meta {
            font-size: 0.75rem;
            color: #64748b;
        }

        .amount-box {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 1rem;
        }

        .deduction-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #ef4444;
            margin-bottom: 2px;
        }

        .allowance-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #10b981;
            margin-bottom: 2px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-paid { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-reviewed { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
        .status-generated { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .print-only { display: none; }

        @media print {
            .btn, .report-card:first-of-type, .main-sidebar, .main-header { display: none !important; }
            .content-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .report-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .print-only { display: block !important; }
            .payroll-table th { background-color: #f8fafc !important; -webkit-print-color-adjust: exact; }
        }
    </style>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark"><i class="fa fa-receipt me-2 text-primary"></i> Payroll Record</h1>
                <p class="text-muted mb-0">Detailed historical payroll logs for {{ $monthLabel }}</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary shadow-sm px-4" onclick="window.print()">
                    <i class="fa fa-print me-2"></i> Export Report
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="report-card">
            <form method="GET" action="{{ route('hr.payroll.report') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="filter-label">Month</label>
                    <input type="month" name="month" class="form-control filter-input" value="{{ $selectedMonth }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Department</label>
                    <select name="department_id" class="form-select select2" onchange="this.form.submit()">
                        <option value="all">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Employee</label>
                    <select name="employee_id" class="form-select select2" onchange="this.form.submit()">
                        <option value="all">All Employees</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_id_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Status</label>
                    <select name="status" class="form-select select2" onchange="this.form.submit()">
                        <option value="all">All Status</option>
                        <option value="generated" {{ request('status') == 'generated' ? 'selected' : '' }}>Generated</option>
                        <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('hr.payroll.report') }}" class="btn btn-light border w-100" style="height: 42px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa fa-sync"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Report Table -->
        <div class="report-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="payroll-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Base Salary</th>
                            <th>Deductions</th>
                            <th>Allowances</th>
                            <th>Net Payable</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payrolls as $payroll)
                            <tr>
                                <td>
                                    <div class="employee-info">
                                        <span class="employee-name">{{ $payroll->employee->full_name }}</span>
                                        <span class="employee-meta">{{ $payroll->employee->employee_id_code }} | {{ $payroll->employee->designation->name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="amount-box text-dark">
                                        {{ number_format($payroll->basic_salary, 2) }}
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $attDeduction = $payroll->attendance_deductions;
                                        $loanDeduction = $payroll->details->where('type', 'deduction')->where('name', 'Loan Installment')->sum('amount');
                                        $otherDeductions = $payroll->manual_deductions + $payroll->deductions - $loanDeduction;
                                    @endphp
                                    
                                    @if($attDeduction > 0)
                                        <div class="deduction-item">
                                            <span>Attendance:</span>
                                            <span>-{{ number_format($attDeduction, 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($loanDeduction > 0)
                                        <div class="deduction-item">
                                            <span>Loan:</span>
                                            <span>-{{ number_format($loanDeduction, 2) }}</span>
                                        </div>
                                    @endif

                                    @foreach($payroll->details->where('type', 'deduction')->where('name', '!=', 'Loan Installment') as $detail)
                                        <div class="deduction-item">
                                            <span>{{ $detail->name }}:</span>
                                            <span>-{{ number_format($detail->amount, 2) }}</span>
                                        </div>
                                    @endforeach

                                    @if($payroll->manual_deductions > 0 && $payroll->details->where('type', 'deduction')->isEmpty())
                                        <div class="deduction-item">
                                            <span>Manual:</span>
                                            <span>-{{ number_format($payroll->manual_deductions, 2) }}</span>
                                        </div>
                                    @endif

                                    <div class="amount-box text-danger mt-1 pt-1 border-top" style="font-size: 0.8rem;">
                                        Total: -{{ number_format($payroll->total_deductions, 2) }}
                                    </div>
                                </td>
                                <td>
                                    @foreach($payroll->details->where('type', 'allowance') as $detail)
                                        <div class="allowance-item">
                                            <span>{{ $detail->name }}:</span>
                                            <span>+{{ number_format($detail->amount, 2) }}</span>
                                        </div>
                                    @endforeach

                                    @if($payroll->commission > 0)
                                        <div class="allowance-item">
                                            <span>Commission:</span>
                                            <span>+{{ number_format($payroll->commission, 2) }}</span>
                                        </div>
                                    @endif

                                    <div class="amount-box text-success mt-1 pt-1 border-top" style="font-size: 0.8rem;">
                                        Total: +{{ number_format($payroll->total_allowances + $payroll->commission, 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="amount-box text-primary">
                                        {{ number_format($payroll->net_salary, 2) }}
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $payroll->status }}">
                                        {{ $payroll->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted small">
                                        @if($payroll->status == 'paid')
                                            <i class="fa fa-calendar-check me-1"></i> {{ $payroll->payment_date ? $payroll->payment_date->format('d M Y') : 'N/A' }}
                                            <br>
                                            <span class="badge bg-light text-dark border">{{ $payroll->payment_method }}</span>
                                        @else
                                            <span class="text-danger">Pending</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa fa-folder-open fa-3x mb-3 opacity-25"></i>
                                    <p>No payroll records found for the selected filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    });
</script>
@endsection
