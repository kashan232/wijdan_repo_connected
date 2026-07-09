@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.period_closing.partials.styles')

<div class="main-content pc-page">
    <div class="container-fluid px-3">

        <div class="pc-hero">
            <div class="pc-hero-content">
                <div class="pc-hero-badge">
                    <i class="fas fa-shield-alt"></i> Accounting Period Management
                </div>
                <h2><i class="fas fa-calendar-times me-2"></i>Period Closing</h2>
                <p>Apna accounting period band karein — sara data safe rahega archive mein. Koi record delete nahi hota.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show pc-alert-soft" style="background:#ecfdf5;border-left:4px solid #10b981;color:#065f46;">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger pc-alert-soft" style="background:#fef2f2;border-left:4px solid #ef4444;">
                <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="row g-4">
            {{-- Settings --}}
            <div class="col-lg-5">
                <div class="pc-card">
                    <div class="pc-card-header settings">
                        <div class="pc-icon-wrap"><i class="fas fa-cog"></i></div>
                        <h5>Closing Settings</h5>
                    </div>
                    <div class="pc-card-body">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-info-circle me-1 text-primary"></i>
                            Pehle password aur archive viewer account set karein. Closing ke waqt password zaroori hoga.
                        </p>

                        <form action="{{ route('period.closing.settings') }}" method="POST" class="mt-3">
                            @csrf

                            <div class="pc-section-title"><i class="fas fa-key me-1"></i> Closing Password</div>
                            <div class="mb-3">
                                <label class="pc-form-label">Password</label>
                                <input type="password" name="closing_password" class="form-control pc-form-control" required minlength="4"
                                    placeholder="{{ $hasPassword ? 'Naya password set karein' : 'Password set karein' }}">
                            </div>
                            <div class="mb-3">
                                <label class="pc-form-label">Confirm Password</label>
                                <input type="password" name="closing_password_confirmation" class="form-control pc-form-control" required minlength="4">
                            </div>

                            <div class="pc-section-title"><i class="fas fa-user-shield me-1"></i> Archive Viewer Account</div>
                            <p class="text-muted small">Alag user — closed period sirf dekh sakta hai (read-only).</p>

                            <div class="mb-3">
                                <label class="pc-form-label">Name</label>
                                <input type="text" name="viewer_name" class="form-control pc-form-control" required
                                    value="{{ old('viewer_name', $settings->viewerUser?->name) }}">
                            </div>
                            <div class="mb-3">
                                <label class="pc-form-label">Email (Login)</label>
                                <input type="email" name="viewer_email" class="form-control pc-form-control" required
                                    value="{{ old('viewer_email', $settings->viewerUser?->email) }}">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="pc-form-label">Viewer Password</label>
                                    <input type="password" name="viewer_password" class="form-control pc-form-control" required minlength="4">
                                </div>
                                <div class="col-6">
                                    <label class="pc-form-label">Confirm</label>
                                    <input type="password" name="viewer_password_confirmation" class="form-control pc-form-control" required minlength="4">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary pc-btn-primary w-100 mt-4">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Close Period --}}
            <div class="col-lg-7">
                <div class="pc-card mb-4">
                    <div class="pc-card-header close-action">
                        <div class="pc-icon-wrap"><i class="fas fa-lock"></i></div>
                        <h5>Close Accounting Period</h5>
                    </div>
                    <div class="pc-card-body">
                        @if(!$hasPassword)
                            <div class="pc-alert-soft warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Pehle left side se closing password set karein, phir period close kar sakte hain.
                            </div>
                        @else
                            <div class="pc-info-strip mb-4">
                                <div class="pc-info-item">
                                    <label>Period Start</label>
                                    <span>{{ \Carbon\Carbon::parse($summary['period_start'])->format('d M Y') }}</span>
                                </div>
                                <div class="pc-info-item">
                                    <label>Last Closed</label>
                                    <span>
                                        @if($summary['last_closed_end'])
                                            {{ \Carbon\Carbon::parse($summary['last_closed_end'])->format('d M Y') }}
                                        @else
                                            — Abhi tak nahi
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <form action="{{ route('period.closing.close') }}" method="POST" id="closePeriodForm"
                                onsubmit="return confirm('Kya aap sure hain? Is date tak ka sara data band ho jayega. Koi record delete nahi hoga.');">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="pc-form-label">Closing Date <span class="text-danger">*</span></label>
                                        <input type="date" name="closing_date" id="closing_date" class="form-control pc-form-control"
                                            value="{{ old('closing_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                                        <small class="text-muted">Jis date tak close karna hai (e.g. 21 June)</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="pc-form-label">Closing Password <span class="text-danger">*</span></label>
                                        <input type="password" name="closing_password" class="form-control pc-form-control" required
                                            placeholder="••••••••">
                                    </div>
                                    <div class="col-12">
                                        <label class="pc-form-label">Notes (optional)</label>
                                        <textarea name="notes" class="form-control pc-form-control" rows="2"
                                            placeholder="e.g. June 2026 closing">{{ old('notes') }}</textarea>
                                    </div>
                                </div>

                                <div class="mt-4" id="previewCounts">
                                    <div class="pc-section-title mb-3">Records to be tagged in this closing</div>
                                    <div class="pc-count-grid">
                                        @php
                                            $chips = [
                                                ['Sales', $summary['counts']['sales']],
                                                ['Bookings', $summary['counts']['product_bookings'] ?? 0],
                                                ['Purchases', $summary['counts']['purchases']],
                                                ['Sale Returns', $summary['counts']['sales_returns']],
                                                ['Purchase Returns', $summary['counts']['purchase_returns']],
                                                ['Inward', $summary['counts']['inward_gatepasses']],
                                                ['Inward Returns', $summary['counts']['inward_returns']],
                                                ['Expenses', $summary['counts']['expense_vouchers']],
                                            ];
                                        @endphp
                                        @foreach($chips as [$label, $count])
                                        <div class="pc-count-chip">
                                            <div class="label">{{ $label }}</div>
                                            <div class="value">{{ $count }}</div>
                                        </div>
                                        @endforeach
                                        <div class="pc-count-chip total">
                                            <div class="label">Total</div>
                                            <div class="value">{{ $summary['total_records'] }}</div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-danger pc-btn-danger mt-4">
                                    <i class="fas fa-lock me-2"></i>Close Period Now
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($summary['closed_periods']->count())
                <div class="pc-card">
                    <div class="pc-card-header history">
                        <div class="pc-icon-wrap"><i class="fas fa-history"></i></div>
                        <h5>Closed Periods History</h5>
                    </div>
                    <div class="pc-card-body p-0">
                        <div class="pc-table-wrap">
                            <table class="table pc-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Closed By</th>
                                        <th>Closed At</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summary['closed_periods'] as $period)
                                    <tr>
                                        <td class="fw-semibold">{{ $period->name }}</td>
                                        <td>{{ $period->closedBy?->name ?? '—' }}</td>
                                        <td>{{ $period->closed_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                        <td>
                                            <a href="{{ route('period.archive.show', $period) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="fas fa-folder-open me-1"></i>Archive
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('closing_date')?.addEventListener('change', function() {
    const date = this.value;
    if (!date) return;
    fetch('{{ route("period.closing.preview") }}?closing_date=' + date, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        const el = document.getElementById('previewCounts');
        if (!el || !data.counts) return;
        const chips = [
            ['Sales', data.counts.sales],
            ['Bookings', data.counts.product_bookings ?? 0],
            ['Purchases', data.counts.purchases],
            ['Sale Returns', data.counts.sales_returns],
            ['Purchase Returns', data.counts.purchase_returns],
            ['Inward', data.counts.inward_gatepasses],
            ['Inward Returns', data.counts.inward_returns],
            ['Expenses', data.counts.expense_vouchers],
        ];
        let html = '<div class="pc-section-title mb-3">Records to be tagged (' + data.closing_date + ')</div><div class="pc-count-grid">';
        chips.forEach(([l, v]) => { html += '<div class="pc-count-chip"><div class="label">'+l+'</div><div class="value">'+v+'</div></div>'; });
        html += '<div class="pc-count-chip total"><div class="label">Total</div><div class="value">'+data.total_records+'</div></div></div>';
        el.innerHTML = html;
    });
});
</script>
@endsection
