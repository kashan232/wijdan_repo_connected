@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.period_closing.partials.styles')

<div class="main-content pc-page">
    <div class="container-fluid px-3">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="pc-card mt-4">
                    <div class="pc-card-header settings">
                        <div class="pc-icon-wrap"><i class="fas fa-lock"></i></div>
                        <h5>Security Verification</h5>
                    </div>
                    <div class="pc-card-body">
                        <p class="text-muted small mb-3">
                            Period Closing aur Closed Archive pages ke liye password zaroori hai.
                            Sirf admin access kar sakta hai.
                        </p>

                        @if($errors->any())
                            <div class="alert alert-danger pc-alert-soft mb-3">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form action="{{ route('period.access.verify') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="pc-form-label">Access Password</label>
                                <input type="password" name="access_password" class="form-control pc-form-control"
                                    required autofocus placeholder="Password enter karein">
                            </div>
                            <button type="submit" class="btn btn-primary pc-btn-primary w-100">
                                <i class="fas fa-unlock me-2"></i>Verify & Continue
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="{{ url('/home') }}" class="text-muted small">
                                <i class="fas fa-arrow-left me-1"></i> Dashboard par wapas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
