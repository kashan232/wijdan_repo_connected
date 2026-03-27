@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>List of Purchase Returns</h3>
                    </div>
                    <div class="border mt-1 shadow rounded bg-white">
                        <div class="table-responsive mt-4 mb-5 p-3">
                            <table id="purchasereturn-table" class="table">
                                <thead class="bg-info text-white">
                                    <tr>
                                        <th>ID</th>
                                        <th>Purchase Invoice #</th>
                                        <th>Invoice #</th>
                                        <th>Vendor</th>
                                        <th>Warehouse</th>
                                        <th>Return Date</th>
                                        <th>Products</th> {{-- separated --}}
                                        <th>Qty</th>
                                        <th>Bill Amount</th>
                                        <th>Item Discount</th>
                                        <th>Extra Discount</th>
                                        <th>Net Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($returns as $return)
                                    <tr>
                                        <td>{{ $return->id }}</td>
                                        <td>{{ $return->purchase->invoice_no ?? 'N/A' }}</td>
                                        <td>{{ $return->return_invoice }}</td>
                                        <td>{{ $return->vendor->name ?? 'N/A' }}</td>
                                        <td>{{ $return->warehouse->warehouse_name ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($return->return_date)->format('Y-m-d') }}</td>

                                        {{-- 🟩 Show returned items and quantities --}}
                                        <td class="text-start align-top">
                                            @php
                                            $prodNames = [];
                                            $qtys = [];
                                            @endphp

                                            @foreach($return->items as $item)
                                            @php
                                            $prodNames[] = $item->product->item_name ?? 'N/A';
                                            $qtys[] = $item->qty + 0; // +0 ensures 2.00 -> 2, but 1.5 -> 1.5
                                            @endphp
                                            @endforeach

                                            @if(count($prodNames))
                                            @foreach($prodNames as $p)
                                            <div>{{ $p }}</div>
                                            @endforeach
                                            @else
                                            <div>-</div>
                                            @endif
                                        </td>

                                        {{-- 🟩 QTY COLUMN --}}
                                        <td class="text-center align-top">
                                            @if(count($qtys))
                                            @foreach($qtys as $q)
                                            <div><strong>{{ $q }}</strong></div>
                                            @endforeach
                                            @else
                                            <div>0</div>
                                            @endif
                                        </td>

                                        <td>{{ number_format($return->bill_amount, 2) }}</td>
                                        <td>{{ number_format($return->item_discount, 2) }}</td>
                                        <td>{{ number_format($return->extra_discount, 2) }}</td>
                                        <td><strong>{{ number_format($return->net_amount, 2) }}</strong></td>

                                        <td>
                                            <div class="btn-group" role="group" aria-label="Actions">
                                                <a href="{{ route('purchasereturn.invoice', $return->id) }}" class="btn btn-sm btn-danger text-white">Invoice</a>
                                            </div>
                                        </td>
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
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#purchasereturn-table').DataTable({
            "pageLength": 10,
            "lengthMenu": [5, 10, 25, 50, 100],
            "order": [
                [0, 'desc']
            ],
            "language": {
                "search": "Search Purchsase:",
                "lengthMenu": "Show _MENU_ entries"
            }
        });
    });
</script>
@endsection