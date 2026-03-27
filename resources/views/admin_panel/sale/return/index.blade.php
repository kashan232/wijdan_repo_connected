@extends('admin_panel.layout.app')
@section('content')

<div class="container-fluid">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Sale Returns</h5>
            <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm  text-center">
                Back
            </a>
        </div>

        <div class="card-body">
            @if($salesReturns->isEmpty())
            <div class="alert alert-info text-center m-0">
                No sale returns found.
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Inv</th>
                            <th>Items</th>
                            <th>Customer</th>
                            <th>Total Items</th>
                            <th>Total Net</th>
                            <th>Return Note</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesReturns as $return)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $return->sale->invoice_no ?? 'N/A' }}</td>
                            <td>
                                @php
                                $products = explode(',', $return->product ?? '');
                                @endphp
                                @if(!empty($products))
                                @foreach($products as $p)
                                <span class="badge bg-light text-dark border mb-1">{{ trim($p) }}</span><br>
                                @endforeach
                                @else
                                N/A
                                @endif

                            </td>
                            <td>{{ $return->sale->customer_relation->customer_name ?? 'N/A' }}</td>

                            <td class="text-center">{{ $return->total_items }}</td>
                            <td class="text-end">{{ number_format($return->total_net, 2) }}</td>
                            <td>{{ $return->return_note }}</td>
                            <td class="text-center">{{ $return->created_at->format('d-m-Y') }}</td>
                            <td class="text-center">
                                <span class="badge bg-danger">Returned</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('saleReturn.invoice', $return->id) }}" target="_blank" class="btn btn-sm btn-info text-white">
                                    Receipt
                                </a>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>
</div>

@endsection