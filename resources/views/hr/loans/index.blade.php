@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        /* ─── Loan Progress Bar ─── */
        .loan-progress { height: 6px; border-radius: 4px; background: #e2e8f0; overflow: hidden; }
        .loan-progress-fill { height: 100%; border-radius: 4px; transition: width 0.4s ease; }

        /* ─── Loan Type Badge ─── */
        .loan-type-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 0.6rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.05em; padding: 2px 8px; border-radius: 20px;
        }
        .loan-type-badge.salary { background: #dbeafe; color: #1d4ed8; }
        .loan-type-badge.self   { background: #fef3c7; color: #92400e; }
        .loan-type-badge.overdue { background: #fee2e2; color: #dc2626; }

        /* ─── Installment Preview Card ─── */
        #installmentPreview {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #bae6fd; border-radius: 12px; padding: 16px;
            display: none; margin-top: 16px;
        }
        #installmentPreview .preview-value {
            font-size: 1.4rem; font-weight: 800; color: #0369a1;
        }
        #installmentPreview .preview-label {
            font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em;
            color: #64748b; font-weight: 600;
        }

        /* ─── Loan Step Dots ─── */
        .loan-type-selector { display: flex; gap: 12px; margin-bottom: 20px; }
        .loan-type-option {
            flex: 1; border: 2px solid #e2e8f0; border-radius: 12px; padding: 14px 10px;
            cursor: pointer; transition: all 0.2s; text-align: center;
        }
        .loan-type-option.selected { border-color: #6366f1; background: #f0f0ff; }
        .loan-type-option i { font-size: 1.5rem; margin-bottom: 6px; display: block; }
        .loan-type-option.selected i { color: #6366f1; }
        .loan-type-option strong { font-size: 0.8rem; display: block; }
        .loan-type-option small { font-size: 0.65rem; color: #94a3b8; }

        /* ─── History Timeline ─── */
        .payment-timeline { position: relative; padding-left: 24px; }
        .payment-timeline::before {
            content: ''; position: absolute; left: 8px; top: 0; bottom: 0;
            width: 2px; background: #e2e8f0;
        }
        .timeline-item { position: relative; margin-bottom: 14px; }
        .timeline-dot {
            position: absolute; left: -20px; top: 4px; width: 10px; height: 10px;
            border-radius: 50%; background: #10b981; border: 2px solid white;
            box-shadow: 0 0 0 2px #10b981;
        }
        .timeline-dot.manual { background: #f59e0b; box-shadow: 0 0 0 2px #f59e0b; }
        .timeline-dot.payroll { background: #6366f1; box-shadow: 0 0 0 2px #6366f1; }

        /* ─── Loan Detail Panel ─── */
        .loan-detail-bar {
            display: flex; gap: 20px; padding: 16px 20px;
            background: #f8fafc; border-radius: 0; border-bottom: 1px solid #e2e8f0;
        }
        .loan-detail-bar .stat { text-align: center; }
        .loan-detail-bar .stat .val { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
        .loan-detail-bar .stat .lbl { font-size: 0.6rem; text-transform: uppercase; color: #94a3b8; font-weight: 600; }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">

                {{-- ── Page Header ── --}}
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="page-title"><i class="fa fa-hand-holding-usd"></i> Loans & Advances</h4>
                        <p class="text-muted mb-0">Manage employee loans, advances, and repayment schedules</p>
                    </div>
                    @can('hr.loans.create')
                        <button class="btn btn-create" id="openLoanModalBtn">
                            <i class="fa fa-plus"></i> New Loan Request
                        </button>
                    @endcan
                </div>

                {{-- ── Stats ── --}}
                @php
                    $pendingCount = \App\Models\Hr\Loan::where('status', 'pending')->count();
                    $activeAmount = \App\Models\Hr\Loan::where('status', 'approved')->sum('amount')
                                 - \App\Models\Hr\Loan::where('status', 'approved')->sum('paid_amount');
                    $paidCount    = \App\Models\Hr\Loan::where('status', 'paid')->count();
                    $selfPaidActive = \App\Models\Hr\Loan::active()->selfPaid()->count();
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-file-invoice-dollar"></i></div>
                        <div class="stat-value">{{ $loans->total() }}</div>
                        <div class="stat-label">Total Loans</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-clock"></i></div>
                        <div class="stat-value">{{ $pendingCount }}</div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-money-bill-wave"></i></div>
                        <div class="stat-value">{{ number_format($activeAmount) }}</div>
                        <div class="stat-label">Active Outstanding</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                        <div class="stat-value">{{ $paidCount }}</div>
                        <div class="stat-label">Fully Repaid</div>
                    </div>
                </div>

                {{-- ── Loan Grid ── --}}
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="loanSearch" placeholder="Search loans...">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm filter-btn active" data-filter="all">All</button>
                                <button class="btn btn-outline-secondary btn-sm filter-btn" data-filter="pending">Pending</button>
                                <button class="btn btn-outline-secondary btn-sm filter-btn" data-filter="approved">Active</button>
                                <button class="btn btn-outline-secondary btn-sm filter-btn" data-filter="paid">Paid</button>
                            </div>
                        </div>
                        <span class="text-muted small" id="loanCount">{{ $loans->total() }} records</span>
                    </div>

                    <div class="hr-grid" id="loanGrid">
                        @forelse($loans as $loan)
                            <div class="hr-item-card"
                                data-id="{{ $loan->id }}"
                                data-employee="{{ strtolower($loan->employee->full_name) }}"
                                data-status="{{ $loan->status }}"
                                data-type="{{ $loan->loan_type }}">

                                {{-- Header --}}
                                <div class="hr-item-header">
                                    <div class="d-flex align-items-center">
                                        <div class="hr-avatar" style="background: linear-gradient(135deg, {{ $loan->loan_type === 'salary_deduction' ? '#6366f1, #8b5cf6' : '#f59e0b, #d97706' }});">
                                            {{ strtoupper(substr($loan->employee->first_name, 0, 1)) }}
                                        </div>
                                        <div class="hr-item-info">
                                            <h4 class="hr-item-name">{{ $loan->employee->full_name }}</h4>
                                            <div class="hr-item-subtitle">{{ $loan->employee->designation->name ?? 'N/A' }}</div>
                                            <div class="hr-item-meta">
                                                <i class="fa fa-calendar-alt me-1"></i>
                                                {{ $loan->created_at->format('d M, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm border" type="button" data-bs-toggle="dropdown">
                                            <i class="fa fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <a class="dropdown-item py-2" href="javascript:void(0)" onclick="viewLoanDetails({{ $loan->id }})">
                                                    <i class="fa fa-eye me-2 text-primary"></i> View Details
                                                </a>
                                            </li>

                                            @if($loan->status === 'pending')
                                                @can('hr.loans.create')
                                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick='editLoan(@json($loan))'><i class="fa fa-edit me-2 text-info"></i> Edit</a></li>
                                                @endcan
                                            @endif

                                            @if($loan->status === 'approved')
                                                @if($loan->loan_type === 'self_paid')
                                                    <li><a class="dropdown-item text-success py-2" href="javascript:void(0)" onclick="recordPaymentModal({{ $loan->id }}, {{ $loan->remaining_amount }})"><i class="fa fa-plus-circle me-2"></i> Record Payment</a></li>
                                                @else
                                                    <li><a class="dropdown-item text-primary py-2" href="javascript:void(0)" onclick="scheduleDeductionModal({{ $loan->id }}, {{ $loan->remaining_amount }})"><i class="fa fa-calendar-plus me-2"></i> Schedule Deduction</a></li>
                                                @endif
                                            @endif

                                            @can('hr.loans.delete')
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger py-2" href="javascript:void(0)" onclick="deleteLoan({{ $loan->id }})"><i class="fa fa-trash me-2"></i> Delete</a></li>
                                            @endcan
                                        </ul>
                                    </div>
                                </div>

                                {{-- Type Badge --}}
                                <div class="mt-2 mb-2">
                                    <span class="loan-type-badge {{ $loan->loan_type === 'salary_deduction' ? 'salary' : 'self' }}">
                                        <i class="fa {{ $loan->loan_type === 'salary_deduction' ? 'fa-building' : 'fa-user' }}"></i>
                                        {{ $loan->type_label }}
                                    </span>
                                    @if($loan->is_overdue)
                                        <span class="loan-type-badge overdue ms-1"><i class="fa fa-exclamation-triangle"></i> Overdue</span>
                                    @endif
                                </div>

                                {{-- Amounts --}}
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted small">Loan Amount</span>
                                        <span class="fw-bold fs-5">Rs. {{ number_format($loan->amount) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted small">
                                            @if($loan->loan_type === 'salary_deduction')
                                                Monthly Deduction
                                            @else
                                                Repayment Type
                                            @endif
                                        </span>
                                        <span class="fw-medium small">
                                            @if($loan->loan_type === 'salary_deduction')
                                                Rs. {{ number_format($loan->monthly_installment) }}/mo
                                                @if($loan->total_installments)
                                                    <span class="text-muted">({{ $loan->remaining_installments }}/{{ $loan->total_installments }} left)</span>
                                                @endif
                                            @else
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.65rem;">Self-Paid</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">Remaining</span>
                                        <span class="fw-bold text-danger">Rs. {{ number_format($loan->remaining_amount) }}</span>
                                    </div>

                                    {{-- Progress Bar --}}
                                    <div class="loan-progress">
                                        <div class="loan-progress-fill"
                                            style="width: {{ $loan->progress_percentage }}%;
                                                   background: {{ $loan->progress_percentage >= 100 ? '#10b981' : ($loan->progress_percentage >= 50 ? '#f59e0b' : '#6366f1') }};"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span style="font-size: 0.6rem; color: #94a3b8;">{{ $loan->progress_percentage }}% repaid</span>
                                        @if($loan->expected_end_month)
                                            <span style="font-size: 0.6rem; color: #94a3b8;">Ends {{ \Carbon\Carbon::parse($loan->expected_end_month.'-01')->format('M Y') }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Status --}}
                                <div class="hr-tags pt-2 border-top mt-auto">
                                    @if($loan->status === 'pending')
                                        @can('hr.loans.approve')
                                            <div class="d-flex gap-2 w-100 mb-1">
                                                <button class="btn btn-sm btn-success flex-grow-1" onclick="approveLoan({{ $loan->id }})"><i class="fa fa-check me-1"></i> Approve</button>
                                                <button class="btn btn-sm btn-danger flex-grow-1" onclick="rejectLoan({{ $loan->id }})"><i class="fa fa-times me-1"></i> Reject</button>
                                            </div>
                                        @else
                                            <span class="hr-tag warning w-100 text-center">Pending Approval</span>
                                        @endcan
                                    @elseif($loan->status === 'approved')
                                        <span class="hr-tag success w-100 text-center">Active Loan</span>
                                    @elseif($loan->status === 'rejected')
                                        <span class="hr-tag danger w-100 text-center">Rejected</span>
                                    @elseif($loan->status === 'paid')
                                        <span class="hr-tag info w-100 text-center">✓ Fully Repaid</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-file-invoice-dollar"></i>
                                <p>No loan records found.</p>
                                <p class="text-muted small">Click "New Loan Request" to create one.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="px-4 py-3 border-top">
                        {{ $loans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         NEW LOAN MODAL (Two-Type Wizard)
    ═══════════════════════════════════════════ --}}
    <div class="modal fade" id="addLoanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 16px;">
                <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;">
                    <div>
                        <h5 class="modal-title fw-800 mb-0"><i class="fa fa-hand-holding-usd me-2"></i> New Loan Request</h5>
                        <p class="mb-0 opacity-75 small">Choose loan type and configure repayment terms</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('hr.loans.store') }}" method="POST" id="addLoanForm" data-ajax-validate="true">
                    @csrf
                    <input type="hidden" name="loan_type" id="loanTypeInput" value="salary_deduction">

                    <div class="modal-body p-4">

                        {{-- Step 1: Loan Type --}}
                        <div class="loan-type-selector">
                            <div class="loan-type-option selected" id="opt_salary" onclick="selectLoanType('salary_deduction')">
                                <i class="fa fa-building text-primary"></i>
                                <strong>Salary Deduction</strong>
                                <small>Auto-deducted from monthly payroll</small>
                            </div>
                            <div class="loan-type-option" id="opt_self" onclick="selectLoanType('self_paid')">
                                <i class="fa fa-user text-warning"></i>
                                <strong>Self-Paid Loan</strong>
                                <small>Employee pays directly (cash/bank)</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            {{-- Employee --}}
                            <div class="col-12">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-user me-1"></i> Employee</label>
                                    <select name="employee_id" class="form-select select2-loan" required style="width:100%;">
                                        <option value="">Select Employee</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->designation->name ?? 'N/A' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Loan Amount --}}
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-money-bill me-1"></i> Loan Amount (Rs.)</label>
                                    <input type="number" name="amount" id="loanAmount" class="form-control" required min="1" placeholder="e.g. 50000" oninput="triggerPreview()">
                                </div>
                            </div>

                            {{-- Start Month (salary type) --}}
                            <div class="col-md-6" id="startMonthField">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-calendar me-1"></i> First Deduction Month</label>
                                    <input type="month" name="start_month" id="startMonthInput" class="form-control" value="{{ date('Y-m', strtotime('+1 month')) }}" oninput="triggerPreview()">
                                </div>
                            </div>
                        </div>

                        {{-- Installment Setup (salary type only) --}}
                        <div id="salaryDeductionFields" class="mt-3">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="flex-grow-1 border-top"></div>
                                <span class="text-muted small text-uppercase fw-600" style="font-size: 0.65rem; white-space: nowrap;">Repayment Setup</span>
                                <div class="flex-grow-1 border-top"></div>
                            </div>

                            <div class="d-flex gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary active flex-1" id="byMonthsBtn" onclick="setRepaymentMode('months')">By Months</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-1" id="byAmountBtn" onclick="setRepaymentMode('amount')">By Monthly Amount</button>
                            </div>

                            <div id="numMonthsField">
                                <div class="form-group-modern">
                                    <label class="form-label">Number of Months</label>
                                    <input type="number" name="num_months" id="numMonths" class="form-control" min="1" max="360" placeholder="e.g. 6" oninput="triggerPreview()">
                                </div>
                            </div>

                            <div id="monthlyAmountField" style="display:none;">
                                <div class="form-group-modern">
                                    <label class="form-label">Monthly Installment (Rs.)</label>
                                    <input type="number" name="installment_amount" id="installmentAmountInput" class="form-control" min="1" placeholder="e.g. 5000" oninput="triggerPreview()">
                                </div>
                            </div>

                            {{-- Live Preview --}}
                            <div id="installmentPreview">
                                <div class="row text-center g-0 mb-3">
                                    <div class="col-4">
                                        <div class="preview-label">Monthly Payment</div>
                                        <div class="preview-value text-primary" id="prev_monthly">—</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="preview-label">Duration</div>
                                        <div class="preview-value" id="prev_months">—</div>
                                        <div style="font-size:0.65rem; color:#64748b;">months</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="preview-label">End Month</div>
                                        <div class="preview-value" style="font-size: 1rem;" id="prev_end">—</div>
                                    </div>
                                </div>
                                <div class="text-center" style="font-size: 0.72rem; color: #0369a1; font-weight: 600;">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Starting <span id="prev_start">—</span> — <span id="prev_months_label">—</span> installments of <span id="prev_monthly_label">—</span>
                                </div>
                            </div>
                        </div>

                        {{-- Self-paid note --}}
                        <div id="selfPaidFields" class="mt-3" style="display:none;">
                            <div class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis">
                                <i class="fa fa-user-circle me-2"></i>
                                <strong>Self-Paid Loan:</strong> Employee will repay directly (cash/bank transfer). No automatic payroll deduction. You can record payments manually from the loan card.
                            </div>
                        </div>

                        {{-- Reason --}}
                        <div class="form-group-modern mt-3">
                            <label class="form-label"><i class="fa fa-sticky-note me-1"></i> Reason / Purpose</label>
                            <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Medical emergency, travel, personal need..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-save"><i class="fa fa-paper-plane me-1"></i> Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         RECORD PAYMENT MODAL (Self-Paid)
    ═══════════════════════════════════════════ --}}
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                    <h5 class="modal-title fw-800 mb-0"><i class="fa fa-plus-circle me-2"></i> Record Loan Payment</h5>
                    <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal"></button>
                </div>
                <form id="recordPaymentForm" data-ajax-validate="true">
                    @csrf
                    <input type="hidden" id="rp_loan_id">
                    <div class="modal-body p-4">
                        <div class="alert alert-success bg-success-subtle text-success-emphasis border-0 mb-3">
                            <small><i class="fa fa-info-circle me-1"></i> Outstanding: <strong>Rs. <span id="rp_remaining">0</span></strong></small>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">Amount (Rs.)</label>
                                    <input type="number" id="rp_amount" name="amount" class="form-control" min="1" placeholder="Payment amount" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">Payment Type</label>
                                    <select name="type" class="form-select" required>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="salary_deduction">Salary Deduction</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">Reference (Optional)</label>
                                    <input type="text" name="reference" class="form-control" placeholder="Voucher / Bank Ref">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group-modern">
                                    <label class="form-label">Notes</label>
                                    <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-save" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fa fa-check me-1"></i> Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         SCHEDULE DEDUCTION MODAL
    ═══════════════════════════════════════════ --}}
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title text-warning-emphasis"><i class="fa fa-clock me-2"></i> Schedule One-Off Deduction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.loans.schedule') }}" method="POST" id="scheduleForm" data-ajax-validate="true">
                    @csrf
                    <input type="hidden" name="loan_id" id="schedule_loan_id">
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis mb-3">
                            <i class="fa fa-info-circle me-1"></i> Force a one-time deduction from salary for a specific month.
                        </div>
                        <div class="form-group-modern mb-3">
                            <label class="form-label">Deduction Amount</label>
                            <input type="number" name="amount" id="schedule_amount" class="form-control" required min="1">
                            <small class="text-muted">Max: Rs. <span id="max_sched_amount" class="fw-bold"></span></small>
                        </div>
                        <div class="form-group-modern mb-3">
                            <label class="form-label">For Month</label>
                            <input type="month" name="month" class="form-control" required value="{{ date('Y-m') }}">
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-save bg-warning border-warning text-dark"><i class="fa fa-calendar-check me-1"></i> Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         LOAN DETAILS MODAL (Full History)
    ═══════════════════════════════════════════ --}}
    <div class="modal fade" id="loanDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header border-0 p-4" id="loanDetailsHeader" style="background: linear-gradient(135deg, #1e293b, #334155); color: white;">
                    <div>
                        <h5 class="modal-title fw-800 mb-0" id="ld_emp_name">Loading...</h5>
                        <div id="ld_subtitle" class="opacity-75 small mt-1"></div>
                    </div>
                    <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal"></button>
                </div>

                {{-- Stats Bar --}}
                <div class="loan-detail-bar flex-wrap" id="ld_stats_bar">
                    <div class="stat"><div class="val" id="ld_total">—</div><div class="lbl">Total Loan</div></div>
                    <div class="stat"><div class="val text-success" id="ld_paid">—</div><div class="lbl">Paid</div></div>
                    <div class="stat"><div class="val text-danger" id="ld_remaining">—</div><div class="lbl">Outstanding</div></div>
                    <div class="stat"><div class="val text-primary" id="ld_installment">—</div><div class="lbl">Monthly Inst.</div></div>
                    <div class="stat"><div class="val" id="ld_inst_count">—</div><div class="lbl">Installments Left</div></div>
                </div>

                {{-- Progress --}}
                <div class="px-4 py-2 bg-white border-bottom">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Repayment Progress</small>
                        <small class="fw-bold" id="ld_progress_pct">0%</small>
                    </div>
                    <div class="loan-progress" style="height: 8px;">
                        <div class="loan-progress-fill bg-success" id="ld_progress_bar" style="width: 0%;"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted" id="ld_start_label"></small>
                        <small class="text-muted" id="ld_end_label"></small>
                    </div>
                </div>

                <div class="modal-body p-0">
                    <ul class="nav nav-tabs nav-justified border-bottom px-4 pt-3 bg-light" id="ldTab" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#ld_payments" type="button"><i class="fa fa-list me-1"></i> Payment History</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#ld_schedule" type="button"><i class="fa fa-calendar me-1"></i> Installment Schedule</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#ld_info" type="button"><i class="fa fa-info-circle me-1"></i> Loan Info</button></li>
                    </ul>

                    <div class="tab-content p-4 bg-light" id="ldTabContent">
                        {{-- Payment History --}}
                        <div class="tab-pane fade show active" id="ld_payments" role="tabpanel">
                            <div id="ld_payment_timeline" class="payment-timeline"></div>
                        </div>

                        {{-- Schedule --}}
                        <div class="tab-pane fade" id="ld_schedule" role="tabpanel">
                            <div id="ld_schedule_content">
                                <p class="text-muted text-center py-3">Schedule only available for salary-deduction loans.</p>
                            </div>
                        </div>

                        {{-- Loan Info --}}
                        <div class="tab-pane fade" id="ld_info" role="tabpanel">
                            <div class="row g-3" id="ld_info_content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {
    // ── Select2 ──
    $('.select2-loan').select2({ dropdownParent: $('#addLoanModal'), width: '100%' });

    // ── Search ──
    $('#loanSearch').on('keyup', function () {
        const q = $(this).val().toLowerCase();
        $('#loanGrid .hr-item-card').filter(function () {
            $(this).toggle($(this).data('employee').includes(q));
        });
        $('#loanCount').text($('#loanGrid .hr-item-card:visible').length + ' records');
    });

    // ── Filter Buttons ──
    $('.filter-btn').on('click', function () {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        const f = $(this).data('filter');
        $('#loanGrid .hr-item-card').each(function () {
            $(this).toggle(f === 'all' || $(this).data('status') === f);
        });
        $('#loanCount').text($('#loanGrid .hr-item-card:visible').length + ' records');
    });

    // ── Open Create Modal ──
    $('#openLoanModalBtn').on('click', function () {
        $('#addLoanForm')[0].reset();
        $('#addLoanForm').attr('action', "{{ route('hr.loans.store') }}");
        $('#addLoanForm .method-put').remove();
        $('.select2-loan').val('').trigger('change');
        selectLoanType('salary_deduction');
        triggerPreview();
        
        $('#addLoanModal .modal-title').html('<i class="fa fa-hand-holding-usd me-2"></i> New Loan Request');
        $('#addLoanModal button[type="submit"]').html('<i class="fa fa-paper-plane me-1"></i> Submit Request');
        
        $('#addLoanModal').modal('show');
    });

    // ── Open Edit Modal ──
    window.editLoan = function(loan) {
        $('#addLoanForm')[0].reset();
        $('#addLoanForm').attr('action', `/hr/loans/${loan.id}`);
        
        if ($('#addLoanForm .method-put').length === 0) {
            $('#addLoanForm').append('<input type="hidden" name="_method" value="PUT" class="method-put">');
        }

        $('.select2-loan').val(loan.employee_id).trigger('change');
        $('#loanAmount').val(loan.amount);
        $('[name="reason"]').val(loan.reason || '');
        $('[name="notes"]').val(loan.notes || '');

        selectLoanType(loan.loan_type);

        if (loan.loan_type === 'salary_deduction') {
            $('#startMonthInput').val(loan.start_month);
            if (loan.total_installments && loan.total_installments > 0) {
                setRepaymentMode('months');
                $('#numMonths').val(loan.total_installments);
            } else if (loan.installment_amount && loan.installment_amount > 0) {
                setRepaymentMode('amount');
                $('#installmentAmountInput').val(loan.installment_amount);
            }
        }
        
        triggerPreview();
        $('#addLoanModal').modal('show');
    };
});

