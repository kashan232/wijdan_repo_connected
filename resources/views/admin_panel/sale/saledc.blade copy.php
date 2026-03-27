@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card shadow border-0">
                        <div class="card-body p-5">

                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                                <!-- Logo & Company Info -->
                                <div>
                                    <img src="{{ asset('assets/images/wijdan_logo.png') }}" alt="Company Logo" height="70">
                                    <h4 class="mt-3 mb-1 fw-bold">Wijdan Exclusive Store</h4>
                                    <p class="text-muted mb-1">Chandni market, Salahuddin Road, Cantonment, Hyderabad</p>
                                    <p class="text-muted mb-0">Phone: +92 327 9226901 | Email: info@wijdanstore.com</p>
                                </div>

                                <!-- DC Info -->
                                <div class="text-end">
                                    <h2 class="fw-bold text-uppercase text-primary mb-2">DELIVERY CHALLAN</h2>
                                    <p class="mb-1"><strong>DC #:</strong> DC-{{ $sale->id }}</p>
                                    <p class="mb-1"><strong>Reference:</strong> {{ $sale->reference ?? 'N/A' }}</p>
                                    <p class="mb-0"><strong>Date:</strong> {{ \Carbon\Carbon::parse($sale->created_at)->format('d M Y, h:i A') }}</p>
                                </div>
                            </div>

                            <!-- Customer Info -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-uppercase text-secondary">Customer Details</h6>
                                    <p class="mb-1"><strong>Name:</strong> {{ $sale->customer_relation->customer_name ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Mobile:</strong> {{ $sale->customer_relation->mobile ?? 'N/A' }}</p>
                                    <p class="mb-0"><strong>Email:</strong> {{ $sale->customer_relation->email_address ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <h6 class="fw-bold text-uppercase text-secondary">Address</h6>
                                    <p class="mb-0">{{ $sale->customer_relation->address ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-hover align-middle text-center">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th class="text-start">Product</th>
                                            <th>Code</th>
                                            <th>Brand</th>
                                            <th>Unit</th>
                                            <th>Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($saleItems as $index => $item)
                                        <tr>
                                            <td>{{ $index+1 }}</td>
                                            <td class="text-start">{{ $item['item_name'] }}</td>
                                            <td>{{ $item['item_code'] }}</td>
                                            <td>{{ $item['brand'] }}</td>
                                            <td>{{ $item['unit'] }}</td>
                                            <td class="fw-bold">{{ $item['qty'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Footer -->
                            <div class="border-top pt-5 mt-5 d-flex justify-content-between no-print">
                                <div class="text-center">
                                    <p class="fw-bold mb-5">Received By:</p>
                                    <p class="border-top pt-2">Signature & Stamp</p>
                                </div>
                                <div class="text-end">
                                    <a href="javascript:window.print()" class="btn btn-primary btn-sm">
                                        <i class="bi bi-printer"></i> Print DC
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        body {
            background: #fff !important;
            font-size: 12pt;
        }
        table {
            border-collapse: collapse !important;
        }
    }
</style>
@endsection
