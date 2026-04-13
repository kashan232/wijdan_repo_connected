@extends('admin_panel.layout.app')
 <style>
        .image-preview-wrapper {
            position: relative;
            display: inline-block;
        }

        .image-preview-wrapper img {
            max-width: 100%;
            border-radius: 8px;
        }

        .clear-image-btn {
            position: absolute;
            top: 2px;
            /* thoda neeche laane ke liye */
            right: 18px;
            width: 28px;
            height: 28px;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease-in-out;
        }

        .clear-image-btn:hover {
            background-color: rgba(255, 0, 0, 0.8);
        }


        .uploader {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        #preview {
            width: 395px;
            height: 325px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f9f9f9;
        }

        #preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: block;
        }

        .info {
            font-size: 14px;
            color: #444;
        }

        button {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #bbb;
            background: white;
            cursor: pointer;
        }
    </style>
@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 ">
                        <div class="page-header">
                            <div class="page-title">
                                <h4>Edit Product</h4>
                                <h6>Manage Product Details</h6>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                @if (session()->has('success'))
                                    <div class="alert alert-success">
                                        <strong>Success!</strong> {{ session('success') }}.
                                    </div>
                                @endif
                                <form action="{{ route('product.update', $product->id) }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')

                                    <div class="row g-3">
                                        <!-- Image Upload -->
                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="image-preview-wrapper">
                                                    <img id="preview"
                                                        src="{{ asset('uploads/products/' . $product->image) }}"
                                                        alt="Product Image">
                                                    <button type="button" class="clear-image-btn"
                                                        id="clearImageBtn">&times;</button>
                                                </div>
                                                <input type="file" id="imageInput" name="image">
                                            </div>
                                        </div>

                                        <!-- Product Info -->
                                        <div class="col-md-8">
                                            <div class="row g-3">
                                                <!-- Product Name -->
                                                <div class="col-sm-4">
                                                    <label class="form-label">Product Name</label>
                                                    <input type="text" name="product_name" class="form-control"
                                                        value="{{ $product->item_name }}" required>
                                                </div>

                                                <!-- Category -->
                                                {{-- <div class="col-sm-4">
                                                    <label class="form-label">Category</label>
                                                    <select name="category_id" id="category">
                                                        <option value="">Select Category</option>
                                                        @foreach ($categories as $category)
                                                           
                                                            <option value="{{ $category->id }}"
                                                                {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                                                {{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div> --}}

                                                 <div class="col-sm-4">
                                                    <label class="form-label">Category</label>
                                                    <select name="category_id" id="category" class="form-select" required>
                                                        @foreach ($categories as $category)
                                                            <option value="{{ $category->id }}"
                                                                {{ $category->id == $product->category_id ? 'selected' : '' }}>
                                                                {{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                 <div class="col-sm-4">
                                                    <label class="form-label">Sub Category</label>
                                                    <select name="sub_category_id" id="subcategory" class="form-select" required>
                                                        @foreach ($subcategories as $subcategory)
                                                            <option value="{{ $subcategory->id }}"
                                                                {{ $subcategory->id == $product->sub_category_id ? 'selected' : '' }}>
                                                                {{ $subcategory->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                               

                                                <!-- Brand -->
                                                <div class="col-sm-4">
                                                    <label class="form-label">Brand</label>
                                                    <select name="brand_id" class="form-select" required>
                                                        @foreach ($brands as $brand)
                                                            <option value="{{ $brand->id }}"
                                                                {{ $brand->id == $product->brand_id ? 'selected' : '' }}>
                                                                {{ $brand->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
<!-- Item Code -->
<div class="col-sm-4">
    <label class="form-label">Item Code</label>
    <input type="text" name="item_code" class="form-control"
        value="{{ $product->item_code }}" readonly>
</div>
                                                <!-- Barcode -->
                                                <div class="col-sm-4">
                                                    <label for="barcodeInput" class="form-label">Barcode</label>
                                                    <div class="input-group">
                                                        <input type="text" id="barcodeInput" name="barcode_path"
                                                            class="form-control" placeholder="Enter or Generate Barcode"
                                                            value="{{ $product->barcode_path }}">
                                                        <button type="button" id="generateBarcodeBtn"
                                                            class="btn btn-primary">Generate</button>
                                                    </div>
                                                </div>

                                                <!-- Unit -->
                                                <div class="col-sm-4">
                                                    <label class="form-label">Unit (UOM)</label>
                                                    <select name="unit" class="form-select" required>
                                                        <option value="Piece" {{ $product->unit_id == 'Piece' ? 'selected' : '' }}>Piece</option>
                                                        <option value="Meter" {{ $product->unit_id == 'Meter' ? 'selected' : '' }}>Meter</option>
                                                        <option value="Yards" {{ $product->unit_id == 'Yards' ? 'selected' : '' }}>Yards</option>
                                                    </select>
                                                </div>
                                                <!-- Stock -->
                                                <div class="col-sm-4">
                                                    <label class="form-label">Stock</label>
                                                    <input type="number" name="Stock" class="form-control"
                                                        value="{{ $product->initial_stock }}">
                                                </div>

                                                <!-- Alert Quantity -->
                                                <div class="col-sm-4">
                                                    <label class="form-label">Alert Quantity</label>
                                                    <input type="number" name="alert_quantity" class="form-control"
                                                        value="{{ $product->alert_quantity }}">
                                                </div>

                                                <!-- Wholesale Price -->
                                                <div class="col-sm-4">
                                                    <label class="form-label">Wholesale Price</label>
                                                    <input type="number" name="wholesale_price" class="form-control"
                                                        value="{{ $product->wholesale_price }}">
                                                </div>

                                                <!-- Retail Price -->
                                                <div class="col-sm-4">
                                                    <label class="form-label">Retail Price</label>
                                                    <input type="number" name="retail_price" class="form-control"
                                                        value="{{ $product->price }}">
                                                </div>

                                                <!-- Note -->
                                                <div class="col-sm-8">
                                                    <label class="form-label">Note</label>
                                                    <textarea name="note" class="form-control" rows="2">{{ $product->note }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit -->
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary w-100 py-2">Save Changes</button>
                                    </div>
                                </form>

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
           document.getElementById('imageInput').addEventListener('change', function(event) {
    let file = event.target.files[0];
    if (file) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.getElementById('preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
            document.getElementById('clearImageBtn').style.display = 'inline-block';
        }
        reader.readAsDataURL(file);
    }
});

document.getElementById('clearImageBtn').addEventListener('click', function() {
    document.getElementById('imageInput').value = "";
    let preview = document.getElementById('preview');
    preview.src = "{{ asset('uploads/products/' . $product->image) }}"; // Purani image wapas
    this.style.display = 'none';
});

        </script>
@endsection