// ── Loan Type Selector ──
function selectLoanType(type) {
    $('#loanTypeInput').val(type);
    if (type === 'salary_deduction') {
        $('#opt_salary').addClass('selected');
        $('#opt_self').removeClass('selected');
        $('#salaryDeductionFields, #startMonthField').show();
        $('#selfPaidFields').hide();
    } else {
        $('#opt_self').addClass('selected');
        $('#opt_salary').removeClass('selected');
        $('#salaryDeductionFields, #startMonthField').hide();
        $('#selfPaidFields').show();
        $('#installmentPreview').hide();
    }
}

// ── Repayment Mode Toggle ──
function setRepaymentMode(mode) {
    if (mode === 'months') {
        $('#byMonthsBtn').addClass('active btn-outline-primary').removeClass('btn-outline-secondary');
        $('#byAmountBtn').removeClass('active btn-outline-primary').addClass('btn-outline-secondary');
        $('#numMonthsField').show();
        $('#monthlyAmountField').hide();
        $('[name="installment_amount"]').val('');
    } else {
        $('#byAmountBtn').addClass('active btn-outline-primary').removeClass('btn-outline-secondary');
        $('#byMonthsBtn').removeClass('active btn-outline-primary').addClass('btn-outline-secondary');
        $('#monthlyAmountField').show();
        $('#numMonthsField').hide();
        $('[name="num_months"]').val('');
    }
    triggerPreview();
}

