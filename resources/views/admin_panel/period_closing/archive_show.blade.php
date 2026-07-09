@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.period_closing.partials.styles')

<div class="main-content pc-page">
    <div class="container-fluid px-3">

        <div class="pc-hero mb-4">
            <div class="pc-hero-content">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <div class="pc-hero-badge"><i class="fas fa-lock"></i> Closed Period</div>
                        <h2>{{ $period->name }}</h2>
                        <p class="mb-0">
                            <i class="fas fa-calendar me-1"></i>
                            {{ $period->start_date->format('d M Y') }} — {{ $period->end_date->format('d M Y') }}
                            &nbsp;|&nbsp;
                            <i class="fas fa-clock me-1"></i>
                            Closed {{ $period->closed_at?->format('d M Y, h:i A') }}
                            @if($period->closedBy)
                                by <strong>{{ $period->closedBy->name }}</strong>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('period.archive.index') }}" class="btn btn-outline-secondary btn-sm me-1">
                        <i class="fas fa-arrow-left me-1"></i> All Archives
                    </a>
                    <form action="{{ route('period.access.lock') }}" method="POST" class="d-inline m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-lock me-1"></i> Lock
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @php
            $totalRecords = array_sum($counts);
            $cards = [
                ['label' => 'Sales', 'count' => $counts['sales'], 'route' => 'period.archive.sales', 'icon' => 'fa-cash-register', 'class' => 'sales'],
                ['label' => 'Bookings', 'count' => $counts['product_bookings'], 'route' => 'period.archive.bookings', 'icon' => 'fa-calendar-check', 'class' => 'bookings'],
                ['label' => 'Purchases', 'count' => $counts['purchases'], 'route' => 'period.archive.purchases', 'icon' => 'fa-shopping-cart', 'class' => 'purchases'],
                ['label' => 'Sale Returns', 'count' => $counts['sales_returns'], 'route' => 'period.archive.sales-returns', 'icon' => 'fa-undo', 'class' => 'sale-returns'],
                ['label' => 'Purchase Returns', 'count' => $counts['purchase_returns'], 'route' => 'period.archive.purchase-returns', 'icon' => 'fa-undo-alt', 'class' => 'purchase-returns'],
                ['label' => 'Inward Gatepass', 'count' => $counts['inward_gatepasses'], 'route' => 'period.archive.inwards', 'icon' => 'fa-truck-loading', 'class' => 'inwards'],
                ['label' => 'Inward Returns', 'count' => $counts['inward_returns'], 'route' => 'period.archive.inward-returns', 'icon' => 'fa-truck', 'class' => 'inward-returns'],
                ['label' => 'Expense Vouchers', 'count' => $counts['expense_vouchers'], 'route' => 'period.archive.expenses', 'icon' => 'fa-file-invoice-dollar', 'class' => 'expenses'],
            ];
        @endphp

        {{-- Summary strip --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="pc-stat-box">
                    <div class="pc-stat-icon blue"><i class="fas fa-database"></i></div>
                    <div class="pc-stat-content">
                        <div class="stat-label">Total Records</div>
                        <div class="stat-value">{{ number_format($totalRecords) }}</div>
                        <div class="stat-sub">Archived documents</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="pc-stat-box">
                    <div class="pc-stat-icon slate"><i class="fas fa-calendar-alt"></i></div>
                    <div class="pc-stat-content">
                        <div class="stat-label">Period Duration</div>
                        <div class="stat-value">{{ $period->start_date->diffInDays($period->end_date) + 1 }}</div>
                        <div class="stat-sub">Days covered</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="pc-stat-box">
                    <div class="pc-stat-icon green"><i class="fas fa-layer-group"></i></div>
                    <div class="pc-stat-content">
                        <div class="stat-label">Document Types</div>
                        <div class="stat-value">8</div>
                        <div class="stat-sub">Modules available</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pc-section-title mb-3" style="margin-top:0;">
            <i class="fas fa-th-large me-2"></i> Select module to view full records
        </div>

        <div class="row g-3 mb-4">
            @foreach($cards as $card)
            <div class="col-6 col-md-4 col-lg-3">
                @if($card['count'] > 0)
                <a href="{{ route($card['route'], $period) }}" class="pc-module-card">
                @else
                <div class="pc-module-card disabled">
                @endif
                    <div class="pc-module-inner">
                        <div class="pc-module-icon"><i class="fas {{ $card['icon'] }}"></i></div>
                        <div class="pc-module-title">{{ $card['label'] }}</div>
                        <div class="pc-module-count">{{ number_format($card['count']) }}</div>
                        @if($card['count'] > 0)
                            <span class="pc-module-link">View records <i class="fas fa-arrow-right ms-1"></i></span>
                        @else
                            <span class="text-muted small">No records</span>
                        @endif
                    </div>
                    <div class="pc-module-footer">Read-only archive</div>
                @if($card['count'] > 0)
                </a>
                @else
                </div>
                @endif
            </div>
            @endforeach
        </div>

        @if($period->snapshots->count())
        <div class="pc-card">
            <div class="pc-card-header history">
                <div class="pc-icon-wrap"><i class="fas fa-chart-pie"></i></div>
                <h5>Closing Snapshot Summary</h5>
            </div>
            <div class="pc-card-body">
                <div class="row g-3">
                    @foreach($period->snapshots as $snapshot)
                        @if(in_array($snapshot->snapshot_type, ['sales_summary', 'purchase_summary', 'expense_summary']))
                        @php
                            $snapClass = match($snapshot->snapshot_type) {
                                'purchase_summary' => 'snap-purchase',
                                'expense_summary' => 'snap-expense',
                                default => '',
                            };
                        @endphp
                        <div class="col-md-4">
                            <div class="pc-snapshot-card {{ $snapClass }}">
                                <div class="snap-label">{{ ucwords(str_replace('_', ' ', str_replace('_summary', '', $snapshot->snapshot_type))) }}</div>
                                <div class="snap-count">{{ $snapshot->data['count'] ?? 0 }} records</div>
                                <div class="snap-total">Rs. {{ number_format($snapshot->data['total'] ?? 0, 2) }}</div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
