@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.period_closing.partials.styles')

<div class="main-content pc-page">
    <div class="container-fluid px-3">

        <div class="pc-hero">
            <div class="pc-hero-content d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="pc-hero-badge">
                        <i class="fas fa-archive"></i> Secure Data Vault
                    </div>
                    <h2><i class="fas fa-database me-2"></i>Closed Period Archive</h2>
                    <p>Band kiye gaye periods ka sara data yahan mojood hai — bilkul safe, koi delete nahi.</p>
                </div>
                @if(auth()->user()->hasRole('period_viewer'))
                    <span class="pc-badge-viewer"><i class="fas fa-eye me-1"></i> Read-Only Viewer</span>
                @endif
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger pc-alert-soft mb-4" style="background:#fef2f2;border-left:4px solid #ef4444;">
                {{ session('error') }}
            </div>
        @endif

        @if($periods->isEmpty())
            <div class="pc-empty-state">
                <i class="fas fa-folder-open d-block"></i>
                <h5>Abhi tak koi period close nahi hui</h5>
                <p class="text-muted mb-0">Jab aap period closing karenge, woh yahan archive mein dikhegi.</p>
                @can('Period Closing')
                <a href="{{ route('period.closing.index') }}" class="btn btn-primary pc-btn-primary mt-3">
                    <i class="fas fa-calendar-times me-2"></i>Go to Period Closing
                </a>
                @endcan
            </div>
        @else
            <div class="row g-4">
                @foreach($periods as $period)
                <div class="col-md-6 col-xl-4">
                    <div class="pc-period-card">
                        <div class="pc-period-card-top">
                            <div class="period-name">
                                <i class="fas fa-calendar-check me-2 opacity-75"></i>{{ $period->name }}
                            </div>
                            <div class="period-dates">
                                <i class="fas fa-arrow-right me-1"></i>
                                {{ $period->start_date->format('d M Y') }} — {{ $period->end_date->format('d M Y') }}
                            </div>
                        </div>
                        <div class="pc-period-card-body">
                            <div class="pc-period-meta">
                                <div class="pc-period-meta-item">
                                    <i class="fas fa-user"></i>
                                    <span>Closed by: <strong>{{ $period->closedBy?->name ?? '—' }}</strong></span>
                                </div>
                                <div class="pc-period-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span>{{ $period->closed_at?->format('d M Y, h:i A') ?? '—' }}</span>
                                </div>
                            </div>
                            <a href="{{ route('period.archive.show', $period) }}" class="btn btn-primary pc-btn-primary w-100">
                                <i class="fas fa-folder-open me-2"></i>Open Archive Records
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection
