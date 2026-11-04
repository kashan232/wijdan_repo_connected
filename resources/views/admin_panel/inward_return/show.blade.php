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
                                <h6><strong>Original Gatepass:</strong> {{ $inwardReturn->inwardGatepass ? $inwardReturn->inwardGatepass->invoice_no : 'N/A' }} (GP#: {{ $inwardReturn->inwardGatepass ? $inwardReturn->inwardGatepass->gatepass_no : 'N/A' }})</h6>
                                @if($inwardReturn->return_reason)
                                    <h6><strong>Reason:</strong> {{ $inwardReturn->return_reason }}</h6>
                                @endif
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <h5 class="text-muted">Vendor Info:</h5>
                                <h6><strong>Vendor Name:</strong> {{ $inwardReturn->vendor ? $inwardReturn->vendor->name : 'N/A' }}</h6>
                                <h6><strong>Received In:</strong> <span class="text-capitalize">{{ $inwardReturn->inwardGatepass ? $inwardReturn->inwardGatepass->receive_type : 'N/A' }}</span></h6>
                                @if($inwardReturn->transport)
                                    <h6><strong>Transport:</strong> {{ $inwardReturn->transport }} {{ $inwardReturn->vehicle_no ? '('.$inwardReturn->vehicle_no.')' : '' }}</h6>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;">#</th>
                                        <th scope="col">Item Details</th>
                                        <th scope="col">Item Code</th>
                                        <th scope="col" class="text-center">Returned Qty</th>
                                        <th scope="col" class="text-end">Price</th>
                                        <th scope="col" class="text-end">Discount</th>
                                        <th scope="col" class="text-end">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $calcSubtotal = 0;
                                        $calcItemDisc = 0;
                                    @endphp
                                    @foreach($inwardReturn->items as $index => $item)
                                    @php
                                        $price = (float)($item->price ?? 0);
                                        $qty = (float)($item->qty ?? 0);
                                        $gross = $price * $qty;
                                        $disc = (float)($item->discount_amount ?? 0);
                                        $lineTotal = (float)($item->total ?? ($gross - $disc));

                                        $calcSubtotal += $gross;
                                        $calcItemDisc += $disc;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td style="text-align: left;">
                                            <strong>{{ $item->product ? $item->product->item_name : 'N/A' }}</strong>
                                            @if($item->product && $item->product->brand)
                                                <div class="small text-muted">{{ $item->product->brand->name }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $item->product ? $item->product->item_code : 'N/A' }}</td>
                                        <td class="text-center fw-bold">{{ $qty }} {{ $item->product && $item->product->unit ? $item->product->unit->name : '' }}</td>
                                        <td class="text-end">Rs. {{ number_format($price, 2) }}</td>
                                        <td class="text-end text-danger">
                                            @if($disc > 0)
                                                - Rs. {{ number_format($disc, 2) }}
                                                @if(($item->discount_type ?? 'pkr') == 'percent')
                                                    <span class="small text-muted">({{ $item->discount_value }}%)</span>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">Rs. {{ number_format($lineTotal, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Return Financial Summary Table -->
                        @php
                            $subtotal = (float)($inwardReturn->subtotal ?: $calcSubtotal);
                            $itemDiscount = (float)($inwardReturn->item_discount ?: $calcItemDisc);
                            $manualDiscount = (float)($inwardReturn->discount ?? 0);
                            $overallDiscount = (float)($inwardReturn->overall_discount ?: ($itemDiscount + $manualDiscount));
                            $netAmount = (float)($inwardReturn->net_amount ?: max(0, $subtotal - $overallDiscount));
                        @endphp
                        <div class="row justify-content-end mt-4">
                            <div class="col-sm-6 col-md-5">
                                <table class="table table-bordered table-sm mb-0">
                                    <tr>
                                        <th class="table-light">Subtotal (Gross):</th>
                                        <td class="text-end fw-bold">Rs. {{ number_format($subtotal, 2) }}</td>
                                    </tr>
                                    @if($itemDiscount > 0)
                                    <tr>
                                        <th class="table-light">Item-wise Discount:</th>
                                        <td class="text-end text-danger">- Rs. {{ number_format($itemDiscount, 2) }}</td>
                                    </tr>
                                    @endif
                                    @if($manualDiscount > 0)
                                    <tr>
                                        <th class="table-light">Manual / Return Discount:</th>
                                        <td class="text-end text-danger">- Rs. {{ number_format($manualDiscount, 2) }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th class="table-light">Overall Discount:</th>
                                        <td class="text-end text-danger fw-bold">- Rs. {{ number_format($overallDiscount, 2) }}</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <th class="fs-6">Net Return Amount:</th>
                                        <td class="text-end fs-6 fw-bold">Rs. {{ number_format($netAmount, 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if($inwardReturn->remarks)
                        <div class="mt-4 pt-2 border-top">
                            <h6><strong>Remarks:</strong> {{ $inwardReturn->remarks }}</h6>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>
@endsection
