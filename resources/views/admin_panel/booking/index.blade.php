@extends('admin_panel.layout.app')
@section('content')

<div class="container-fluid">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0">BOOKINGS</h5>
            <span class="fw-bold text-dark">
                <a href="{{ route('bookings.create') }}" class="btn btn-primary">Add Booking</a>
                <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm  text-center">
                    Back
                </a>
            </span>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Reference</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Discount</th>
                        <th>Total</th>
                        <th>Advance</th> <!-- NEW -->
                        <th>Remaining</th> <!-- NEW -->
                        <th>Booking Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                    @php
                    // safe numeric values
                    $totalNet = floatval($booking->total_net ?? 0);
                    // prefer explicit advance_payment, fallback to cash (some records used cash)
                    $advance = floatval($booking->advance_payment ?? $booking->cash ?? 0);
                    // remaining amount (could be negative if advance > total)
                    $remaining = $totalNet - $advance;
                    @endphp
                    <tr>
                        <td>{{ $booking->id }}</td>
                        <td>{{ $booking->customer_relation->customer_name ?? 'Walk-in Customer' }}</td>
                        <td>{{ $booking->reference }}</td>

                        {{-- Product names vertically --}}
                        <td>
                            @php
                            $productNames = [];
                            $productIds = explode(',', $booking->product);
                            foreach ($productIds as $pid) {
                            $product = \App\Models\Product::find($pid);
                            if ($product) $productNames[] = e($product->item_name);
                            }
                            @endphp
                            {!! implode('<br>', $productNames) !!}
                        </td>

                        {{-- Quantities vertically --}}
                        <td>
                            @php $qtys = explode(',', $booking->qty); @endphp
                            {!! implode('<br>', $qtys) !!}
                        </td>

                        {{-- Prices vertically --}}
                        <td>
                            @php
                            $prices = explode(',', $booking->per_price);
                            foreach ($prices as &$p) { $p = number_format((float)$p, 2); }
                            @endphp
                            {!! implode('<br>', $prices) !!}
                        </td>

                        {{-- Discounts vertically --}}
                        <td>
                            @php
                            $discounts = explode(',', $booking->per_discount);
                            foreach ($discounts as &$d) { $d = number_format((float)$d, 2); }
                            @endphp
                            {!! implode('<br>', $discounts) !!}
                        </td>

                        {{-- Total --}}
                        <td>{{ number_format($totalNet, 2) }}</td>

                        {{-- Advance (new column) --}}
                        <td>{{ number_format($advance, 2) }}</td>

                        {{-- Remaining (new column) --}}
                        <td>
                            @if($remaining <= 0)
                                <span class="text-success">{{ number_format($remaining, 2) }}</span>
                                @else
                                <span class="text-danger">{{ number_format($remaining, 2) }}</span>
                                @endif
                        </td>

                        <td>{{ $booking->created_at->format('d-m-Y') }}</td>

                        <td>
                            {{-- Receipt --}}
                            <a href="{{ route('booking.receipt', $booking->id) }}"
                                target="_blank"
                                class="btn btn-sm btn-outline-secondary">
                                Receipt
                            </a>

                            @if($booking->sale_date != null)
                            <span class="btn btn-sm btn-dark">Booking Sale</span>
                            @else
                            <a href="{{ route('sales.from.booking', $booking->id) }}" class="btn btn-sm btn-success">Confirm</a>

                            <form action="{{ route('bookings.destroy', $booking->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this booking?')">Delete</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>

            </table>

        </div>

    </div>
</div>

@endsection