// ── Live Installment Preview ──
let previewTimer = null;
function triggerPreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(doPreview, 100);
}

function doPreview() {
    const amount     = parseFloat($('#loanAmount').val()) || 0;
    const loanType   = $('#loanTypeInput').val();
    let months       = parseInt($('#numMonths').val()) || 0;
    let monthly      = parseFloat($('#installmentAmountInput').val()) || 0;

    if (loanType !== 'salary_deduction' || amount <= 0) { $('#installmentPreview').slideUp(200); return; }

    const isByMonths = $('#byMonthsBtn').hasClass('active');
    if (isByMonths) {
        if (months <= 0) { $('#installmentPreview').slideUp(200); return; }
        monthly = amount / months;
    } else {
        if (monthly <= 0) { $('#installmentPreview').slideUp(200); return; }
        months = Math.ceil(amount / monthly);
    }

    const startMonthVal = $('#startMonthInput').val();
    let startD = startMonthVal ? new Date(startMonthVal + '-01') : new Date();
    const endD = new Date(startD);
    endD.setMonth(endD.getMonth() + (months - 1));

    const mNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const preview = $('#installmentPreview');

    $('#prev_monthly, #prev_monthly_label').text('Rs. ' + Math.round(monthly).toLocaleString());
    $('#prev_months, #prev_months_label').text(months);
    $('#prev_end').text(mNames[endD.getMonth()] + ' ' + endD.getFullYear());
    $('#prev_start').text(mNames[startD.getMonth()] + ' ' + startD.getFullYear());

        $.post(`/hr/loans/${id}/reject`, { _token: '{{ csrf_token() }}' })
        .done(res => Swal.fire('Rejected!', res.success, 'success').then(() => location.reload()));
    }});
}

