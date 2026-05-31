@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        .report-card {
            background: var(--hr-card);
            border: 1px solid var(--hr-border);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .report-table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--hr-border);
            margin-top: 20px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .report-table th, .report-table td {
            border: 1px solid #e2e8f0;
            padding: 12px 10px;
            text-align: center;
            vertical-align: middle;
        }

        .report-table th {
            background-color: #f8fafc;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
            border-bottom: 2px solid #cbd5e1;
        }

        .report-table .emp-col {
            text-align: left;
            position: sticky;
            left: 0;
            background: white;
            z-index: 10;
            min-width: 280px;
            border-right: 3px solid #e2e8f0;
            padding-left: 20px !important;
        }

        .department-row {
            background: #f8fafc !important;
        }

        .dept-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.1rem;
            font-weight: 800;
            color: #4f46e5;
            padding: 10px 0;
        }

        .dept-title .icon-box {
            width: 32px;
            height: 32px;
            background: #eef2ff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .designation-row {
            background: #ffffff !important;
        }

        .desig-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            color: #64748b;
            padding: 4px 10px;
            border-left: 3px solid #cbd5e1;
            margin-left: 10px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 5px;
            font-weight: 900;
            font-size: 0.7rem;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.12);
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
            margin: auto;
            cursor: pointer;
            position: relative;
        }

        /* Custom Premium CSS Tooltip */
        .status-badge[data-tooltip]:not([data-tooltip=""]):hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            opacity: 0;
            animation: tooltipFadeIn 0.2s forwards;
            pointer-events: none;
        }

        .status-badge[data-tooltip]:not([data-tooltip=""]):hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: #1e293b transparent transparent transparent;
            z-index: 1000;
            opacity: 0;
            animation: tooltipFadeIn 0.2s forwards;
            pointer-events: none;
        }

        @keyframes tooltipFadeIn {
            from { opacity: 0; transform: translateX(-50%) translateY(5px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }

        .status-P { background: linear-gradient(135deg, #22c55e, #16a34a); border: 1px solid #15803d; }
        .status-A { background: linear-gradient(135deg, #ef4444, #dc2626); border: 1px solid #b91c1c; }
        .status-L { background: linear-gradient(135deg, #f59e0b, #d97706); border: 1px solid #b45309; }
        .status-LV { background: linear-gradient(135deg, #3b82f6, #2563eb); border: 1px solid #1d4ed8; }
        .status-H { background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: 1px solid #6d28d9; }
        .status-WO { background: linear-gradient(135deg, #94a3b8, #64748b); border: 1px solid #475569; }

        .total-col {
            min-width: 50px;
            text-align: center;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            min-width: 32px;
            display: inline-block;
        }

        .bg-success-soft { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .bg-danger-soft { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .bg-warning-soft { background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .bg-info-soft { background-color: #f0f9ff; color: #075985; border: 1px solid #bae6fd; }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-top: 30px;
            padding: 16px 24px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            width: fit-content;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .day-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }

        .day-num { font-size: 1rem; font-weight: 800; }
        .day-name { font-size: 0.6rem; font-weight: 600; opacity: 0.7; }

        .sunday { background-color: #fff1f2 !important; }

        .today-col {
            background-color: #f0f7ff !important; /* Extremely light blue for column */
            border-left: 2px solid #3b82f6 !important;
            border-right: 2px solid #3b82f6 !important;
        }

        th.today-col {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
        }
        
        th.today-col .day-name {
            color: #ffffff !important;
            opacity: 1;
        }
            box-shadow: inset 0 -2px 0 rgba(0,0,0,0.1);
        }
        
        .designation-header td {
            border-top: none !important;
        }
        .select2-container--bootstrap4 .select2-selection {
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            height: 42px !important;
            display: flex !important;
            align-items: center !important;
        }
        
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 40px !important;
            padding-left: 12px !important;
            color: #475569 !important;
            font-weight: 500 !important;
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }

        .select2-container--bootstrap4 .select2-dropdown {
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
            z-index: 9999 !important;
        }

        .select2-container--bootstrap4 .select2-search--dropdown .select2-search__field {
            border: 1px solid #e2e8f0 !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            margin-bottom: 8px !important;
        }

        .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
        }

        .filter-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
            display: block;
        }

        .filter-input {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            height: 42px;
            padding: 8px 12px;
            width: 100%;
            color: #475569;
            font-weight: 500;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="page-header d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title"><i class="fa fa-file-invoice"></i> Attendance Record</h1>
                <p class="page-subtitle">{{ $monthLabel }} Report</p>
            </div>
            <div>
                <button type="button" class="btn btn-outline-primary shadow-sm" onclick="window.print()">
                    <i class="fa fa-print me-2"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="report-card">
            <form method="GET" action="{{ route('hr.attendance.report') }}" class="row g-4 align-items-end">
                <div class="col-md-3">
                    <label class="filter-label">Select Month</label>
                    <input type="month" name="month" class="filter-input" value="{{ $selectedMonth }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Department</label>
                    <select name="department_id" class="form-select select2" onchange="this.form.submit()">
                        <option value="all">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $selectedDepartment == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Designation</label>
                    <select name="designation_id" class="form-select select2" onchange="this.form.submit()">
                        <option value="all">All Designations</option>
                        @foreach ($designations as $desig)
                            <option value="{{ $desig->id }}" {{ $selectedDesignation == $desig->id ? 'selected' : '' }}>
                                {{ $desig->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="filter-label">Employee</label>
                    <select name="employee_id" class="form-select select2" onchange="this.form.submit()">
                        <option value="all">All Employees</option>
                        @foreach ($allEmployees as $emp)
                            <option value="{{ $emp->id }}" {{ $selectedEmployee == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_id_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('hr.attendance.report') }}" class="btn btn-light border w-100" style="height: 42px; display: flex; align-items: center; justify-content: center;" title="Clear Filters">
                        <i class="fa fa-sync"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Report Table -->
        <div class="report-card p-0 overflow-hidden">
            <div class="report-table-container">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th class="emp-col">Employee Details</th>
                            @for ($day = 1; $day <= $daysInMonth; $day++)
                                @php
                                    $date = $monthDate->copy()->day($day);
                                    $isSunday = $date->format('l') == 'Sunday';
                                    $isToday = $date->isToday();
                                @endphp
                                <th class="day-col {{ $isSunday ? 'sunday' : '' }} {{ $isToday ? 'today-col' : '' }}">
                                    <div class="day-header">
                                        <span class="day-num">{{ $day }}</span>
                                        <span class="day-name">{{ $date->format('D') }}</span>
                                    </div>
                                </th>
                            @endfor
                            <th style="min-width: 50px;">P</th>
                            <th style="min-width: 50px;">A</th>
                            <th style="min-width: 50px;">L</th>
                            <th style="min-width: 50px;">LV</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $departmentName => $designations)
                            <tr class="department-row">
                                <td colspan="{{ $daysInMonth + 5 }}" class="text-start py-3 px-4">
                                    <div class="dept-title">
                                        <div class="icon-box"><i class="fa fa-sitemap"></i></div>
                                        <span>DEPARTMENT: {{ strtoupper($departmentName ?: 'Unassigned Department') }}</span>
                                    </div>
                                </td>
                            </tr>
                            @foreach ($designations as $designationName => $designationEmployees)
                                <tr class="designation-row">
                                    <td colspan="{{ $daysInMonth + 5 }}" class="text-start py-2 px-4">
                                        <div class="desig-title">
                                            <i class="fa fa-user-tag text-primary"></i>
                                            <span>DESIGNATION: {{ $designationName ?: 'Unassigned Designation' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @foreach ($designationEmployees as $emp)
                                    @php
                                        $pCount = 0;
                                        $aCount = 0;
                                        $lCount = 0;
                                        $lvCount = 0;
                                        $offDaysArray = $emp->weekly_off 
                                            ? array_map('trim', explode(',', strtolower($emp->weekly_off))) 
                                            : ['sunday'];
                                    @endphp
                                    <tr>
                                        <td class="emp-col ps-4">
                                            <div class="fw-bold text-dark">{{ $emp->full_name }}</div>
                                            <div class="small text-muted">{{ $emp->employee_id_code }}</div>
                                        </td>
                                        @for ($day = 1; $day <= $daysInMonth; $day++)
                                            @php
                                                $dateObj = $monthDate->copy()->day($day);
                                                $currentDate = $dateObj->format('Y-m-d');
                                                $currentDayName = strtolower($dateObj->format('l'));
                                                $isToday = $dateObj->isToday();
                                                
                                                $attendance = $attendanceMap[$emp->id][$currentDate] ?? null;
                                                $leaveType = $leavesMap[$emp->id][$currentDate] ?? null;
                                                $holidayName = $holidaysMap[$currentDate] ?? null;
                                                $isWeeklyOff = in_array($currentDayName, $offDaysArray);
                                                
                                                $status = '';
                                                
                                                if ($attendance) {
                                                    if ($attendance->status == 'present' && !$attendance->is_late) {
                                                        $status = 'P';
                                                        $pCount++;
                                                    } elseif ($attendance->status == 'late' || ($attendance->status == 'present' && $attendance->is_late)) {
                                                        $status = 'L';
                                                        $lCount++;
                                                    } elseif ($attendance->status == 'leave') {
                                                        $status = 'LV';
                                                        $lvCount++;
                                                    } else {
                                                        $status = 'A';
                                                        $aCount++;
                                                    }
                                                } elseif ($leaveType) {
                                                    $status = 'LV';
                                                    $lvCount++;
                                                } elseif ($holidayName) {
                                                    $status = 'H';
                                                } elseif ($isWeeklyOff) {
                                                    $status = 'WO';
                                                } else {
                                                    if ($currentDate <= date('Y-m-d')) {
                                                        $status = 'A';
                                                        $aCount++;
                                                    }
                                                }
                                            @endphp
                                            <td class="{{ $currentDayName == 'sunday' ? 'sunday' : '' }} {{ $isToday ? 'today-col' : '' }}">
                                                @if ($status)
                                                    @php
                                                        $tooltip = '';
                                                        if ($status == 'H') $tooltip = $holidayName;
                                                        elseif ($status == 'LV') $tooltip = $leaveType;
                                                        elseif ($status == 'L' && $attendance && $attendance->late_minutes) {
                                                            $hours = floor($attendance->late_minutes / 60);
                                                            $mins = $attendance->late_minutes % 60;
                                                            $timeString = '';
                                                            if ($hours > 0) $timeString .= $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ';
                                                            if ($mins > 0 || $hours == 0) $timeString .= $mins . ' min' . ($mins > 1 ? 's' : '');
                                                            $tooltip = trim($timeString) . ' late';
                                                        }
                                                    @endphp
                                                    <span class="status-badge status-{{ $status }}" {!! $tooltip ? 'data-tooltip="'.$tooltip.'"' : '' !!}>
                                                        {{ $status }}
                                                    </span>
                                                @endif
                                            </td>
                                        @endfor
                                        <td class="total-col"><span class="badge bg-success-soft text-success">{{ $pCount }}</span></td>
                                        <td class="total-col"><span class="badge bg-danger-soft text-danger">{{ $aCount }}</span></td>
                                        <td class="total-col"><span class="badge bg-warning-soft text-warning">{{ $lCount }}</span></td>
                                        <td class="total-col"><span class="badge bg-info-soft text-info">{{ $lvCount }}</span></td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="legend">
                <div class="legend-item"><span class="status-badge status-P">P</span> Present</div>
                <div class="legend-item"><span class="status-badge status-A">A</span> Absent</div>
                <div class="legend-item"><span class="status-badge status-L">L</span> Late</div>
                <div class="legend-item"><span class="status-badge status-LV">LV</span> Leave</div>
                <div class="legend-item"><span class="status-badge status-H">H</span> Holiday</div>
                <div class="legend-item"><span class="status-badge status-WO">WO</span> Weekly Off</div>
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
