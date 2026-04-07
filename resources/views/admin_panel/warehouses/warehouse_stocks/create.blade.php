@extends('admin_panel.layout.app')
@section('content')

<div class="card shadow-sm border-0">
     <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            ➕ Add Warehouse Stock
        </h5>
        <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm">
            Back
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouse_stocks.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label>Warehouse</label>
                <select name="warehouse_id" class="form-control" required>
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label>Product</label>
                <select name="product_id" class="form-control" required>
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->item_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" required>
            </div>
            {{-- <div class="mb-3">
                <label>Price</label>
                <input type="number" step="0.01" name="price" class="form-control">
            </div>  --}}
            <div class="mb-3">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Add Stock</button>
        </form>
    </div>
</div>

@endsection
@section('scripts')

<script>
    $(document).ready(function() {
        $('select[name="product_id"]').select2({
            placeholder: "Search or Select Product",
            allowClear: true,
            width: '100%'
        });
    });
</script>
@endsection