function deleteLoan(id) {
    Swal.fire({ title: 'Delete Loan Record?', text: 'This removes all history.', icon: 'error',
        showCancelButton: true, confirmButtonText: 'Yes, Delete', confirmButtonColor: '#ef4444' })
    .then(r => { if (r.isConfirmed) {
        $.ajax({ url: `/hr/loans/${id}`, type: 'DELETE', data: { _token: '{{ csrf_token() }}' } })
        .done(res => Swal.fire('Deleted!', res.success, 'success').then(() => location.reload()));
    }});
}

// ── Schedule Deduction ──
function scheduleDeductionModal(id, remaining) {
    $('#schedule_loan_id').val(id);
    $('#max_sched_amount').text(parseFloat(remaining).toLocaleString());
    $('#schedule_amount').attr('max', remaining);
    $('#scheduleModal').modal('show');
}

// ── Record Payment ──
function recordPaymentModal(id, remaining) {
    $('#rp_loan_id').val(id);
    $('#rp_remaining').text(parseFloat(remaining).toLocaleString());
    $('#rp_amount').attr('max', remaining).val('');
    $('#recordPaymentModal').modal('show');
}

// ── View Full Loan Details ──
function viewLoanDetails(id) {
    $('#ld_emp_name').text('Loading...');
    $('#ld_payment_timeline').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
    $('#loanDetailsModal').modal('show');

    $.get(`/hr/loans/${id}/history`, function (d) {
        // Header
        const typeBadge = d.loan_type === 'salary_deduction'
            ? '<span class="badge bg-primary">Salary Deduction</span>'
            : '<span class="badge bg-warning text-dark">Self-Paid</span>';
        $('#ld_emp_name').text(d.employee.name);
        $('#ld_subtitle').html(`${d.employee.designation} &nbsp;|&nbsp; ${typeBadge} &nbsp;|&nbsp; <span class="badge bg-${d.status === 'approved' ? 'success' : (d.status === 'paid' ? 'info' : 'secondary')}">${d.status.toUpperCase()}</span>`);

        // Stats Bar
        const fmt = v => `Rs. ${parseFloat(v).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        $('#ld_total').text(fmt(d.amount));
        $('#ld_paid').text(fmt(d.paid_amount));
        $('#ld_remaining').text(fmt(d.remaining_amount));
        $('#ld_installment').text(d.loan_type === 'salary_deduction' ? fmt(d.installment_amount) : 'N/A');
        $('#ld_inst_count').text(d.loan_type === 'salary_deduction' ? `${d.remaining_installments} / ${d.total_installments || '?'}` : 'N/A');

        // Progress
        const pct = d.progress_percentage;
        $('#ld_progress_bar').css('width', pct + '%');
        $('#ld_progress_pct').text(pct + '%');
        $('#ld_start_label').text(d.start_month ? 'Start: ' + d.start_month : '');
        $('#ld_end_label').text(d.expected_end_month ? 'End: ' + d.expected_end_month : '');

        // Payment Timeline
        const timeline = $('#ld_payment_timeline');
        timeline.empty();
        if (d.payments && d.payments.length > 0) {
            d.payments.forEach(p => {
                const dotClass = p.source === 'payroll_auto' ? 'payroll' : 'manual';
                timeline.append(`
                    <div class="timeline-item">
                        <div class="timeline-dot ${dotClass}"></div>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="fw-600">${p.payment_date}</span>
                                <span class="badge bg-light text-dark border ms-2" style="font-size:0.65rem;">${p.type_label}</span>
                                ${p.source === 'payroll_auto' ? '<span class="badge bg-primary ms-1" style="font-size:0.6rem;">Auto</span>' : ''}
                                ${p.reference ? `<br><small class="text-muted">Ref: ${p.reference}</small>` : ''}
                                ${p.notes ? `<br><small class="text-muted">${p.notes}</small>` : ''}
                            </div>
                            <span class="fw-800 text-success">+ Rs. ${parseFloat(p.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                        </div>
                    </div>
                `);
            });
        } else {
            timeline.html('<div class="text-center text-muted py-3"><i class="fa fa-inbox fa-2x mb-2 d-block"></i>No payments recorded yet.</div>');
        }

        // Schedule
        const schedEl = $('#ld_schedule_content');
        if (d.loan_type === 'salary_deduction' && d.total_installments) {
            let rows = '';
            d.scheduled_deductions.forEach(s => {
                const badge = s.status === 'deducted' ? 'success' : (s.status === 'skipped' ? 'secondary' : 'warning');
                rows += `<tr>
                    <td>${s.deduction_month}</td>
                    <td><span class="badge bg-${badge}">${s.status}</span></td>
                    <td class="text-end fw-bold">Rs. ${parseFloat(s.amount).toLocaleString()}</td>
                    <td class="small text-muted">${s.notes || '—'}</td>
                </tr>`;
            });
            schedEl.html(`<div class="alert alert-info bg-info-subtle border-0 text-info-emphasis mb-3">
                <i class="fa fa-calendar me-1"></i>
                <strong>${d.total_installments} installments</strong> of Rs. ${parseFloat(d.installment_amount).toLocaleString()} each.
                Starting <strong>${d.start_month || '—'}</strong> through <strong>${d.expected_end_month || '—'}</strong>.
            </div>
            ${rows ? `<table class="table table-sm"><thead class="text-muted"><tr><th>Month</th><th>Status</th><th class="text-end">Amount</th><th>Notes</th></tr></thead><tbody>${rows}</tbody></table>` : '<p class="text-muted text-center">No scheduled deductions recorded yet.</p>'}`);
        } else {
            schedEl.html('<div class="alert alert-secondary border-0 text-center">This is a self-paid loan. No installment schedule.</div>');
        }

        // Info Tab
        $('#ld_info_content').html(`
            <div class="col-md-6"><div class="card border-0 bg-white shadow-sm p-3"><p class="text-muted mb-1" style="font-size:0.7rem;">REASON</p><p class="mb-0">${d.reason || '—'}</p></div></div>
            <div class="col-md-6"><div class="card border-0 bg-white shadow-sm p-3"><p class="text-muted mb-1" style="font-size:0.7rem;">NOTES</p><p class="mb-0">${d.notes || '—'}</p></div></div>
            <div class="col-md-4"><div class="card border-0 bg-white shadow-sm p-3"><p class="text-muted mb-1" style="font-size:0.7rem;">APPROVED ON</p><p class="mb-0 fw-bold">${d.approved_at || '—'}</p></div></div>
            <div class="col-md-4"><div class="card border-0 bg-white shadow-sm p-3"><p class="text-muted mb-1" style="font-size:0.7rem;">DISBURSED ON</p><p class="mb-0 fw-bold">${d.disbursed_at || '—'}</p></div></div>
            <div class="col-md-4"><div class="card border-0 bg-white shadow-sm p-3"><p class="text-muted mb-1" style="font-size:0.7rem;">EXPECTED COMPLETION</p><p class="mb-0 fw-bold">${d.expected_end_month || '—'}</p></div></div>
        `);
    });
}
</script>
@endsection
