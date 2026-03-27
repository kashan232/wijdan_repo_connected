@extends('admin_panel.layout.app')
@section('content')

<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            ➕ Edit Warehouse Stock
        </h5>
        <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm">
            Back
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouse_stocks.update', $warehouseStock->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label>Warehouse</label>
                <select name="warehouse_id" class="form-control" required>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $warehouseStock->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->warehouse_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label>Product</label>
                <select name="product_id" class="form-control" required>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ $warehouseStock->product_id == $product->id ? 'selected' : '' }}>
                            {{ $product->item_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label>Quantity</label>
                <input type="number" name="quantity" value="{{ $warehouseStock->quantity }}" class="form-control" required>
            </div>
            {{--  <div class="mb-3">
                <label>Price</label>
                <input type="number" step="0.01" name="price" value="{{ $warehouseStock->price }}" class="form-control">
            </div>  --}}
            <div class="mb-3">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control">{{ $warehouseStock->remarks }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Stock</button>
        </form>
    </div>
</div>

@endsection
