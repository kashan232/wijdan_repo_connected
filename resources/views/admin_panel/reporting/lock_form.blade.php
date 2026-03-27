@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-6 mt-5">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="card-title mb-0">Report Locked</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">This report is password protected. Please enter your login password to continue.</p>
                            
                            @if($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('report.unlock') }}" method="POST">
                                @csrf
                                <input type="hidden" name="intended" value="{{ $intended }}">
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your login password" required autofocus>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Unlock Report</button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer text-center">
                            <a href="{{ route('home') }}" class="btn btn-link">Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
