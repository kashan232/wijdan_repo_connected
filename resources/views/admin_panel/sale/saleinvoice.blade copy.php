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
                                    <p class="text-muted mb-0">Phone: 022786661 | Email: info@wijdanstore.com</p>
                                </div>

                                <!-- Invoice Info -->
                                <div class="text-end">
                                    <h2 class="fw-bold text-uppercase text-success mb-2">SALES INVOICE</h2>
                                    <p class="mb-1"><strong>Invoice #:</strong> {{ $sale->id }}</p>
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
                            <!-- Items Table -->
                            <div class="table-responsive mb-4">
                                <table class="table table-striped table-hover align-middle text-center">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th class="text-start">Product</th>
                                            <th>Code</th>
                                            <th>Brand</th>
                                            <th>Unit</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Discount</th>
                                            <th>Total</th>
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
                                            <td>{{ $item['qty'] }}</td>
                                            <td>{{ number_format($item['price'], 2) }}</td>
                                            <td>{{ number_format($item['discount'], 2) }}</td>
                                            <td class="fw-bold">{{ number_format($item['total'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <td colspan="8" class="text-end fw-bold">Total Items</td>
                                            <td class="fw-bold">{{ $sale->total_items }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-end fw-bold">Grand Total</td>
                                            <td class="fw-bold text-success">{{ number_format($sale->total_bill_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-end fw-bold">Extra Discount</td>
                                            <td>{{ number_format($sale->total_extradiscount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-end fw-bold h5">Net Amount</td>
                                            <td class="fw-bold text-primary h5">{{ number_format($sale->total_net, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-start fw-bold">Cash: {{ number_format($sale->cash, 2) }}</td>
                                            <td colspan="5" class="text-end fw-bold">Change: {{ number_format($sale->change, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="9" class="text-start fw-bold">
                                                In Words: <em>{{ $sale->total_amount_Words }}</em>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <!-- Footer -->
                            <div class="border-top pt-4 d-flex justify-content-between no-print">
                                <p class="text-muted small mb-0">Thank you for the visit
!</p>
                                <a href="javascript:window.print()" class="btn btn-success btn-sm">
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