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

                                <!-- Invoice Info -->
                                <div class="text-end">
                                    <h2 class="fw-bold text-uppercase text-primary mb-2">PURCHASE INVOICE</h2>
                                    <p class="mb-1"><strong>Invoice #:</strong> {{ $purchase->invoice_no }}</p>
                                    <p class="mb-1">
                                        <strong>Date:</strong>
                                        {{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Vendor & Warehouse -->
                            <div class="row mb-4">
                                <!-- Vendor & Warehouse -->
                                <div class="row mb-4">
                                    <!-- Vendor -->
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100 bg-light">
                                            <h6 class="fw-bold text-uppercase text-primary mb-3">
                                                <i class="bi bi-person-lines-fill me-1"></i> Vendor Details
                                            </h6>
                                            <ul class="list-unstyled mb-0">
                                                <li><strong>Name:</strong> {{ $purchase->vendor->name ?? 'N/A' }}</li>
                                                <li><strong>Phone:</strong> {{ $purchase->vendor->phone ?? 'N/A' }}</li>
                                                <li><strong>Address:</strong> {{ $purchase->vendor->address ?? 'N/A' }}</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Warehouse -->
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100 bg-light">
                                            <h6 class="fw-bold text-uppercase text-primary mb-3">
                                                <i class="bi bi-building me-1"></i> Warehouse Details
                                            </h6>
                                            <ul class="list-unstyled mb-0 text-md-end">
                                                <li><strong>Name:</strong> {{ $purchase->warehouse->warehouse_name ?? 'N/A' }}</li>
                                                <li><strong>Location:</strong> {{ $purchase->warehouse->location ?? 'N/A' }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive mb-4">
                                <table class="table table-striped table-hover align-middle text-center">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th class="text-start">Product</th>
                                            <th>Unit</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Discount</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchase->items as $index => $item)
                                        <tr>
                                            <td>{{ $index+1 }}</td>
                                            <td class="text-start">{{ $item->product->item_name ?? 'N/A' }}</td>
                                            <td>{{ $item->unit }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td>{{ number_format($item->price, 2) }}</td>
                                            <td>{{ number_format($item->item_discount, 2) }}</td>
                                            <td class="fw-bold">{{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <td colspan="6" class="text-end fw-bold">Subtotal</td>
                                            <td>{{ number_format($purchase->subtotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end fw-bold">Extra Charges</td>
                                            <td>{{ number_format($purchase->extra_cost, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end fw-bold h5">Grand Total</td>
                                            <td class="fw-bold text-success h5">{{ number_format($purchase->net_amount, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <!-- Footer -->
                            <div class="border-top pt-4 d-flex justify-content-between no-print">
                                <p class="text-muted small mb-0">This is a computer-generated invoice. No signature required.</p>
                                <a href="javascript:window.print()" class="btn btn-danger btn-sm">
                                    <i class="bi bi-printer"></i> Print Invoice
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* Print Styling */
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