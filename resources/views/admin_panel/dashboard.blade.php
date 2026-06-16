@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Google Font */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');

    .welcome-wrapper{
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Poppins', sans-serif;
    }

    .welcome-box{
        text-align: center;
        padding: 55px 40px;
        background: linear-gradient(180deg, #ffffff, #f9fafb);
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(0,0,0,0.10);
        max-width: 780px;
        width: 100%;
        border: 1px solid #eef1f5;
    }

    .welcome-divider{
        width: 90px;
        height: 5px;
        background: linear-gradient(90deg, #0d6efd, #6610f2);
        margin: 0 auto 28px;
        border-radius: 6px;
    }

    .welcome-title{
        font-size: 42px;      /* 🔥 Bigger title */
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 14px;
    }

    .welcome-subtitle{
        font-size: 20px;      /* 🔥 Bigger subtitle */
        color: #475569;
        margin-bottom: 30px;
        font-weight: 500;
    }

    .welcome-footer{
        font-size: 15px;
        color: #64748b;
        margin-top: 30px;
        font-weight: 500;
        letter-spacing: .3px;
    }

    .welcome-footer strong{
        color: #0f172a;
        font-weight: 700;
    }

    /* Small screens */
    @media(max-width:768px){
        .welcome-title{
            font-size: 30px;
        }
        .welcome-subtitle{
            font-size: 16px;
        }
    }

    /* --- Premium Attendance Widget Styles --- */
    .attendance-widget-premium {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 24px;
        padding: 35px 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid rgba(226, 232, 240, 0.8);
        margin: 0 auto 40px auto;
        max-width: 420px;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .attendance-widget-premium:hover {
        transform: translateY(-5px);
    }

    .attendance-widget-premium::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
    }

    .attendance-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 25px;
    }

    .attendance-header h3 {
        margin: 0 0 15px 0;
        font-size: 16px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .time-display {
        font-size: 42px;
        font-weight: 900;
        color: #0f172a;
        line-height: 1;
        margin-bottom: 8px;
        font-variant-numeric: tabular-nums;
        letter-spacing: -1px;
    }

    .date-display {
        color: #64748b;
        font-size: 15px;
        font-weight: 500;
    }

    .btn-attendance {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        padding: 16px;
        font-size: 18px;
        font-weight: 700;
        border-radius: 14px;
        border: none;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: white;
    }

    .btn-check-in {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
    }

    .btn-check-in:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 15px 25px -5px rgba(16, 185, 129, 0.5);
    }

    .btn-check-out {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 10px 20px -5px rgba(239, 68, 68, 0.4);
    }

    .btn-check-out:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 15px 25px -5px rgba(239, 68, 68, 0.5);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 25px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.late {
        background: #fef2f2;
        color: #ef4444;
        border: 1px solid #fecaca;
    }

    .status-badge.on-time {
        background: #f0fdf4;
        color: #10b981;
        border: 1px solid #a7f3d0;
    }

    .shift-completed-card {
        background: linear-gradient(145deg, #ffffff, #f0fdf4);
        border-radius: 20px;
        padding: 30px 20px;
        border: 2px solid #bbf7d0;
        text-align: center;
        box-shadow: 0 10px 25px rgba(22, 163, 74, 0.1);
        position: relative;
        overflow: hidden;
    }

    .shift-completed-card::before {
        content: '';
        position: absolute;
        top: -50px;
        left: -50px;
        width: 100px;
        height: 100px;
        background: rgba(34, 197, 94, 0.1);
        border-radius: 50%;
    }
    .shift-completed-card::after {
        content: '';
        position: absolute;
        bottom: -50px;
        right: -50px;
        width: 120px;
        height: 120px;
        background: rgba(59, 130, 246, 0.05);
        border-radius: 50%;
    }

    .shift-completed-card i.main-icon {
        font-size: 55px;
        color: #22c55e;
        margin-bottom: 15px;
        filter: drop-shadow(0 4px 6px rgba(34, 197, 94, 0.3));
        animation: bounce-slight 2s infinite ease-in-out;
    }

    @keyframes bounce-slight {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    .shift-completed-card h4 {
        color: #064e3b;
        font-size: 24px;
        font-weight: 900;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }
    
    .shift-completed-card p {
        color: #059669;
        font-size: 15px;
        font-weight: 500;
        margin-bottom: 25px;
    }

    .shift-stats {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 10px;
        background: white;
        border-radius: 12px;
        padding: 15px;
        border: 1px solid #f1f5f9;
    }

    .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .stat-item span {
        font-size: 11px;
        color: #94a3b8;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }

    .stat-item strong {
        font-size: 15px;
        color: #1e293b;
        font-weight: 700;
    }
    
    .stat-divider {
        width: 1px;
        background: #e2e8f0;
        height: 100%;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container">

            <div class="dashboard-grid-container">
                <style>
                    .dashboard-grid-container {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 30px;
                        max-width: 1200px;
                        margin: 0 auto;
                        align-items: center;
                    }
                    .dashboard-left {
                        width: 100%;
                    }
                    .dashboard-right {
                        background: linear-gradient(180deg, #ffffff, #f9fafb);
                        border-radius: 18px;
                        box-shadow: 0 18px 45px rgba(0,0,0,0.06);
                        border: 1px solid #eef1f5;
                        padding: 50px 40px;
                        text-align: left;
                    }
                    .dashboard-right .welcome-title {
                        font-size: 38px;
                        margin-bottom: 15px;
                    }
                    .dashboard-right .welcome-divider {
                        margin: 0 0 25px 0;
                    }
                    @media(max-width: 992px) {
                        .dashboard-grid-container {
                            grid-template-columns: 1fr;
                        }
                        .dashboard-right {
                            text-align: center;
                        }
                        .dashboard-right .welcome-divider {
                            margin: 0 auto 25px auto;
                        }
                    }
                </style>

                <!-- LEFT SIDE: ATTENDANCE WIDGET -->
                <div class="dashboard-left">
                    @php
                        $employee = auth()->user()->employee;
                    @endphp

                    @if($employee)
                        @php
                            $attendance = $employee->getTodayAttendance();
                            $shiftName = $employee->shift ? $employee->shift->name : 'Custom Shift';
                            $shiftStart = \Carbon\Carbon::parse($employee->getStartTime())->format('h:i A');
                            $shiftEnd = \Carbon\Carbon::parse($employee->getEndTime())->format('h:i A');
                        @endphp
                        
                        <div class="attendance-widget-premium" style="margin: 0; max-width: 100%;">
                            <div class="attendance-header">
                                <h3>My Workspace</h3>
                                <div class="time-display" id="liveTime">{{ \Carbon\Carbon::now()->format('h:i:s A') }}</div>
                                <div class="date-display">{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</div>
                                
                                <div style="margin-top: 15px; padding: 8px 16px; background: #e2e8f0; border-radius: 8px; font-size: 14px; font-weight: 600; color: #334155; display: inline-block;">
                                    <i class="fa fa-user-clock" style="color: #3b82f6; margin-right: 5px;"></i> 
                                    Shift: <span style="color: #0f172a;">{{ $shiftName }}</span> 
                                    <span style="font-weight: 500; margin-left: 5px;">({{ $shiftStart }} - {{ $shiftEnd }})</span>
                                </div>
                            </div>
                            
                            @if(session('success'))
                                <div class="alert alert-success" style="padding: 12px; margin-bottom: 20px; border-radius: 10px; background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; font-weight: 500; font-size: 14px;">
                                    <i class="fa fa-check-circle" style="margin-right: 5px;"></i> {{ session('success') }}
                                </div>
                            @endif
                            @if(session('error'))
                                <div class="alert alert-danger" style="padding: 12px; margin-bottom: 20px; border-radius: 10px; background-color: #fef2f2; border: 1px solid #fecaca; color: #ef4444; font-weight: 500; font-size: 14px;">
                                    <i class="fa fa-exclamation-circle" style="margin-right: 5px;"></i> {{ session('error') }}
                                </div>
                            @endif

                            @if(session('sweet_error'))
                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                <script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Action Not Allowed',
                                            text: '{{ session("sweet_error") }}',
                                            confirmButtonColor: '#ef4444',
                                            confirmButtonText: 'Understood',
                                            background: '#fff',
                                            customClass: {
                                                popup: 'rounded-4'
                                            }
                                        });
                                    });
                                </script>
                            @endif

                            @if(!$attendance)
                                <form action="{{ route('attendance.web.checkIn') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-attendance btn-check-in">
                                        <i class="fa fa-fingerprint"></i> Clock In Now
                                    </button>
                                </form>
                            @elseif(!$attendance->clock_out)
                                @php
                                    $punchGap = $employee->punch_gap_minutes ?? 0;
                                    $checkInTimeObj = \Carbon\Carbon::parse($attendance->clock_in);
                                    $canCheckOutTime = $checkInTimeObj->copy()->addMinutes($punchGap);
                                    $currentNow = \Carbon\Carbon::now();
                                    $canCheckOut = $currentNow->gte($canCheckOutTime);
                                    $remainingSeconds = $canCheckOut ? 0 : $currentNow->diffInSeconds($canCheckOutTime);
                                @endphp

                                <div style="margin-bottom: 15px; text-align: center;">
                                    <div style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 10px;">
                                        <i class="fa fa-sign-in" style="color: #10b981;"></i> Clocked In: {{ \Carbon\Carbon::parse($attendance->clock_in)->format('h:i A') }}
                                    </div>
                                    @if($attendance->is_late)
                                        <div class="status-badge late" style="margin-bottom: 0;">
                                            <i class="fa fa-exclamation-triangle"></i> Late by {{ $attendance->late_minutes }} mins
                                        </div>
                                    @else
                                        <div class="status-badge on-time" style="margin-bottom: 0;">
                                            <i class="fa fa-check"></i> Checked in on time
                                        </div>
                                    @endif
                                </div>
                                
                                <div id="gapTimerContainer" style="display: {{ $canCheckOut ? 'none' : 'block' }}; text-align: center; margin-top: 20px; background: #fffbeb; padding: 15px; border-radius: 12px; border: 1px solid #fde68a;">
                                    <div style="font-size: 14px; color: #d97706; font-weight: 700; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Punch Gap Timer</div>
                                    <div id="gapTimer" style="font-size: 32px; font-weight: 900; color: #b45309; font-variant-numeric: tabular-nums;" data-seconds="{{ $remainingSeconds }}">
                                        {{ sprintf('%02d:%02d', floor($remainingSeconds / 60), $remainingSeconds % 60) }}
                                    </div>
                                    <div style="font-size: 12px; color: #ef4444; margin-top: 5px; font-weight: 500;"><i class="fa fa-lock"></i> You cannot clock out yet.</div>
                                </div>

                                <form id="checkOutForm" action="{{ route('attendance.web.checkOut') }}" method="POST" style="margin-top: 20px; display: {{ $canCheckOut ? 'block' : 'none' }};">
                                    @csrf
                                    <button type="submit" class="btn-attendance btn-check-out">
                                        <i class="fa fa-sign-out"></i> Clock Out
                                    </button>
                                </form>
                            @else
                                @php
                                    $inTime = \Carbon\Carbon::parse($attendance->clock_in);
                                    $outTime = \Carbon\Carbon::parse($attendance->clock_out);
                                    $diff = $inTime->diff($outTime);
                                    $durationStr = $diff->h . 'h ' . $diff->i . 'm';
                                @endphp
                                <div class="shift-completed-card">
                                    <i class="fa fa-medal main-icon"></i>
                                    <h4>Great Work Today!</h4>
                                    <p>Your shift has been recorded successfully.</p>
                                    
                                    <div class="shift-stats">
                                        <div class="stat-item">
                                            <span>Clock In</span>
                                            <strong>{{ $inTime->format('h:i A') }}</strong>
                                        </div>
                                        <div class="stat-divider"></div>
                                        <div class="stat-item">
                                            <span>Clock Out</span>
                                            <strong>{{ $outTime->format('h:i A') }}</strong>
                                        </div>
                                        <div class="stat-divider"></div>
                                        <div class="stat-item">
                                            <span>Duration</span>
                                            <strong style="color: #2563eb;">{{ $durationStr }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <script>
                            // Live clock updates
                            setInterval(function() {
                                var now = new Date();
                                var hours = now.getHours();
                                var minutes = now.getMinutes();
                                var seconds = now.getSeconds();
                                var ampm = hours >= 12 ? 'PM' : 'AM';
                                hours = hours % 12;
                                hours = hours ? hours : 12;
                                minutes = minutes < 10 ? '0' + minutes : minutes;
                                seconds = seconds < 10 ? '0' + seconds : seconds;
                                var strTime = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
                                document.getElementById('liveTime').innerHTML = strTime;
                            }, 1000);

                            // Punch Gap Timer logic
                            var gapTimerEl = document.getElementById('gapTimer');
                            if (gapTimerEl) {
                                var gapSeconds = parseInt(gapTimerEl.getAttribute('data-seconds'));
                                if (gapSeconds > 0) {
                                    var gapInterval = setInterval(function() {
                                        gapSeconds--;
                                        if (gapSeconds <= 0) {
                                            clearInterval(gapInterval);
                                            document.getElementById('gapTimerContainer').style.display = 'none';
                                            document.getElementById('checkOutForm').style.display = 'block';
                                        } else {
                                            var m = Math.floor(gapSeconds / 60);
                                            var s = gapSeconds % 60;
                                            gapTimerEl.innerHTML = (m < 10 ? '0'+m : m) + ':' + (s < 10 ? '0'+s : s);
                                        }
                                    }, 1000);
                                }
                            }
                        </script>
                    @endif
                </div>

                <!-- RIGHT SIDE: WELCOME TEXT -->
                <div class="dashboard-right">
                    <h1 class="welcome-title">
                        Welcome to Wijdan Exclusive Store
                    </h1>

                    <p class="welcome-subtitle">
                        Management & Reporting Dashboard
                    </p>

                    <div class="welcome-divider"></div>

                    <p class="welcome-footer" style="margin-top: 10px;">
                        Developed by <strong>ProWave Software Solutions</strong>
                    </p>
                </div>

            </div>

        </div>
    </div>
</div>
@endsection
