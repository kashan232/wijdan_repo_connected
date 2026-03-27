@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg border-0 rounded-lg mt-4">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <h3 class="mb-0 fw-bold text-white"><i class="fas fa-user-plus me-2"></i> Add New Customer</h3>
                        </div>
                        <div class="card-body p-5">
                            <form action="{{ route('customers.store') }}" method="POST">
                                @csrf

                                @if ($errors->any())
                                <div class="alert alert-danger shadow-sm rounded">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                        <li><i class="fas fa-exclamation-circle me-1"></i> {{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                <!-- Customer ID & Type Row -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-secondary">Customer ID</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="fas fa-id-badge text-primary"></i></span>
                                            <input type="text" class="form-control fw-bold text-dark" name="customer_id" readonly
                                                value="{{ $latestId }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-secondary">Customer Type</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="fas fa-users text-primary"></i></span>
                                            <select class="form-select" name="customer_type">
                                                <option value="Main Customer">Main Customer</option>
                                                <option value="Walking Customer">Walking Customer</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-secondary">Customer Category</label>
                                        <div class="d-flex align-items-center mt-2 border rounded p-2 bg-light">
                                            <div class="form-check form-check-inline me-3">
                                                <input class="form-check-input" type="radio" name="customer_category" id="catWholesaler" value="Wholesaler" checked>
                                                <label class="form-check-label fw-semibold" for="catWholesaler">Wholesaler</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="customer_category" id="catRetailer" value="Retailer">
                                                <label class="form-check-label fw-semibold" for="catRetailer">Retailer</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Name & Phone Row -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">Customer Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="fas fa-user text-primary"></i></span>
                                            <input type="text" class="form-control" name="customer_name" placeholder="Enter full name" value="{{ old('customer_name') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="fas fa-phone text-primary"></i></span>
                                            <input type="text" class="form-control" name="mobile" placeholder="03XXXXXXXXX" value="{{ old('mobile') }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- Address Row -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-secondary">Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-map-marker-alt text-primary"></i></span>
                                        <textarea rows="3" class="form-control" name="address" placeholder="Enter complete address">{{ old('address') }}</textarea>
                                    </div>
                                </div>

                                <!-- Opening Balance -->
                                <div class="mb-5">
                                    <label class="form-label fw-bold text-secondary">Opening Balance (Rs)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-wallet text-success"></i></span>
                                        <input type="number" step="0.01" class="form-control" name="opening_balance" placeholder="0.00" value="{{ old('opening_balance') }}">
                                    </div>
                                    <div class="form-text text-muted">Positive value = Receivable (Debit)</div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="{{ route('customers.index') }}" class="btn btn-light btn-lg px-4 fw-bold text-secondary">Cancel</a>
                                    <button class="btn btn-primary btn-lg px-5 fw-bold shadow-sm" type="submit">
                                        <i class="fas fa-save me-2"></i> Save Customer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
