<div class="pc-archive-list-header">
    <div>
        <h5><i class="fas fa-list-alt me-2"></i>{{ $title }}</h5>
        <small>{{ $period->name }} &nbsp;•&nbsp; Read-only archive</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="pc-badge-closed"><i class="fas fa-lock me-1"></i> Closed</span>
        <form action="{{ route('period.access.lock') }}" method="POST" class="m-0 d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm" title="Lock period pages">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </form>
        <a href="{{ route('period.archive.show', $period) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
</div>
