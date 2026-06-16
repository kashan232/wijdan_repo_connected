@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Inward Return Invoice</h4>
                    <div class="page-title-right">
                        <button onclick="window.print()" class="btn btn-primary"><i class="ri-printer-line"></i> Print</button>
                        <a href="{{ route('inward-returns.index') }}" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card" id="printArea">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <h5 class="text-muted">Return Info:</h5>
                                <h6><strong>Return Invoice No:</strong> {{ $inwardReturn->return_invoice }}</h6>
                                <h6><strong>Date:</strong> {{ \Carbon\Carbon::parse($inwardReturn->return_date)->format('d-M-Y') }}</h6>
                                <h6><strong>Original Gatepass:</strong> {{ $inwardReturn->inwardGatepass ? $inwardReturn->inwardGatepass->invoice_no : 'N/A' }}</h6>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <h5 class="text-muted">Vendor Info:</h5>
                                <h6><strong>Vendor Name:</strong> {{ $inwardReturn->vendor ? $inwardReturn->vendor->name : 'N/A' }}</h6>
                                <h6><strong>Received In:</strong> <span class="text-capitalize">{{ $inwardReturn->inwardGatepass ? $inwardReturn->inwardGatepass->receive_type : 'N/A' }}</span></h6>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Item Details</th>
                                        <th scope="col">Item Code</th>
                                        <th scope="col">Brand</th>
                                        <th scope="col">Returned Quantity</th>
                                        <th scope="col">Unit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($inwardReturn->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td style="text-align: left;">{{ $item->product ? $item->product->item_name : 'N/A' }}</td>
                                        <td>{{ $item->product ? $item->product->item_code : 'N/A' }}</td>
                                        <td>{{ $item->product && $item->product->brand ? $item->product->brand->name : 'N/A' }}</td>
                                        <td>{{ $item->qty }}</td>
                                        <td>{{ $item->product && $item->product->unit ? $item->product->unit->name : 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

@endsection

@section('scripts')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printArea, #printArea * {
            visibility: visible;
        }
        #printArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .page-title-box { display: none; }
    }
</style>
@endsection
