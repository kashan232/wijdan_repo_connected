@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        .loan-progress { height: 4px; border-radius: 4px; background: #e2e8f0; overflow: hidden; }
        .loan-progress-fill { height: 100%; border-radius: 4px; transition: width 0.4s ease; }

        .payroll-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--hr-border);
            padding-bottom: 0;
        }

        .payroll-tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            color: var(--hr-text-light);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            bottom: -2px;
        }

        .payroll-tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }

        .payroll-tab:hover {
            color: var(--hr-text);
        }

        .payroll-card {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 12px;
            transition: all 0.2s ease-in-out;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            min-height: 280px;
        }

        .payroll-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #4b5563;
            /* Default gray for unknown */
        }

        .payroll-card.monthly::before {
            background: #2563eb;
        }

        /* Solid Blue */
        .payroll-card.daily::before {
            background: #059669;
        }

        /* Solid Green */
        .payroll-card.commission::before {
            background: #d97706;
        }

        /* Solid Orange */

        .payroll-card:hover {
            border-color: #9ca3af;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .payroll-type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .payroll-type-badge.monthly {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .payroll-type-badge.daily {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .payroll-type-badge.commission {
            background: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        .salary-display {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
            margin-top: 10px;
        }

        .salary-display .amount {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 2px;
            color: #0f172a;
        }

        .salary-display .label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .breakdown-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 0.8rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .breakdown-row:last-child {
            border-bottom: none;
        }

        .breakdown-row .label {
            color: #475569;
        }

        .breakdown-row .value {
            font-weight: 600;
            color: var(--hr-text);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .status-badge.generated {
            background: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        .status-badge.reviewed {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .status-badge.paid {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .month-badge {
            background: #f8fafc;
            padding: 2px 8px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 0.75rem;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .payroll-actions {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .payroll-actions .btn {
            flex: 1;
            min-width: 45%;
            padding: 4px;
            font-size: 0.75rem;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-transform: uppercase;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 16px;
        }

        .modal-detail-section {
            margin-bottom: 24px;
        }

        .modal-detail-section h6 {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row .label {
            color: #475569;
            font-size: 0.85rem;
        }

        .detail-row .value {
            font-weight: 600;
            color: #0f172a;
            font-size: 0.85rem;
        }

        .detail-row.total {
            font-size: 1rem;
            padding-top: 10px;
            margin-top: 8px;
            border-top: 2px solid #e2e8f0;
        }

        /* Modern Payroll UI Overhaul (ERP Style) */
        :root {
            --modern-primary: #2563eb;
            --modern-success: #059669;
            --modern-danger: #dc2626;
            --modern-warning: #d97706;
            --modern-text: #1e293b;
            --modern-text-light: #64748b;
            --modern-bg: #f8fafc;
            --modern-card-bg: #ffffff;
            --modern-border: #cbd5e1;
        }

        #payrollGrid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
        }

        @media (max-width: 1400px) {
            #payrollGrid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 992px) {
            #payrollGrid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            #payrollGrid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            #payrollGrid {
                grid-template-columns: 1fr;
            }
        }

        .net-payable {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            margin-top: 20px;
        }

        .net-payable .label {
            font-size: 0.85rem;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            color: #15803d;
        }

        .net-payable .amount {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        /* Modern Expandable Sections */
        .expandable-section {
            border: 1px solid var(--modern-border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .expandable-section:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
            border-color: #cbd5e1;
        }

        .expandable-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: white;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
        }

        .expandable-header:hover {
            background: #f8fafc;
        }

        .expandable-header.active {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }

        .expandable-header.active .expand-icon {
            transform: rotate(180deg);
            color: white;
        }

        .expandable-header.active .expandable-value {
            color: white;
        }

        .expandable-header.active .detail-item-label {
            color: rgba(255, 255, 255, 0.9);
        }

        .expandable-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 1rem;
        }

        .expandable-title i {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .expandable-value {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--modern-text);
        }

        .expand-icon {
            font-size: 0.9rem;
            transition: transform 0.3s ease, color 0.2s ease;
            color: var(--modern-text-light);
            background: rgba(0, 0, 0, 0.05);
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .expandable-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), padding 0.3s ease;
            background: #f8fafc;
            padding: 0 20px;
        }

        .expandable-content.active {
            max-height: 1000px;
            padding: 20px;
            border-top: 1px solid var(--modern-border);
        }

        /* Scrollable Attendance Details */
        .attendance-details-scroll {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .attendance-details-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .attendance-details-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .attendance-details-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .attendance-details-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .section-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid var(--modern-border);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            color: var(--modern-text);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .section-header i {
            color: var(--modern-primary);
            font-size: 1.1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: white;
            border-radius: 8px;
            margin-bottom: 8px;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .detail-item:hover {
            border-color: var(--modern-border);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-item-label {
            color: var(--modern-text-light);
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-item-value {
            font-weight: 600;
            color: var(--modern-text);
            font-size: 1rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed var(--modern-border);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row.total {
            background: #f0fdf4;
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
            border: 1px solid #bbf7d0;
        }

        .detail-row.total .label {
            font-weight: 700;
            color: #166534;
        }

        .detail-row.total .value {
            font-size: 1.2rem;
            font-weight: 800;
            color: #15803d;
        }

        .detail-row.total-deduction {
            background: #fef2f2;
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
            border: 1px solid #fecaca;
        }

        .detail-row.total-deduction .label {
            font-weight: 700;
            color: #991b1b;
        }

        .detail-row.total-deduction .value {
            font-size: 1.2rem;
            font-weight: 800;
            color: #b91c1c;
        }

        .no-data-message {
            text-align: center;
            padding: 30px;
            color: var(--modern-text-light);
            font-style: italic;
            font-size: 0.9rem;
            background: white;
            border-radius: 8px;
        }

        .period-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 24px;
            box-shadow: 0 8px 20px -6px rgba(99, 102, 241, 0.4);
        }

        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-box {
            background: white;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid var(--modern-border);
        }

        .stat-box .count {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 4px;
            display: block;
        }

        .stat-box .text {
            font-size: 0.75rem;
            color: var(--modern-text-light);
            text-transform: uppercase;
            font-weight: 600;
        }

        .period-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .attendance-stat {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            border-left: 3px solid #6366f1;
        }

        .attendance-stat-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }

        .stat-highlight {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
            color: #92400e;
        }

        .stat-highlight.danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        .stat-highlight.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        /* Professional ERP Modal styles */
        .fw-800 { font-weight: 800; }
        .fw-700 { font-weight: 700; }
        .text-slate-300 { color: #cbd5e1; }
        .text-slate-400 { color: #94a3b8; }
        .text-slate-500 { color: #64748b; }
        .text-slate-600 { color: #475569; }
        .text-slate-700 { color: #334155; }
        .text-slate-800 { color: #1e293b; }
        .text-slate-900 { color: #0f172a; }
        .bg-slate-50 { background-color: #f8fafc; }
        .bg-slate-100 { background-color: #f1f5f9; }
        .bg-slate-900 { background-color: #0f172a; }
        .text-blue-600 { color: #2563eb; }
        .bg-blue-50 { background-color: #eff6ff; }
        .bg-blue-100 { background-color: #dbeafe; }
        .text-blue-700 { color: #1d4ed8; }
        .text-emerald-400 { color: #34d399; }
        .text-emerald-600 { color: #059669; }
        .bg-emerald-600 { background-color: #059669; }
        .text-red-400 { color: #f87171; }
        .text-red-600 { color: #dc2626; }
        .bg-red-50 { background-color: #fef2f2; }
        .text-red-700 { color: #b91c1c; }
        
        .erp-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
        }
        
        .erp-value {
            font-weight: 700;
            font-size: 0.95rem;
        }
        
        .erp-description {
            font-size: 0.68rem;
            color: #94a3b8;
            line-height: 1.25;
            margin-top: 2px;
        }
        
        .erp-mini-desc {
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 2px 6px;
        }
        
        .shadow-xs { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .tracking-wider { letter-spacing: 0.05em; }
        
        .attendance-stats-box {
            border: 1px dashed #cbd5e1;
            background-color: #f8fafc;
        }

        .net-payable-card {
            background: linear-gradient(135deg, #1e293b, #0f172a);
        }
        
        .shadow-emerald-200 {
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);
        }
        
        #erp_absent_days_list .absent-day-item {
            background: #fff;
            border: 1px solid #fee2e2;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div
                    class="page-header d-flex justify-content-between align-items-center bg-white p-4 rounded-3 shadow-sm mb-4 border-start border-4 border-primary">
                    <div>
                        <h1 class="page-title text-primary fw-bold mb-1" style="font-size:1.6rem;"><i
                                class="fa fa-money-bill-wave me-2"></i> Payroll Management</h1>
                        <p class="page-subtitle text-muted mb-0">Manage monthly and daily employee payroll</p>
                    </div>
                    <div class="d-flex gap-2">
                        @can('hr.payroll.create')
                            <div class="dropdown">
                                <button class="btn btn-primary px-4 py-2 dropdown-toggle shadow-sm rounded-pill fw-bold"
                                    type="button" id="generateDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="background: linear-gradient(135deg, #6366f1, #4f46e5); border: none;">
                                    <i class="fa fa-plus-circle me-1"></i> Generate Payroll
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                                    aria-labelledby="generateDropdown" style="border-radius: 12px; padding: 8px;">
                                    <li>
                                        <a class="dropdown-item py-2 px-3 rounded-2 fw-bold text-primary mb-1 custom-dropdown-item"
                                            href="javascript:void(0)" id="generateMonthlyBtn">
                                            <i class="fa fa-calendar-alt me-2"></i> Generate Monthly Payroll
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 px-3 rounded-2 fw-bold text-success mb-1 custom-dropdown-item"
                                            href="javascript:void(0)" id="generateDailyBtn">
                                            <i class="fa fa-calendar-day me-2"></i> Generate Daily Payroll
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider my-1">
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 px-3 rounded-2 fw-bold text-warning custom-dropdown-item"
                                            href="javascript:void(0)" id="generateBtn">
                                            <i class="fa fa-hand-holding-usd me-2"></i> Manual / Single Entry
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        @endcan
                    </div>
                </div>

                <style>
                    .custom-dropdown-item {
                        transition: all 0.2s ease;
                    }

                    .custom-dropdown-item:hover {
                        background: #f8fafc;
                        transform: translateX(4px);
                    }
                </style>

                <!-- Stats Row -->
                @php
                    $generatedCount = \App\Models\Hr\Payroll::where('status', 'generated')->count();
                    $reviewedCount = \App\Models\Hr\Payroll::where('status', 'reviewed')->count();
                    $paidCount = \App\Models\Hr\Payroll::where('status', 'paid')->count();
                    $totalNet = \App\Models\Hr\Payroll::sum('net_salary');
                    $monthlyCount = \App\Models\Hr\Payroll::monthly()->count();
                    $dailyCount = \App\Models\Hr\Payroll::daily()->count();
                    $commissionCount = \App\Models\Hr\Payroll::where('payroll_type', 'commission')->count();
                    $allCount = $monthlyCount + $dailyCount + $commissionCount;
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-file-invoice-dollar"></i></div>
                        <div class="stat-value">{{ $payrolls->total() }}</div>
                        <div class="stat-label">Total Payrolls</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-clock"></i></div>
                        <div class="stat-value">{{ $generatedCount }}</div>
                        <div class="stat-label">Generated</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-eye"></i></div>
                        <div class="stat-value">{{ $reviewedCount }}</div>
                        <div class="stat-label">Reviewed</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                        <div class="stat-value">{{ $paidCount }}</div>
                        <div class="stat-label">Paid</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-coins"></i></div>
                        <div class="stat-value">{{ number_format($totalNet, 0) }}</div>
                        <div class="stat-label">Total Amount</div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="payroll-tabs">
                    <a href="{{ route('hr.payroll.index') }}"
                        class="payroll-tab {{ ($activeTab ?? 'all') === 'all' ? 'active' : '' }}">
                        <i class="fa fa-list"></i> All Payrolls ({{ $allCount }})
                    </a>
                    <a href="{{ route('hr.payroll.monthly') }}"
                        class="payroll-tab {{ ($activeTab ?? '') === 'monthly' ? 'active' : '' }}">
                        <i class="fa fa-calendar-alt"></i> Monthly ({{ $monthlyCount }})
                    </a>
                    <a href="{{ route('hr.payroll.daily') }}"
                        class="payroll-tab {{ ($activeTab ?? '') === 'daily' ? 'active' : '' }}">
                        <i class="fa fa-calendar-day"></i> Daily ({{ $dailyCount }})
                    </a>
                    <a href="{{ route('hr.payroll.index', ['type' => 'commission']) }}"
                        class="payroll-tab {{ ($activeTab ?? '') === 'commission' ? 'active' : '' }}">
                        <i class="fa fa-money-bill-wave"></i> Commission ({{ $commissionCount }})
                    </a>
                </div>

                <!-- Payrolls Card -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="payrollSearch" placeholder="Search by employee name...">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm active" data-status="all">All</button>
                                <button class="btn btn-outline-warning btn-sm" data-status="generated">Generated</button>
                                <button class="btn btn-outline-info btn-sm" data-status="reviewed">Reviewed</button>
                                <button class="btn btn-outline-success btn-sm" data-status="paid">Paid</button>
                            </div>
                        </div>
                        <span class="text-muted small" id="payrollCount">{{ $payrolls->total() }} payrolls</span>
                    </div>

                    <div class="hr-grid" id="payrollGrid">
                        @forelse($payrolls as $payroll)
                            <div class="payroll-card {{ $payroll->payroll_type }}" data-id="{{ $payroll->id }}"
                                data-name="{{ strtolower($payroll->employee->full_name ?? '') }}"
                                data-status="{{ $payroll->status }}" data-type="{{ $payroll->payroll_type }}">

                                <!-- Header: Avatar + Simple Text -->
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <div class="hr-avatar rounded shrink-0"
                                        style="width: 32px; height: 32px; min-width: 32px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.8rem; background: {{ $payroll->payroll_type === 'monthly' ? '#2563eb' : ($payroll->payroll_type === 'commission' ? '#d97706' : '#059669') }};">
                                        {{ strtoupper(substr($payroll->employee->first_name ?? 'U', 0, 1) . substr($payroll->employee->last_name ?? 'N', 0, 1)) }}
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden" style="line-height: 1.2;">
                                        <h6 class="mb-0 text-truncate font-weight-bold"
                                            style="font-size: 0.85rem; color: #1e293b;">
                                            {{ $payroll->employee->full_name ?? 'Unknown' }}
                                        </h6>
                                        <div class="text-truncate text-muted" style="font-size: 0.7rem;">
                                            {{ $payroll->employee->designation->name ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Middle: Money & Month -->
                                <div class="bg-light rounded p-2 mb-2 border">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-uppercase text-muted font-weight-bold"
                                            style="font-size: 0.65rem; letter-spacing: 0.3px;">Net Payable</span>
                                        <span class="text-muted font-weight-bold"
                                            style="font-size: 0.65rem;">{{ $payroll->month }}</span>
                                    </div>
                                    <div class="font-weight-bold"
                                        style="font-size: 1.2rem; color: #0f172a; line-height: 1;">
                                        Rs. {{ number_format($payroll->net_salary, 2) }}
                                    </div>
                                    @if ($payroll->payroll_type === 'monthly' && $payroll->commission > 0)
                                        <div class="text-success font-weight-bold mt-1" style="font-size: 0.70rem; line-height: 1;">
                                            + Rs. {{ number_format($payroll->commission, 2) }} Comm
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="payroll-type-badge {{ $payroll->payroll_type }}"
                                            style="font-size: 0.6rem; padding: 1px 4px;">{{ ucfirst($payroll->payroll_type) }}</span>
                                        <span class="status-badge {{ $payroll->status }}"
                                            style="font-size: 0.6rem; padding: 1px 4px;">{{ ucfirst($payroll->status) }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="mt-auto border-top pt-2 payroll-actions mb-0">
                                    @can('hr.payroll.view')
                                        <button class="btn btn-outline-secondary view-details-btn" title="Details"
                                            data-id="{{ $payroll->id }}">
                                            <i class="fa fa-eye"></i> View
                                        </button>
                                    @endcan

                                    @if ($payroll->canEdit() && auth()->user()->can('hr.payroll.edit'))
                                        <button class="btn btn-outline-primary edit-payroll-btn" title="Edit"
                                            data-id="{{ $payroll->id }}">
                                            <i class="fa fa-edit"></i> Edit
                                        </button>
                                    @endif

                                    @if ($payroll->canMarkReviewed() && auth()->user()->can('hr.payroll.edit'))
                                        <button class="btn btn-outline-info mark-reviewed-btn" title="Mark Reviewed"
                                            data-id="{{ $payroll->id }}">
                                            <i class="fa fa-check"></i> Review
                                        </button>
                                    @endif

                                    @if ($payroll->canMarkPaid() && auth()->user()->can('hr.payroll.edit'))
                                        <button class="btn btn-outline-success mark-paid-btn" title="Mark Paid"
                                            data-id="{{ $payroll->id }}"
                                            data-name="{{ $payroll->employee->full_name }}"
                                            data-month="{{ \Carbon\Carbon::parse($payroll->month)->format('F Y') }}"
                                            data-net="{{ number_format($payroll->net_salary, 2) }}"
                                            data-base="{{ number_format($payroll->basic_salary + $payroll->allowances + $payroll->commission, 2) }}">
                                            <i class="fa fa-check-double"></i> Pay
                                        </button>
                                    @endif

                                    @if ($payroll->status !== 'paid' && auth()->user()->can('hr.payroll.delete'))
                                        <button class="btn btn-outline-danger delete-btn" title="Delete"
                                            data-id="{{ $payroll->id }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-money-bill-wave"></i>
                                <p>No payrolls generated yet.</p>
                                <p class="text-muted small">Click "Generate Payroll" to create payroll entries.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="px-4 py-3 border-top">
                        {{ $payrolls->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Payroll Modal -->
    <div class="modal fade" id="generatePayrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;">
                    <h5 class="modal-title">
                        <i class="fa fa-plus"></i>
                        <span>Generate Payroll</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="generatePayrollForm" action="{{ route('hr.payroll.generate') }}" method="POST"
                    data-ajax-validate="true">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-bookmark"></i> Payroll Type</label>
                            <select name="payroll_type" class="form-select" id="payrollTypeSelect">
                                <option value="">Select Type</option>
                                <option value="monthly">Monthly</option>
                                <option value="daily">Daily</option>
                            </select>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-user"></i> Employee</label>
                            <select name="employee_id" class="form-select">
                                <option value="">Select Employee</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group-modern" id="monthField">
                            <label class="form-label"><i class="fa fa-calendar"></i> Month</label>
                            <input type="month" name="month" class="form-control">
                        </div>
                        <div class="form-group-modern" id="dateField" style="display: none;">
                            <label class="form-label"><i class="fa fa-calendar-day"></i> Date</label>
                            <input type="date" name="date" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save"
                            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                            <i class="fa fa-check"></i>
                            <span>Generate</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Generate Daily Payrolls Modal -->
    <div class="modal fade" id="generateDailyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #10b981, #059669) !important;">
                    <h5 class="modal-title">
                        <i class="fa fa-calendar-day"></i>
                        <span>Generate Daily Payrolls</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="generateDailyForm" action="{{ route('hr.payroll.generate-daily') }}" method="POST"
                    data-ajax-validate="true">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            This will generate payroll for all active daily-wage employees for the selected date.
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-calendar-day"></i> Date</label>
                            <input type="date" name="date" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save"
                            style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fa fa-check"></i>
                            <span>Generate All</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Interactive Monthly Payrolls Modal -->
    <!-- Interactive Monthly Payrolls Modal -->
    <div class="modal fade" id="generateMonthlyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #10b981, #059669) !important;">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-calendar-alt me-2"></i>
                        <span>Generate Monthly Payrolls</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="finalGenerateMonthlyForm" action="{{ route('hr.payroll.generate-monthly') }}" method="POST"
                    data-ajax-validate="true">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info border-0 shadow-sm rounded-3">
                            <i class="fa fa-info-circle me-1"></i>
                            Select a month to generate payroll for all eligible employees. This will calculate salaries,
                            commissions, and attendance deductions based on the set rules.
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label fw-bold"><i class="fa fa-calendar text-primary"></i> Select
                                Month</label>
                            <input type="month" name="month" class="form-control form-control-lg shadow-sm">
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-success px-4"
                            style="background: linear-gradient(135deg, #10b981, #059669); border:none; border-radius:8px;">
                            <i class="fa fa-check me-1"></i> Generate All
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Professional ERP Payroll Payment Modal -->
    <div class="modal fade" id="payrollPayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 20px;">
                <!-- Modern Gradient Header -->
                <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #1e293b, #334155); color: white;">
                    <div>
                        <h4 class="modal-title fw-800 mb-1" style="letter-spacing: -0.02em;">Process Payroll Payment</h4>
                        <p class="mb-0 text-slate-300 small opacity-75">Review employee earnings and deductions before confirming payment</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white opacity-50" data-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="payrollPayForm" action="" method="POST" data-ajax-validate="true">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="payment_type" id="erp_payment_type" value="net">
                    
                    <div class="modal-body p-0 bg-light">
                        <!-- Employee Info Banner -->
                        <div class="bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div id="erp_emp_avatar" class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" 
                                     style="width: 54px; height: 54px; background: #6366f1; font-size: 1.2rem;">JS</div>
                                <div>
                                    <h5 id="erp_emp_name" class="mb-0 fw-800 text-slate-900">Janzaib Shahbaz</h5>
                                    <span id="erp_emp_designation" class="text-slate-500 small">Senior System Architect</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div id="erp_payroll_month" class="badge bg-slate-100 text-slate-700 px-3 py-2 rounded-pill fw-bold border mb-1">March 2026</div>
                                <div id="erp_payroll_type" class="text-slate-400 small text-uppercase fw-800" style="letter-spacing: 0.1em; font-size: 0.65rem;">Monthly Payroll</div>
                            </div>
                        </div>

                        <div class="row g-0">
                            <!-- Left Section: Earnings -->
                            <div class="col-md-6 border-end bg-white">
                                <div class="p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-blue-50 text-blue-600 rounded-pill p-2">
                                            <i class="fa fa-wallet"></i>
                                        </div>
                                        <h6 class="mb-0 fw-700 text-slate-80 slate-uppercase" style="font-size: 0.8rem; letter-spacing: 0.05em;">Earnings Analysis</h6>
                                    </div>

                                    <div class="vstack gap-3" id="erp_earnings_list">
                                        <!-- Monthly Earnings -->
                                        <div class="erp-field-group">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="erp-label">Monthly Earnings</label>
                                                <span class="erp-value text-slate-900" id="erp_monthly_earnings">Rs. 0.00</span>
                                            </div>
                                            <div class="erp-description">Base salary or primary wage for the current cycle</div>
                                        </div>

                                        <!-- Earning Type (Conditional) -->
                                        <div class="erp-field-group d-none" id="erp_earning_type_container" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="erp-label" id="erp_earning_type_label">Commission Earned</label>
                                                <span class="erp-value text-blue-600" id="erp_earning_type_value">Rs. 0.00</span>
                                            </div>
                                            
                                            <!-- Commission Metrics Mini Card -->
                                            <div id="erp_commission_metrics_box" class="mt-2 p-2 bg-blue-50/50 rounded-3 border border-blue-100 d-none" style="display: none;">
                                                <div class="row g-2 text-center" style="font-size: 0.65rem;">
                                                    <div class="col-3 border-end">
                                                        <div class="text-slate-400 text-uppercase fw-700 mb-1" title="Total Sale Amount">Sale Amt.</div>
                                                        <div id="erp_sale_amount" class="fw-800 text-slate-700">Rs. 0</div>
                                                    </div>
                                                    <div class="col-3 border-end">
                                                        <div class="text-slate-400 text-uppercase fw-700 mb-1" title="Total Commission">Max Comm.</div>
                                                        <div id="erp_comm_total" class="fw-800 text-slate-700">Rs. 0</div>
                                                    </div>
                                                    <div class="col-3 border-end">
                                                        <div class="text-slate-400 text-uppercase fw-700 mb-1" title="Already Paid to Employee">Emp. Paid</div>
                                                        <div id="erp_comm_paid" class="fw-800 text-slate-700">Rs. 0</div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="text-slate-400 text-uppercase fw-700 mb-1" title="Remaining to Pay Employee">Remaining</div>
                                                        <div id="erp_comm_remaining" class="fw-800 text-blue-600">Rs. 0</div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center opacity-75" style="font-size: 0.6rem;">
                                                    <span class="text-slate-500 font-monospace" id="erp_comm_customer_ratio">Customer Paid: 0%</span>
                                                    <span class="text-slate-500 fw-600" id="erp_comm_customer_name">Customer: N/A</span>
                                                </div>

                                                <!-- Breakdown of individual commissions (Invoices) -->
                                                <div id="erp_commission_items_list" class="mt-2 pt-2 border-top d-none">
                                                    <div class="text-slate-400 text-uppercase fw-700 mb-1" style="font-size: 0.6rem;">Commission Breakdown</div>
                                                    <ul class="list-unstyled mb-0" style="font-size: 0.65rem;"></ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Allowances -->
                                        <div class="erp-field-group">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="erp-label">Allowances</label>
                                                <span class="erp-value text-slate-900" id="erp_total_allowances">Rs. 0.00</span>
                                            </div>
                                            <div class="erp-description">Total fixed and recurring allowances for the month</div>
                                        </div>

                                        <!-- Attendance Pattern Stats -->
                                        <div class="attendance-stats-box bg-slate-50 p-3 rounded-4 border border-dashed mt-2">
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <label class="erp-label d-block mb-1 text-slate-500">Shift Hours</label>
                                                    <span class="fw-700 text-slate-800" id="erp_shift_hours">00.0h</span>
                                                </div>
                                                <div class="col-6">
                                                    <label class="erp-label d-block mb-1 text-slate-500">Worked Hours</label>
                                                    <span class="fw-700 text-slate-800" id="erp_worked_hours">00.0h</span>
                                                </div>
                                                <div class="col-12 border-top pt-2">
                                                    <label class="erp-label d-block mb-1">Attendance Time Pattern</label>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-800 font-monospace text-blue-600" style="font-size: 1.1rem;" id="erp_attendance_pattern">00 | 00 | 00</span>
                                                        <span class="badge bg-blue-100 text-blue-700 erp-mini-desc">Shift | Worked | Late</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <label class="erp-label d-block mb-1">Part Time & Early Pattern</label>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-800 font-monospace text-slate-700" id="erp_extra_hours_pattern">00 | 00 | 00</span>
                                                        <span class="badge bg-slate-200 text-slate-600 erp-mini-desc">Total | Worked | Extra</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bonus Input -->
                                        <div class="erp-input-group mt-3">
                                            <label class="erp-label mb-2 d-block text-success fw-700">Bonus / Extra Payment</label>
                                            <div class="input-group input-group-sm shadow-sm border rounded-3 overflow-hidden">
                                                <span class="input-group-text bg-white border-0 text-success">Rs.</span>
                                                <input type="number" name="manual_allowances" id="erp_bonus_input" 
                                                       class="form-control border-0 bg-white" placeholder="0.00" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Section: Deductions -->
                            <div class="col-md-6 bg-slate-50/50">
                                <div class="p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-red-50 text-red-600 rounded-pill p-2">
                                            <i class="fa fa-minus-circle"></i>
                                        </div>
                                        <h6 class="mb-0 fw-700 text-slate-800 text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.05em;">Deduction Control</h6>
                                    </div>

                                    <div class="vstack gap-3">
                                        <!-- Loan Deduction -->
                                        <div class="erp-field-group" id="erp_loan_container">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="erp-label">Loan Deduction This Month</label>
                                                <span class="erp-value text-red-600" id="erp_loan_deduction">Rs. 0.00</span>
                                            </div>
                                            <!-- Rich Loan Info Card (shown only when loan exists) -->
                                            <div id="erp_loan_info_card" class="d-none mt-2 p-2 rounded-3 border" style="background: #fff7ed; border-color: #fed7aa; font-size: 0.65rem;">
                                                <div class="row g-1 text-center mb-2">
                                                    <div class="col-4 border-end">
                                                        <div class="text-slate-400 fw-700 text-uppercase">Total Loan</div>
                                                        <div class="fw-800 text-slate-700" id="erp_li_total">—</div>
                                                    </div>
                                                    <div class="col-4 border-end">
                                                        <div class="text-slate-400 fw-700 text-uppercase">Outstanding</div>
                                                        <div class="fw-800 text-danger" id="erp_li_remaining">—</div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="text-slate-400 fw-700 text-uppercase">Installments Left</div>
                                                        <div class="fw-800 text-orange-600" id="erp_li_inst_left">—</div>
                                                    </div>
                                                </div>
                                                <div class="border-top pt-1 d-flex justify-content-between align-items-center">
                                                    <span class="text-slate-400" id="erp_li_progress_label">0% repaid</span>
                                                    <div class="loan-progress flex-grow-1 mx-2" style="height: 4px;">
                                                        <div class="loan-progress-fill" id="erp_li_progress_bar" style="width: 0%; background: #f59e0b;"></div>
                                                    </div>
                                                    <span class="text-slate-500 fw-600" id="erp_li_end_month"></span>
                                                </div>
                                            </div>
                                            <div class="erp-description" id="erp_loan_desc">No active salary-deduction loan found</div>
                                        </div>

                                        <!-- Absent Days -->
                                        <div class="erp-field-group">
                                            <label class="erp-label d-block mb-2">Absent Days Timeline</label>
                                            <div id="erp_absent_days_list" class="vstack gap-1 px-1" style="max-height: 160px; overflow-y: auto; scrollbar-width: thin;">
                                                <div class="text-center py-2 bg-white rounded border border-dashed text-slate-400 small">
                                                    No absent records found
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Attendance Summary Pattern -->
                                        <div class="erp-field-group mt-2">
                                            <label class="erp-label d-block mb-1">Attendance Summary Pattern</label>
                                            <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded border shadow-xs">
                                                <span class="fw-800 font-monospace text-red-600" style="font-size: 1.1rem;" id="erp_attendance_summary_pattern">00 | 00 | 00</span>
                                                <div class="text-end text-slate-400" style="font-size: 0.6rem; line-height: 1;">
                                                    <div>ASSIGNED | WORKED</div>
                                                    <div>LATE + EARLY</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Manual Deduction -->
                                        <div class="erp-input-group mt-4">
                                            <label class="erp-label mb-2 d-block text-red-700 fw-700">Manual Deduction</label>
                                            <div class="input-group input-group-sm shadow-sm border rounded-3 overflow-hidden">
                                                <span class="input-group-text bg-white border-0 text-red-600">Rs.</span>
                                                <input type="number" name="manual_deductions" id="erp_manual_deduction_input" 
                                                       class="form-control border-0 bg-white" placeholder="0.00" step="0.01">
                                            </div>
                                        </div>

                                        <!-- Summary Section in Side Column -->
                                        <div class="mt-auto pt-4">
                                            <div class="net-payable-card bg-slate-900 text-white rounded-4 p-4 shadow-lg border border-slate-700">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="small text-slate-400">Total Earnings</span>
                                                    <span class="fw-600" id="erp_total_gross">Rs. 0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom border-slate-700">
                                                    <span class="small text-slate-400">Total Deductions</span>
                                                    <span class="fw-600 text-red-400" id="erp_total_deductions">Rs. 0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="small fw-700 text-uppercase tracking-wider">Net Payable</div>
                                                    <div class="fs-4 fw-800 text-emerald-400" id="erp_net_payable">Rs. 0.00</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer with Confirmation -->
                    <div class="modal-footer bg-white border-0 p-4 pt-0 gap-3">
                        <button type="button" class="btn btn-slate-100 fw-700 px-4 py-3 rounded-3 text-slate-700 border-0" data-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-emerald-600 text-white fw-700 px-4 py-3 rounded-4 border-0 flex-grow-1 shadow-emerald-200"
                                style="background: linear-gradient(135deg, #059669, #10b981); box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);">
                            <i class="fa fa-check-double me-2"></i>Confirm & Process Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #6366f1, #4f46e5) !important;">
                    <h5 class="modal-title">
                        <i class="fa fa-file-invoice"></i>
                        <span>Payroll Details</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Payroll Modal -->
    <div class="modal fade" id="editPayrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #f59e0b, #d97706) !important;">
                    <h5 class="modal-title">
                        <i class="fa fa-edit"></i>
                        <span>Edit Payroll</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editPayrollForm" method="POST" data-ajax-validate="true">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-plus-circle"></i> Manual Allowances</label>
                            <input type="number" name="manual_allowances" class="form-control" step="0.01"
                                min="0" value="0">
                            <small class="text-muted">Additional allowances not in salary structure</small>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-minus-circle"></i> Manual Deductions</label>
                            <input type="number" name="manual_deductions" class="form-control" step="0.01"
                                min="0" value="0">
                            <small class="text-muted">Additional deductions not in salary structure</small>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-sticky-note"></i> Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Add notes or comments..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save"
                            style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fa fa-save"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Tab switching
            $('.payroll-tab').click(function() {
                $(this).addClass('active').siblings().removeClass('active');
                var tab = $(this).data('tab');

                $('.payroll-card').each(function() {
                    if (tab === 'all') {
                        $(this).show();
                    } else {
                        $(this).toggle($(this).data('type') === tab);
                    }
                });
                updateCount();
            });

            // Status filter
            $('[data-status]').click(function() {
                $(this).addClass('active').siblings().removeClass('active');
                var status = $(this).data('status');

                $('.payroll-card').each(function() {
                    if (status === 'all') {
                        $(this).show();
                    } else {
                        $(this).toggle($(this).data('status') === status);
                    }
                });
                updateCount();
            });

            // Search
            $('#payrollSearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('.payroll-card').each(function() {
                    var name = $(this).data('name') || '';
                    $(this).toggle(name.indexOf(q) !== -1);
                });
                updateCount();
            });

            function updateCount() {
                $('#payrollCount').text($('.payroll-card:visible').length + ' payrolls');
            }

            // Generate payroll modal
            $('#generateBtn').click(function() {
                $('#generatePayrollForm')[0].reset();
                $('#generatePayrollModal').modal('show');
            });

            // Generate monthly modal
            $('#generateMonthlyBtn').click(function() {
                $('#generateMonthlyModal').find('form')[0].reset();
                $('#generateMonthlyModal').modal('show');
            });

            // Generate daily modal
            $('#generateDailyBtn').click(function() {
                $('#generateDailyForm')[0].reset();
                $('#generateDailyModal').modal('show');
            });

            // Payroll type change
            $('#payrollTypeSelect').change(function() {
                var type = $(this).val();
                if (type === 'daily') {
                    $('#monthField').hide().find('input').val(''); // Clear month
                    $('#dateField').show();
                } else if (type === 'monthly') {
                    $('#monthField').show();
                    $('#dateField').hide().find('input').val(''); // Clear date
                } else {
                    $('#monthField').hide();
                    $('#dateField').hide();
                }
            });

            // View details
            $(document).on('click', '.view-details-btn', function() {
                var id = $(this).data('id');
                $('#detailsModal').modal('show');

                $.ajax({
                    url: '/hr/payroll/' + id + '/details',
                    type: 'GET',
                    success: function(response) {
                        renderDetails(response);
                    },
                    error: function() {
                        $('#detailsContent').html(
                            '<div class="alert alert-danger">Failed to load details.</div>');
                    }
                });
            });

            function renderDetails(data) {
                // Compact Header with Period & Employee
                var headerHtml = `
                    <div class="d-flex align-items-center justify-content-between mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center gap-3">
                            <div style="background: #e0e7ff; color: #4338ca; width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                ${data.payroll.employee.first_name.charAt(0)}${data.payroll.employee.last_name.charAt(0)}
                            </div>
                            <div>
                                <h6 style="margin:0; font-weight:700;">${data.payroll.employee.first_name} ${data.payroll.employee.last_name}</h6>
                                <div class="text-muted small">${data.payroll.employee.designation ? data.payroll.employee.designation.name : 'N/A'}</div>
                            </div>
                        </div>
                        <div class="text-end d-flex gap-2">
                             <div class="badge bg-white text-dark border px-3 py-2 d-flex align-items-center" style="font-weight: 600;">
                                <i class="fa fa-tag me-2 text-muted"></i>
                                ${data.payroll.payroll_type.charAt(0).toUpperCase() + data.payroll.payroll_type.slice(1)} Payroll
                            </div>
                             <div class="period-badge mb-0 py-1 px-3" style="font-size: 0.85rem;">
                                <i class="fa fa-calendar-alt me-1"></i> ${data.payroll_period.formatted}
                            </div>
                        </div>
                    </div>
                `;

                var html = `
                    ${headerHtml}
                    
                    <div class="row g-3">
                        ${data.payroll.payroll_type === 'commission' && data.payroll.sale ? `
                                                                            <div class="col-12">
                                                                                <div class="section-card h-100 mb-0 shadow-sm border-0 apple-glow">
                                                                                    <div class="section-header mb-3 py-2 text-warning border-warning border-opacity-25" style="border-bottom-width: 2px;">
                                                                                        <div class="d-flex align-items-center">
                                                                                            <div class="icon-box bg-warning-light text-warning me-2">
                                                                                                <i class="fa fa-shopping-cart"></i>
                                                                                            </div>
                                                                                            <span class="fw-bold">Sale & Commission Details</span>
                                                                                        </div>
                                                                                    </div>
                                                                                    
                                                                                    <div class="row">
                                                                                        <div class="col-md-6 border-end">
                                                                                            <div class="detail-row py-2">
                                                                                                <span class="label">Invoice Number</span>
                                                                                                <span class="value text-primary fw-bold">#${data.payroll.sale.invoice_no}</span>
                                                                                            </div>
                                                                                            <div class="detail-row py-2">
                                                                                                <span class="label">Customer Name</span>
                                                                                                <span class="value fw-bold">${data.payroll.sale.customer_relation ? data.payroll.sale.customer_relation.customer_name : 'Walk-in'}</span>
                                                                                            </div>
                                                                                            <div class="detail-row py-2">
                                                                                                <span class="label">Customer Phone</span>
                                                                                                <span class="value">${data.payroll.sale.customer_relation ? data.payroll.sale.customer_relation.mobile : 'N/A'}</span>
                                                                                            </div>
                                                                                            <div class="detail-row py-2">
                                                                                                <span class="label">Total Bill Amount</span>
                                                                                                <span class="value fw-bold text-dark">Rs. ${parseFloat(data.payroll.sale.total_net).toFixed(2)}</span>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-md-6 ps-md-4">
                                                                                             <div class="detail-row py-2">
                                                                                                <span class="label">Max Commission for Sale</span>
                                                                                                <span class="value text-success fw-bold">Rs. ${parseFloat(data.payroll.sale.total_commission).toFixed(2)}</span>
                                                                                            </div>
                                                                                            <div class="detail-row py-2">
                                                                                                <span class="label text-muted">Commission Already Paid</span>
                                                                                                <span class="value text-muted">Rs. ${parseFloat(data.payroll.sale.commission_paid).toFixed(2)}</span>
                                                                                            </div>
                                                                                            <div class="detail-row py-3 mt-3 bg-light rounded text-center border-dashed">
                                                                                                <div class="label text-warning fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">REMAINING COMMISSION BALANCE</div>
                                                                                                <div class="value text-warning fs-4 fw-bold">Rs. ${parseFloat(data.payroll.sale.total_commission - data.payroll.sale.commission_paid).toFixed(2)}</div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="detail-row total mt-4 p-3 bg-green-50 rounded-3 border-green-200 d-flex justify-content-between align-items-center">
                                                                                        <div class="d-flex flex-column">
                                                                                            <span class="label text-success fw-bold">This Payment Amount</span>
                                                                                            <small class="text-success-muted">Commission disbursed in this transaction</small>
                                                                                        </div>
                                                                                        <span class="value text-success fs-4 fw-bold">Rs. ${parseFloat(data.payroll.net_salary).toFixed(2)}</span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        ` : `
                                                                            <!-- Left Column: Earnings -->
                                                                            <div class="col-md-6">
                                                                                <div class="section-card h-100 mb-0">
                                                                                    <div class="section-header mb-3 py-2 text-primary border-primary border-opacity-25" style="border-bottom-width: 2px;">
                                                                                        <i class="fa fa-wallet"></i> Earnings
                                                                                        <span class="badge bg-primary ms-2" style="font-size: 0.7rem; font-weight: 500;">
                                                                                            ${data.structure_info.use_daily_wages ? 'Daily Wage' : 
                                                                                              data.structure_info.salary_type === 'commission' ? 'Commission Only' :
                                                                                              data.structure_info.salary_type === 'both' ? 'Salary + Commission' : 'Salary'}
                                                                                        </span>
                                                                                    </div>
                                                                                    
                                                                                    ${data.structure_info.use_daily_wages ? `
                                        <!-- Daily Wage Structure -->
                                        <div class="detail-row py-2">
                                            <div class="d-flex flex-column">
                                                <span class="label">Daily Wage Rate</span>
                                                <small class="text-muted" style="font-size: 0.75rem;">Rs. ${parseFloat(data.structure_info.daily_wages).toFixed(2)} per day</small>
                                            </div>
                                            <span class="value fw-bold">Rs. ${parseFloat(data.breakdown.earnings.basic_salary).toFixed(2)}</span>
                                        </div>
                                    ` : `
                                        <!-- Monthly Salary Structure -->
                                        ${data.breakdown.earnings.basic_salary > 0 ? `
                                                                                            <div class="detail-row py-2">
                                                                                                <div class="d-flex flex-column">
                                                                                                    <span class="label">${data.structure_info.salary_type === 'commission' ? 'Base Amount' : 'Basic Salary'}</span>
                                                                                                    ${data.structure_info.base_salary > 0 ? `<small class="text-muted" style="font-size: 0.75rem;">Monthly: Rs. ${parseFloat(data.structure_info.base_salary).toFixed(2)}</small>` : ''}
                                                                                                </div>
                                                                                                <span class="value fw-bold">Rs. ${parseFloat(data.breakdown.earnings.basic_salary).toFixed(2)}</span>
                                                                                            </div>
                                                                                        ` : ''}
                                    `}

                                                                                    ${(data.breakdown.earnings.commission > 0 || data.structure_info.salary_type === 'commission' || data.structure_info.salary_type === 'both') ? `
                                        <div class="detail-row py-2 ${data.structure_info.salary_type === 'commission' ? 'bg-light' : ''}">
                                            <div class="d-flex flex-column">
                                                <span class="label ${data.structure_info.salary_type === 'commission' ? 'fw-bold' : ''}">Sales Commission</span>
                                                ${data.commission_details && data.commission_details.length > 0 ? 
                                                    `<small class="text-muted" style="font-size: 0.75rem;">${(data.commission_details[0].meta && data.commission_details[0].meta.text_desc) ? data.commission_details[0].meta.text_desc : (data.commission_details[0].name || '')}</small>` 
                                                    : data.structure_info.commission_percentage > 0 ? 
                                                    `<small class="text-muted" style="font-size: 0.75rem;">${data.structure_info.commission_percentage}% of sales</small>` : ''}
                                            </div>
                                            <span class="value fw-bold ${data.structure_info.salary_type === 'commission' ? 'text-success' : ''}">Rs. ${parseFloat(data.breakdown.earnings.commission || 0).toFixed(2)}</span>
                                        </div>
                                    ` : ''}
                                                                                    
                                                                                    <div class="expandable-section allowances-section my-2 shadow-sm border-0 bg-light">
                                                                                        <div class="expandable-header py-2 px-3" onclick="toggleExpandable(this)" style="background: transparent;">
                                                                                            <div class="expandable-title small">Allowances</div>
                                                                                            <div class="d-flex align-items-center gap-2">
                                                                                                <span class="expandable-value small">Rs. ${parseFloat(data.breakdown.earnings.allowances).toFixed(2)}</span>
                                                                                                <i class="fa fa-chevron-down expand-icon" style="font-size: 0.7rem;"></i>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="expandable-content">
                                                                                            ${data.allowance_details.length > 0 ? 
                                                                                                data.allowance_details.map(allowance => `
                                                <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                                    <small class="text-muted">${allowance.name}</small>
                                                    <small class="fw-bold">Rs. ${parseFloat(allowance.amount).toFixed(2)}</small>
                                                </div>
                                            `).join('') 
                                                                                                : '<div class="text-center small text-muted py-1">- None -</div>'}
                                                                                        </div>
                                                                                    </div>
                                                                                    
                                                                                    ${data.breakdown.earnings.manual_allowances > 0 ? `
                                        <div class="detail-row py-2">
                                            <span class="label">Manual Allowances</span>
                                            <span class="value">Rs. ${parseFloat(data.breakdown.earnings.manual_allowances).toFixed(2)}</span>
                                        </div>
                                    ` : ''}
                                                                                    <div class="detail-row total mt-auto bg-green-50 border-green-200">
                                                                                        <span class="label text-success">Total Earnings</span>
                                                                                        <span class="value text-success">Rs. ${parseFloat(data.breakdown.earnings.total).toFixed(2)}</span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <!-- Right Column: Deductions -->
                                                                            <div class="col-md-6">
                                                                                <div class="section-card h-100 mb-0">
                                                                                    <div class="section-header mb-3 py-2 text-danger border-danger border-opacity-25" style="border-bottom-width: 2px;">
                                                                                        <i class="fa fa-file-invoice-dollar"></i> Deductions
                                                                                    </div>

                                                                                    <div class="detail-row py-2">
                                                                                        <span class="label">Fixed Deductions</span>
                                                                                        <span class="value fw-bold">Rs. ${parseFloat(data.breakdown.deductions.fixed_deductions).toFixed(2)}</span>
                                                                                    </div>

                                                                                    <div class="expandable-section attendance-section my-2 shadow-sm border-0 bg-light">
                                                                                        <div class="expandable-header py-2 px-3" onclick="toggleExpandable(this)" style="background: transparent;">
                                                                                            <div class="expandable-title small">Attendance Deductions</div>
                                                                                            <div class="d-flex align-items-center gap-2">
                                                                                                <span class="expandable-value small">Rs. ${parseFloat(data.breakdown.deductions.attendance_deductions).toFixed(2)}</span>
                                                                                                <i class="fa fa-chevron-down expand-icon" style="font-size: 0.7rem;"></i>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="expandable-content">
                                                                                            ${data.payroll.payroll_type === 'daily' ? `
                                                <!-- Daily Payroll View -->
                                                <div class="py-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                                        <span class="text-muted small fw-bold text-uppercase">Total Deduction</span>
                                                        <span class="text-danger fw-bold fs-6">Rs. ${parseFloat(data.attendance_breakdown.total_deduction || 0).toFixed(2)}</span>
                                                    </div>
                                                    
                                                    ${data.attendance_breakdown.has_data ? `
                                                                                                        <div class="d-flex justify-content-between gap-3">
                                                                                                            <div class="text-center p-2 rounded bg-white border ${data.attendance_breakdown.is_late ? 'border-warning apple-glow-warning' : 'border-light'} flex-fill">
                                                                                                                <div class="small text-muted mb-1">Check In</div>
                                                                                                                ${data.attendance_breakdown.late_deduction_amount > 0 ? `
                                                                    <div class="text-danger fw-bold small mb-1">-Rs. ${parseFloat(data.attendance_breakdown.late_deduction_amount).toFixed(2)}</div>
                                                                ` : ''}
                                                                                                                <div class="fw-bold ${data.attendance_breakdown.is_late ? 'text-warning' : 'text-dark'}">
                                                                                                                    ${data.attendance_breakdown.check_in || '--:--'}
                                                                                                                </div>
                                                                                                                ${data.attendance_breakdown.is_late ? `
                                                                    <div class="badge bg-warning text-dark mt-1" style="font-size: 0.7rem;">Late (${data.attendance_breakdown.late_minutes}m)</div>
                                                                ` : ''}
                                                                                                            </div>
                                                                                                            
                                                                                                            <div class="text-center p-2 rounded bg-white border ${data.attendance_breakdown.is_early_out ? 'border-info apple-glow-info' : 'border-light'} flex-fill">
                                                                                                                <div class="small text-muted mb-1">Check Out</div>
                                                                                                                ${data.attendance_breakdown.early_deduction_amount > 0 ? `
                                                                    <div class="text-danger fw-bold small mb-1">-Rs. ${parseFloat(data.attendance_breakdown.early_deduction_amount).toFixed(2)}</div>
                                                                ` : ''}
                                                                                                                <div class="fw-bold ${data.attendance_breakdown.is_early_out ? 'text-info' : 'text-dark'}">
                                                                                                                    ${data.attendance_breakdown.check_out || '--:--'}
                                                                                                                </div>
                                                                                                                ${data.attendance_breakdown.is_early_out ? `
                                                                    <div class="badge bg-info text-white mt-1" style="font-size: 0.7rem;">Early (${data.attendance_breakdown.early_checkout_minutes}m)</div>
                                                                ` : ''}
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    ` : `
                                                                                                        <div class="text-center text-muted small py-2">
                                                                                                            <i class="fa fa-exclamation-circle"></i> No attendance record
                                                                                                        </div>
                                                                                                    `}
                                                </div>
                                            ` : `
                                                <!-- Monthly Payroll View -->
                                                <!-- Summary Badges -->
                                                <div class="d-flex flex-wrap gap-2 justify-content-center py-2 border-bottom mb-2">
                                                     <span class="badge bg-white text-muted border border-light shadow-sm">
                                                        Present: <b class="text-success">${data.attendance_breakdown.days_present || 0}</b>
                                                     </span>
                                                     <span class="badge bg-white text-muted border border-light shadow-sm">
                                                        Absent: <b class="text-danger">${data.attendance_breakdown.days_absent || 0}</b>
                                                     </span>
                                                     ${data.attendance_breakdown.days_paid_leave > 0 ? `
                                                                                                     <span class="badge bg-white text-muted border border-light shadow-sm">
                                                                                                        Paid Leave: <b class="text-primary">${data.attendance_breakdown.days_paid_leave}</b>
                                                                                                     </span>` : ''}
                                                     <span class="badge bg-white text-muted border border-light shadow-sm">
                                                        Late: <b class="text-warning">${data.attendance_breakdown.late_check_ins || 0}</b>
                                                     </span>
                                                     <span class="badge bg-white text-muted border border-light shadow-sm">
                                                        Early Out: <b class="text-info">${data.attendance_breakdown.early_check_outs || 0}</b>
                                                     </span>
                                                </div>
                                                
                                                ${!data.attendance_breakdown.has_data ? `
                                                                                                    <div class="alert alert-warning py-2 mb-0 small text-center">
                                                                                                        <i class="fa fa-exclamation-triangle me-1"></i>
                                                                                                        ${data.attendance_breakdown.data_message || 'Attendance data incomplete for this period'}
                                                                                                    </div>
                                                                                                ` : `
                                                                                                    <!-- Detailed Records with Scroll -->
                                                                                                    <div class="attendance-details-scroll" style="max-height: 200px; overflow-y: auto;">
                                                                                                        
                                                                                                        ${(data.attendance_breakdown.absent_records && data.attendance_breakdown.absent_records.length > 0) ? `
                                                            <div class="mb-3">
                                                                <div class="small fw-bold text-danger mb-2 px-2">
                                                                    <i class="fa fa-times-circle me-1"></i> Absent Days (${data.attendance_breakdown.absent_records.length})
                                                                </div>
                                                                ${data.attendance_breakdown.absent_records.map(record => `
                                                                                                                    <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom" style="font-size: 0.8rem;">
                                                                                                                        <div>
                                                                                                                            <span class="text-muted">${record.date}</span>
                                                                                                                            <span class="badge bg-light text-muted ms-1">${record.day}</span>
                                                                                                                            ${record.note ? `<span class="badge bg-danger ms-1">${record.note}</span>` : ''}
                                                                                                                        </div>
                                                                                                                        <span class="text-danger fw-bold">-Rs. ${parseFloat(record.deduction).toFixed(2)}</span>
                                                                                                                    </div>
                                                                                                                `).join('')}
                                                            </div>
                                                        ` : ''}
                                                                                                        
                                                                                                        ${(data.attendance_breakdown.paid_leave_records && data.attendance_breakdown.paid_leave_records.length > 0) ? `
                                                            <div class="mb-3">
                                                                <div class="small fw-bold text-primary mb-2 px-2">
                                                                    <i class="fa fa-bed me-1"></i> Paid Leaves (${data.attendance_breakdown.paid_leave_records.length})
                                                                </div>
                                                                ${data.attendance_breakdown.paid_leave_records.map(record => `
                                                                                                                    <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom" style="font-size: 0.8rem;">
                                                                                                                        <div>
                                                                                                                            <span class="text-muted">${record.date}</span>
                                                                                                                            <span class="badge bg-light text-muted ms-1">${record.day}</span>
                                                                                                                        </div>
                                                                                                                        <span class="badge bg-primary">${record.type}</span>
                                                                                                                    </div>
                                                                                                                `).join('')}
                                                            </div>
                                                        ` : ''}
                                                                                                        
                                                                                                        ${(data.attendance_breakdown.late_records && data.attendance_breakdown.late_records.length > 0) ? `
                                                            <div class="mb-3">
                                                                <div class="small fw-bold text-warning mb-2 px-2">
                                                                    <i class="fa fa-clock me-1"></i> Late Check-ins (${data.attendance_breakdown.late_records.length})
                                                                </div>
                                                                ${data.attendance_breakdown.late_records.map(record => `
                                                                                                                    <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom" style="font-size: 0.8rem;">
                                                                                                                        <div>
                                                                                                                            <span class="text-muted">${record.date}</span>
                                                                                                                            <span class="badge bg-warning text-dark ms-1">${record.check_in}</span>
                                                                                                                            <span class="text-muted small ms-1">(${record.late_minutes} min late)</span>
                                                                                                                        </div>
                                                                                                                        <span class="text-danger fw-bold">-Rs. ${parseFloat(record.deduction).toFixed(2)}</span>
                                                                                                                    </div>
                                                                                                                `).join('')}
                                                            </div>
                                                        ` : ''}
                                                                                                        
                                                                                                        ${(data.attendance_breakdown.early_records && data.attendance_breakdown.early_records.length > 0) ? `
                                                            <div class="mb-2">
                                                                <div class="small fw-bold text-info mb-2 px-2">
                                                                    <i class="fa fa-sign-out-alt me-1"></i> Early Check-outs (${data.attendance_breakdown.early_records.length})
                                                                </div>
                                                                ${data.attendance_breakdown.early_records.map(record => `
                                                                                                                    <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom" style="font-size: 0.8rem;">
                                                                                                                        <div>
                                                                                                                            <span class="text-muted">${record.date}</span>
                                                                                                                            <span class="badge bg-info text-white ms-1">${record.check_out}</span>
                                                                                                                            <span class="text-muted small ms-1">(${record.early_minutes} min early)</span>
                                                                                                                        </div>
                                                                                                                        <span class="text-danger fw-bold">-Rs. ${parseFloat(record.deduction).toFixed(2)}</span>
                                                                                                                    </div>
                                                                                                                `).join('')}
                                                            </div>
                                                        ` : ''}
                                                                                                        
                                                                                                        ${(!data.attendance_breakdown.absent_records?.length && !data.attendance_breakdown.late_records?.length && !data.attendance_breakdown.early_records?.length && !data.attendance_breakdown.paid_leave_records?.length) ? `
                                                            <div class="text-center text-muted py-2 small">
                                                                <i class="fa fa-check-circle text-success me-1"></i> No attendance issues this period
                                                            </div>
                                                        ` : ''}
                                                                                                    </div>
                                                                                                `}
                                            `}
                                                                                        </div>
                                                                                    </div>

                                                                                    ${data.breakdown.deductions.carried_forward > 0 ? `
                                        <div class="detail-row py-2" style="background: #fff1f2; border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; border: 1px dashed #fecaca;">
                                            <div class="d-flex justify-content-between w-100">
                                                <span class="label text-danger small fw-bold">Carried Forward (From Prev)</span>
                                                <span class="value text-danger small fw-bold">Rs. ${parseFloat(data.breakdown.deductions.carried_forward).toFixed(2)}</span>
                                            </div>
                                        </div>
                                    ` : ''}

                                                                                    <div class="detail-row py-2">
                                                                                        <div class="d-flex justify-content-between w-100">
                                                                                            <span class="label small text-muted">Carry Fwd(To Next)</span>
                                                                                            <span class="value text-warning small">Rs. ${parseFloat(data.breakdown.deductions.carried_forward_to_next || 0).toFixed(2)}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="detail-row py-2">
                                                                                        <span class="label">Manual Deductions</span>
                                                                                        <span class="value">Rs. ${parseFloat(data.breakdown.deductions.manual_deductions).toFixed(2)}</span>
                                                                                    </div>
                                                                                    <div class="detail-row total-deduction mt-auto">
                                                                                        <span class="label">Total Deductions</span>
                                                                                        <span class="value">Rs. ${parseFloat(data.breakdown.deductions.total).toFixed(2)}</span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        `}
                    </div>

                    <!-- Net Payable & Footer Notes -->
                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <div class="net-payable py-3 px-4 mt-2 d-flex align-items-center justify-content-between" style="border-radius: 12px;">
                                <div class="text-start">
                                    <div class="label text-white-50 mb-0 small">Net Payable Amount</div>
                                    <div class="small text-white-50" style="font-size: 0.8rem;">${data.payroll.status.toUpperCase()}</div>
                                </div>
                                <div class="amount mb-0" style="font-size: 2rem;">Rs. ${parseFloat(data.breakdown.net_payable).toFixed(2)}</div>
                            </div>
                        </div>
                        ${data.payroll.notes ? `
                                                                            <div class="col-12">
                                                                                <div class="alert alert-warning mb-0 py-2 fs-7 small d-flex align-items-center">
                                                                                    <i class="fa fa-sticky-note me-2 text-warning"></i> 
                                                                                    <span class="fst-italic text-truncate">${data.payroll.notes}</span>
                                                                                </div>
                                                                            </div>
                                                                        ` : ''}
                    </div>
                `;

                $('#detailsContent').html(html);
            }

            // Toggle expandable sections
            window.toggleExpandable = function(header) {
                const content = $(header).next('.expandable-content');
                const isActive = $(header).hasClass('active');

                if (isActive) {
                    $(header).removeClass('active');
                    content.removeClass('active');
                } else {
                    $(header).addClass('active');
                    content.addClass('active');
                }
            };

            // Edit payroll
            $(document).on('click', '.edit-payroll-btn', function() {
                var id = $(this).data('id');
                $('#editPayrollForm').attr('action', '/hr/payroll/' + id);
                $('#editPayrollModal').modal('show');
            });

            // Mark reviewed
            $(document).on('click', '.mark-reviewed-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: '<span style="color:#3b82f6">Verify Payroll?</span>',
                    html: 'By marking this as reviewed, you confirm that the calculations have been checked and are ready for payment.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fa fa-eye me-1"></i> Confirm Review'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updating Status...',
                            html: 'Synchronizing with payroll records.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '/hr/payroll/' + id + '/mark-reviewed',
                            type: 'PATCH',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: 'success',
                                        title: 'Payroll verified successfully!'
                                    }).then(() => location.reload());
                                }
                            },
                            error: function(xhr) {
                                let error = 'Failed to update status.';
                                if (xhr.responseJSON && xhr.responseJSON.error) error =
                                    xhr.responseJSON.error;
                                Swal.fire('Error', error, 'error');
                            }
                        });
                    }
                });
            });

            // Professional ERP Payroll Payment Modal Logic
            let activePayrollData = null;

            $(document).on('click', '.mark-paid-btn', function() {
                const btn = $(this);
                const id = btn.data('id');
                const name = btn.data('name');
                const avatar = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);

                // Show loading state or clear previous
                $('#erp_emp_name').text(name);
                $('#erp_emp_avatar').text(avatar);
                $('#payrollPayForm').attr('action', `/hr/payroll/${id}/mark-paid`);
                $('#erp_bonus_input').val('');
                $('#erp_manual_deduction_input').val('');
                $('#erp_earning_type_container').addClass('d-none').hide();
                $('#erp_commission_metrics_box').addClass('d-none').hide();

                // Load detailed stats via AJAX
                $.get(`/hr/payroll/${id}/details`, function(data) {
                    activePayrollData = data;
                    const p = data.payroll;
                    const stats = data.stats;

                    // Header Info
                    $('#erp_emp_designation').text(p.employee?.designation?.name || 'Employee');
                    $('#erp_payroll_month').text(p.month + ' ' + p.year);
                    $('#erp_payroll_type').text(p.payroll_type + ' Payroll');

                    // Earnings Section
                    $('#erp_monthly_earnings').text(`Rs. ${parseFloat(p.basic_salary).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                    
                    const hasCommDetails = (data.commission_details && data.commission_details.length > 0);
                    const hasCommMetrics = !!data.commission_metrics;
                    
                    let commAmount = p.commission;
                    if (!commAmount && data.breakdown && data.breakdown.earnings) {
                        commAmount = data.breakdown.earnings.commission;
                    }
                    const hasCommAmount = (commAmount && parseFloat(commAmount) > 0);

                    if (p.payroll_type === 'commission' || hasCommAmount || hasCommMetrics || hasCommDetails) {
                        $('#erp_earning_type_container').removeClass('d-none').css('display', 'block');
                        $('#erp_earning_type_label').text('Commission Earned');
                        $('#erp_earning_type_value').text(`Rs. ${parseFloat(commAmount || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                        
                        // Populate Metrics
                        if (data.commission_metrics) {
                            const cm = data.commission_metrics;
                            $('#erp_commission_metrics_box').removeClass('d-none').css('display', 'block');
                            
                            const formatVal = (val) => {
                                if (val === 'N/A' || isNaN(parseFloat(val))) return val;
                                return `Rs. ${parseFloat(val).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
                            };

                            $('#erp_sale_amount').text(formatVal(cm.sale_total));
                            $('#erp_comm_total').text(formatVal(cm.total_commission));
                            $('#erp_comm_paid').text(formatVal(cm.paid_so_far));
                            $('#erp_comm_remaining').text(formatVal(cm.remaining_commission));
                            $('#erp_comm_customer_ratio').text(`Customer Paid: ${formatVal(cm.customer_paid_total)} (${cm.payment_ratio})`);
                            $('#erp_comm_customer_name').text(`Customer: ${cm.customer_name}`);
                        } else if (hasCommDetails) {
                            // Build aggregated metrics from detail rows (monthly with multiple sales)
                            $('#erp_commission_metrics_box').removeClass('d-none').css('display', 'block');
                            let aggSaleTotal = 0, aggMaxComm = 0, aggCustPaid = 0, aggCurrComm = 0, aggRemaining = 0;
                            data.commission_details.forEach(d => {
                                if (d.meta && d.meta.max_commission !== undefined) {
                                    aggSaleTotal   += parseFloat(d.meta.sale_total || 0);
                                    aggMaxComm     += parseFloat(d.meta.max_commission || 0);
                                    aggCustPaid    += parseFloat(d.meta.customer_paid_total || 0);
                                    aggCurrComm    += parseFloat(d.meta.current_commission || 0);
                                    aggRemaining   += parseFloat(d.meta.remaining_commission || 0);
                                } else {
                                    aggCurrComm    += parseFloat(d.amount || 0);
                                }
                            });
                            const Rs = v => `Rs. ${parseFloat(v).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
                            $('#erp_sale_amount').text(Rs(aggSaleTotal));
                            $('#erp_comm_total').text(Rs(aggMaxComm));
                            $('#erp_comm_paid').text(Rs(aggCurrComm));
                            $('#erp_comm_remaining').text(Rs(aggRemaining));
                            $('#erp_comm_customer_ratio').text(`Customer Paid: ${Rs(aggCustPaid)} (${aggSaleTotal > 0 ? Math.round((aggCustPaid/aggSaleTotal)*100) : 0}%)`);
                            $('#erp_comm_customer_name').text(`Multiple Sales (${data.commission_details.length})`);
                        } else {
                            $('#erp_commission_metrics_box').addClass('d-none').hide();
                        }

                        // Populate individual items breakdown
                        const itemsList = $('#erp_commission_items_list');
                        const itemsUl = itemsList.find('ul');
                        itemsUl.empty();
                        if (hasCommDetails) {
                            itemsList.removeClass('d-none').show();
                            data.commission_details.forEach(detail => {
                                let metaHtml = '';
                                if (detail.meta && detail.meta.max_commission !== undefined) {
                                    const saleTotal = parseFloat(detail.meta.sale_total || 0);
                                    const custPaid = parseFloat(detail.meta.customer_paid_total || 0);
                                    const maxComm = parseFloat(detail.meta.max_commission || 0);
                                    const ratio = saleTotal > 0 ? (custPaid / saleTotal) * 100 : 0;
                                    const earnedSoFar = saleTotal > 0 ? (custPaid / saleTotal) * maxComm : 0;
                                    const alreadyPaid = parseFloat(detail.meta.paid_so_far || 0);
                                    const currentComm = parseFloat(detail.meta.current_commission || detail.amount || 0);
                                    const remaining = parseFloat(detail.meta.remaining_commission || 0);

                                    metaHtml = `
                                        <div class="mt-1 pb-1" style="font-size: 0.65rem; background: #f8fafc; border-radius: 4px; padding: 6px;">
                                            <div class="row g-1 text-muted">
                                                <div class="col-6"><strong>Sale Amount:</strong> Rs. ${saleTotal.toLocaleString()}</div>
                                                <div class="col-6"><strong>Max Comm (Total):</strong> Rs. ${maxComm.toLocaleString()}</div>
                                                <div class="col-12 mt-1">
                                                    <strong>Cust Paid So Far:</strong> Rs. ${custPaid.toLocaleString()} 
                                                    <span class="badge bg-secondary ms-1" style="font-size: 0.55rem;">${ratio.toFixed(1)}% of sale</span>
                                                </div>
                                            </div>
                                            <div class="border-top mt-1 pt-1 row g-1">
                                                <div class="col-12 text-primary" style="font-size: 0.6rem;">
                                                    <i class="fa fa-info-circle me-1"></i>
                                                    Earned (${ratio.toFixed(1)}% of max): Rs. ${earnedSoFar.toLocaleString()} 
                                                    - Already Paid: Rs. ${alreadyPaid.toLocaleString()}
                                                </div>
                                                <div class="col-8 mt-1">
                                                    <strong class="text-success">Yield This Month: Rs. ${currentComm.toLocaleString(undefined, {minimumFractionDigits: 2})}</strong>
                                                </div>
                                                <div class="col-4 mt-1 text-end text-warning fw-bold">
                                                    Left: Rs. ${remaining.toLocaleString()}
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                } else if(detail.meta && detail.meta.text_desc) {
                                    metaHtml = `<div class="text-muted mt-1" style="font-size: 0.6rem;">${detail.meta.text_desc}</div>`;
                                } else if (detail.description) {
                                    metaHtml = `<div class="text-muted mt-1" style="font-size: 0.6rem;">${detail.description}</div>`;
                                }
                            
                                itemsUl.append(`
                                    <li class="border-bottom border-light pb-2 mb-2">
                                        <div class="d-flex justify-content-between mb-0">
                                            <span class="text-slate-600 fw-bold">${detail.name}</span>
                                            <span class="fw-700 text-success">Rs. ${parseFloat(detail.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                                        </div>
                                        ${metaHtml}
                                    </li>
                                `);
                            });
                        } else {
                            itemsList.addClass('d-none').hide();
                        }
                    } else {
                        $('#erp_earning_type_container').addClass('d-none').hide();
                        $('#erp_commission_metrics_box').addClass('d-none').hide();
                    }

                    $('#erp_total_allowances').text(`Rs. ${parseFloat(p.allowances).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                    $('#erp_shift_hours').text(`${stats.assigned_hours}h`);
                    $('#erp_worked_hours').text(`${stats.worked_hours}h`);
                    $('#erp_attendance_pattern').text(stats.attendance_time_pattern);
                    $('#erp_extra_hours_pattern').text(stats.extra_time_pattern);

                    // Deductions Section — Loan
                    const loanInfo = data.loan_info;
                    const loanInst = loanInfo ? parseFloat(loanInfo.monthly_installment || 0) : parseFloat(stats.loan_installment || 0);
                    $('#erp_loan_deduction').text(`Rs. ${loanInst.toLocaleString(undefined, {minimumFractionDigits: 2})}`);

                    if (loanInfo && loanInst > 0) {
                        const fmt = v => `Rs. ${parseFloat(v).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
                        $('#erp_li_total').text(fmt(loanInfo.amount));
                        $('#erp_li_remaining').text(fmt(loanInfo.remaining_amount));
                        $('#erp_li_inst_left').text(`${loanInfo.remaining_installments} / ${loanInfo.total_installments || '?'}`);
                        $('#erp_li_progress_bar').css('width', loanInfo.progress_percentage + '%');
                        $('#erp_li_progress_label').text(loanInfo.progress_percentage + '% repaid');
                        $('#erp_li_end_month').text(loanInfo.expected_end_month ? 'Ends ' + loanInfo.expected_end_month : '');
                        $('#erp_loan_info_card').removeClass('d-none');
                        $('#erp_loan_desc').text(`Auto-deducting Rs. ${loanInst.toLocaleString()} this month`);
                    } else {
                        $('#erp_loan_info_card').addClass('d-none');
                        $('#erp_loan_desc').text(loanInst > 0 ? 'Active loan deduction' : 'No active salary-deduction loan');
                    }

                    // Absent Days List
                    const absentContainer = $('#erp_absent_days_list');
                    absentContainer.empty();
                    
                    let records = [];
                    if (data.attendance_breakdown) {
                        if (data.attendance_breakdown.absent_records) {
                            records = data.attendance_breakdown.absent_records;
                        } else if (p.payroll_type === 'daily' && data.attendance_breakdown.status && data.attendance_breakdown.status.toLowerCase() !== 'present') {
                            records = [{
                                date: data.attendance_breakdown.formatted_date,
                                note: data.attendance_breakdown.status,
                                deduction: data.attendance_breakdown.total_deduction || 0
                            }];
                        }
                    }
                    
                    if (records.length > 0) {
                        records.forEach(day => {
                            const deductionText = day.deduction > 0 ? `<span class="text-slate-500 ms-2" style="font-size: 0.75rem;">(-Rs. ${parseFloat(day.deduction).toLocaleString()})</span>` : '';
                            absentContainer.append(`<div class="absent-day-item d-flex justify-content-between align-items-center mb-1">
                                <span><i class="fa fa-calendar-times text-slate-300 me-2"></i>${day.date}</span>
                                <div class="text-end">
                                    <span class="text-red-500 fw-bold small">${day.note || day.status || 'Absent'}</span>
                                    ${deductionText}
                                </div>
                            </div>`);
                        });
                    } else {
                        absentContainer.append('<div class="text-center py-2 bg-white rounded border border-dashed text-slate-400 small">No attendance deductions found</div>');
                    }

                    $('#erp_attendance_summary_pattern').text(stats.attendance_summary_pattern);

                    // Calculations
                    calculateERPTotals();

                    // Show Modal
                    $('#payrollPayModal').modal('show');
                });
            });

            // Dynamic Recalculation
            $('#erp_bonus_input, #erp_manual_deduction_input').on('input', function() {
                calculateERPTotals();
            });

            function calculateERPTotals() {
                if (!activePayrollData) return;

                const p = activePayrollData.payroll;
                const bonus = parseFloat($('#erp_bonus_input').val()) || 0;
                const manualDeduction = parseFloat($('#erp_manual_deduction_input').val()) || 0;

                let comm = p.commission || 0;
                if (!comm && activePayrollData.breakdown && activePayrollData.breakdown.earnings) {
                    comm = activePayrollData.breakdown.earnings.commission || 0;
                }

                const totalGross = parseFloat(p.basic_salary) + parseFloat(p.allowances) + parseFloat(comm) + bonus;
                const totalDeductions = parseFloat(p.deductions) + parseFloat(p.attendance_deductions) + parseFloat(p.carried_forward_deduction) + manualDeduction;
                const netPayable = Math.max(0, totalGross - totalDeductions);

                $('#erp_total_gross').text(`Rs. ${totalGross.toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                $('#erp_total_deductions').text(`Rs. ${totalDeductions.toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                $('#erp_net_payable').text(`Rs. ${netPayable.toLocaleString(undefined, {minimumFractionDigits: 2})}`);
            }

            // Form Submission with Confirmation
            $('#payrollPayForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const net = $('#erp_net_payable').text();

                Swal.fire({
                    title: '<span class="text-emerald-600">Final Confirmation</span>',
                    html: `You are about to record a payment of <b>${net}</b>.<br>This will finalize the payroll for this month.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Proceed with Payment'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize(),
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Success', response.success, 'success').then(() => location.reload());
                                }
                            },
                            error: function(xhr) {
                                let error = 'Failed to process payment.';
                                if (xhr.responseJSON && xhr.responseJSON.error) error = xhr.responseJSON.error;
                                Swal.fire('Error', error, 'error');
                            }
                        });
                    }
                });
            });

            // Delete payroll
            $(document).on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: '<span style="color:#ef4444">Remove Record?</span>',
                    html: 'You are about to permanently delete this payroll record.<br><b>This action cannot be undone!</b>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fa fa-trash me-1"></i> Confirm Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Removing...',
                            html: 'Clearing records from the database.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '/hr/payroll/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: 'success',
                                        title: 'Record successfully deleted!'
                                    }).then(() => location.reload());
                                }
                            },
                            error: function(xhr) {
                                let error = 'Failed to delete record.';
                                if (xhr.responseJSON && xhr.responseJSON.error) error =
                                    xhr.responseJSON.error;
                                Swal.fire('Error', error, 'error');
                            }
                        });
                    }
                });
            });

            // Generic AJAX form submission - handles JSON responses for generate modals
            $('form[data-ajax-validate="true"]').submit(function(e) {
                e.preventDefault();
                var form = $(this);
                var isGenerate = form.attr('id').includes('Generate');

                var submitBtn = form.find('button[type="submit"]');
                var originalHtml = submitBtn.html();

                // Disable button and show spinner
                submitBtn.prop('disabled', true).html(
                    '<i class="fa fa-spinner fa-spin me-2"></i>Processing...');

                Swal.fire({
                    title: isGenerate ? 'Generating Records...' : 'Processing Transaction...',
                    html: isGenerate ?
                        'Please wait while we perform complex salary calculations and synchronize attendance data. This might take a moment.' :
                        'Validating and recording transaction details in our secure ledger.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        form.closest('.modal').modal('hide');
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '<span style="color:#059669">Success!</span>',
                                html: response.success,
                                showConfirmButton: true,
                                confirmButtonText: 'Great, Continue',
                                confirmButtonColor: '#10b981'
                            }).then(() => {
                                if (response.reload) location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Oops! Something went wrong while processing.';
                        var errorTitle = 'Operation Failed';

                        if (xhr.status === 422 && xhr.responseJSON.errors) {
                            errorTitle = 'Check Input Data';
                            errorMessage = '<ul class="text-start mb-0">';
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                errorMessage += `<li>${value[0]}</li>`;
                            });
                            errorMessage += '</ul>';
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors && xhr
                            .responseJSON.errors.general) {
                            errorMessage = xhr.responseJSON.errors.general[0];
                        }

                        Swal.fire({
                            icon: 'error',
                            title: `<span style="color:#dc2626">${errorTitle}</span>`,
                            html: `<div class="p-2">${errorMessage}</div>`,
                            confirmButtonColor: '#dc2626',
                            confirmButtonText: 'Try Again'
                        });
                    },
                    complete: function() {
                        // Restore button state
                        submitBtn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });
    </script>
@endsection
