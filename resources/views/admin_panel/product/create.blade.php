<!-- meta tags and other links -->

@extends('admin_panel.layout.app')
@section('content')
<!-- Select2 CSS -->

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
<!-- navbar-wrapper end -->
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="body-wrapper">
                {{-- @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
            </div>
            @endif --}}

            <div class="bodywrapper__inner">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <!-- Left: Page Title -->
                    <h6 class="page-title mb-0">Add Product</h6>

                    <!-- Center: Buttons -->
                    <div class="d-flex justify-content-center flex-wrap gap-2 flex-grow-1">
                        {{-- <button class="btn btn-md btn--warning py-2" ></button> --}}
                        <!-- Category Button -->
                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                            data-target="#categoryModal">
                            <i class="la la-plus-circle"></i> Add Category
                        </button>

                        <!-- Subcategory Button -->
                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                            data-target="#subcategoryModal">
                            <i class="las la-plus"></i> Add Subcategory
                        </button>

                        {{-- <button type="button" class="btn btn-sm btn-outline-primary cuModalBtn"
                                    data-modal_title="Add New Model" data-toggle="modal" data-target="#modelModal">
                                    <i class="las la-plus"></i>Add Units </button> --}}
                        {{-- <button class="btn btn-md btn-outline--primary py-2 "></button> --}}
                        <button type="button" class="btn btn-sm btn-outline-primary cuModalBtn"
                            data-modal_title="Add New Brand" data-toggle="modal" data-target="#cuModal">
                            <i class="las la-plus"></i> Add Brand
                        </button>

                        {{-- <button type="button" class="btn btn-sm btn-outline-primary cuModalBtn"
                                    data-modal_title="Add New Brand">
                                    <i class="las la-plus"></i>Add Brand </button>  --}}

                        <a class="btn btn-md btn-outline-primary py-2 " href="{{ url('/home') }}"
                            class="btn btn-md btn-outline--primary py-2">
                            <i class="la la-tachometer-alt"></i> Go To Dashboard
                        </a>
                    </div>
                    <!-- Right: Back Button -->
                    <div class="d-flex">
                        <a href="{{ route('product') }}" class="btn btn-sm btn-outline-primary">
                            <i class="la la-undo"></i> Back
                        </a>
                    </div>
                </div>
                <div class="row mb-none-30">
                    <div class="col-lg-12 col-md-12 mb-30">
                        <div class="card">
                            <div class="card-body">
                                @if (session()->has('success'))
                                <div class="alert alert-success">
                                    <strong>Success!</strong> {{ session('success') }}.
                                </div>
                                @endif

                                <form action="{{ route('store-product') }}" method="POST"
                                    enctype="multipart/form-data" id="productForm">
                                    @csrf
                                    <div class="row g-3">
                                        @if ($errors->any())
                                        <div class="col-12">
                                            <div class="alert alert-danger py-2">
                                                <strong>Validation Errors:</strong>
                                                <ul class="mb-0 ps-3">
                                                    @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                        @endif
                                        <!-- Image Upload -->
                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="image-preview-wrapper">
                                                    <img id="preview" src="" alt="No Image Selected">
                                                    <button type="button" class="clear-image-btn"
                                                        id="clearImageBtn">&times;</button>
                                                </div>

                                                <input type="file" id="imageInput" name="image">
                                            </div>
                                        </div>

                                        <!-- Product Info -->
                                        <div class="col-md-8">
                                            <div class="row g-3">

                                                <div class="col-sm-4">
                                                    <label class="form-label">Product Name</label>
                                                    <input type="text" name="product_name" class="form-control"
                                                        required>
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">Category</label>
                                                    <select id="category-dropdown" name="category_id"
                                                        class="form-select">
                                                        <option value="">Select Category</option>
                                                        @foreach ($categories as $cat)
                                                        <option value="{{ $cat->id }}">{{ $cat->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">Sub Category</label>
                                                    <select id="subcategory-dropdown" name="sub_category_id"
                                                        class="form-select">
                                                        <option value="">Select Subcategory</option>
                                                    </select>
                                                </div>

                                                {{-- <div class="col-sm-4">
                                                            <label class="form-label">Item Code</label>
                                                            <input type="text" name="sku" class="form-control"
                                                                value="Null">
                                                        </div> --}}

                                                <div class="col-sm-4">
                                                    <label class="form-label">Brand</label>
                                                    <select name="brand_id[]" class="form-select brand-select" multiple required>
                                                        @foreach ($brands as $brand)
                                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-sm-4">
                                                    <div class="mb-3">
                                                        <label for="barcodeInput" class="form-label">Barcode</label>
                                                        <div class="input-group">
                                                            <input type="text" id="barcodeInput"
                                                                name="barcode_path" class="form-control"
                                                                placeholder="Enter or Generate Barcode">
                                                            <button type="button" id="generateBarcodeBtn"
                                                                class="btn btn-primary">Generate Barcode</button>
                                                        </div>
                                                        {{-- <div id="barcodePreview" class="mt-3"></div> --}}
                                                    </div>
                                                </div>


                                                <div class="col-sm-4">
                                                    <label for="color-select">Color Name</label>
                                                    <select name="color[]" id="color-select" class="form-select"
                                                        multiple="multiple" style="width: 100%">
                                                        <option value="Black" selected>Black</option>
                                                        <option value="White">White</option>
                                                        <option value="Red">Red</option>
                                                        <option value="Blue">Blue</option>
                                                    </select>
                                                </div>




                                                <!-- Hidden barcode value -->
                                                {{-- <input type="hidden" name="barcode" val    ue="{{ $barcode }}"> --}}

                                                <!-- Barcode display -->

                                                <div class="col-sm-4">
                                                    <label class="form-label">Unit (UOM)</label>
                                                    <select name="unit" class="form-select" required>
                                                        <option value="" disabled selected>Select One
                                                        </option>
                                                        <option value="Piece">Piece</option>
                                                        <option value="Meter">Meter</option>
                                                        <option value="Yards">Yards</option>
                                                    </select>
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">Stock in (Piece)</label>
                                                    <input type="number"
                                                        name="Stock"
                                                        class="form-control"
                                                        placeholder="0"
                                                        step="0.01"
                                                        min="0">
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">Alert Quantity</label>
                                                    <input type="number" name="alert_quantity"
                                                        class="form-control" value="0">
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">Wholesale Price</label>
                                                    <input type="number" name="wholesale_price"
                                                        class="form-control" value="Null">
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">Retail Price</label>
                                                    <input type="number" name="retail_price"
                                                        class="form-control" value="Null">
                                                </div>

                                                <div class="col-sm-8">
                                                    <label class="form-label">Note</label>
                                                    <textarea name="note" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="mt-4">
                                        <button type="submit" id="submitProductBtn"
                                            class="btn btn-primary w-100 py-2">Submit
                                            Product</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- bodywrapper__inner end -->
        </div><!-- body-wrapper end -->
    </div>

    {{-- category modal  --}}
    <div id="categoryModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span class="type"></span> <span>Add Category</span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('manual.category') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="redirect_url" value="{{ route('product') }}">

                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary h-45 w-100">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- SubCategor modal  --}}
    <div id="subcategoryModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span class="type"></span> <span>Add Category</span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('manual.subcategory') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Category Name</label>
                            <select name="category_id" class="form-select">
                                {{-- <option selected disabled>Select Category</option> --}}
                                @foreach ($categories as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sub-Category Name</label>
                            <input type="text" id="sub_category" name="sub_category" class="form-control"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary h-45 w-100">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- start model modal  --}}
    <div id="modelModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span class="type"></span> <span>Add Models</span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('manual.Unit') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary h-45 w-100">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- start brand modal --}}
    <!--Create Update Modal -->
    <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span class="type"></span> <span>Add Brand</span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('manual.Brand') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary h-45 w-100">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endsection


    @section('scripts')

    <script>
        $(document).ready(function() {
            $('.brand-select').select2({
                placeholder: "Select Brand",
                allowClear: true
            });
        });
        $(document).ready(function() {
            function handleAjaxFormSubmit(formId, modalId) {
                $(formId).on('submit', function(e) {
                    e.preventDefault();

                    let form = $(this);
                    let url = form.attr('action');
                    let data = form.serialize();

                    $.ajax({
                        type: "POST",
                        url: url,
                        data: data,
                        success: function(response) {
                            if (response.success) {
                                alert(response
                                    .message); // Ya toastr, sweetalert use kar sakte ho
                                form[0].reset();
                                $(modalId).modal('hide');
                            } else {
                                alert("Something went wrong.");
                            }
                        },
                        error: function(xhr) {
                            let errors = xhr.responseJSON.errors;
                            let message = 'Validation Error:\n';
                            for (let key in errors) {
                                message += errors[key][0] + '\n';
                            }
                            alert(message);
                        }
                    });
                });
            }

            // Call for each form
            handleAjaxFormSubmit('#categoryForm', '#categoryModal');
            handleAjaxFormSubmit('#subcategoryForm', '#subcategoryModal');
            handleAjaxFormSubmit('#unitForm', '#modelModal');
            handleAjaxFormSubmit('#brandForm', '#cuModal');
        });
    </script>

    <script>
        (function() {
            const form = document.getElementById('productForm');

            // 1) Enter key se submit rok do (textarea & select2 search me allow)
            form.addEventListener('keydown', function(e) {
                if (e.key !== 'Enter') return;

                const el = e.target;
                const tag = el.tagName.toLowerCase();

                // allow newline in textarea
                if (tag === 'textarea') return;

                // allow select2 search box to work
                if (el.classList && el.classList.contains('select2-search__field')) return;

                // baki sab me prevent (inputs, selects, etc.)
                e.preventDefault();
            });

            // 2) Sirf submit button click par hi submit allow
            form.addEventListener('submit', function(e) {
                // e.submitter modern browsers me mil jata hai; safety fallback bhi
                const byButton = e.submitter && e.submitter.id === 'submitProductBtn';
                if (!byButton) {
                    e.preventDefault();
                }
            });
        })();
    </script>


    <script>
        document.getElementById('generateBarcodeBtn').addEventListener('click', function() {
            let currentValue = document.getElementById('barcodeInput').value.trim();

            // Agar manually enter kiya hai to uska barcode show karo
            if (currentValue !== "") {
                fetch('/generate-barcode-image?code=' + currentValue)
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('barcodePreview').innerHTML =
                            `<img src="${data.barcode_image}" alt="Barcode" class="img-fluid border p-2">`;
                    });
            }
            // Agar empty hai to auto-generate karo
            else {
                fetch('{{ route('generate-barcode-image') }}')
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('barcodeInput').value = data.barcode_number;
                        document.getElementById('barcodePreview').innerHTML =
                            `<img src="${data.barcode_image}" alt="Barcode" class="img-fluid border p-2">`;
                    });
            }
        });
    </script>

    <script>
        const imageInput = document.getElementById('imageInput');
        const preview = document.getElementById('preview');
        const clearImageBtn = document.getElementById('clearImageBtn');

        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        clearImageBtn.addEventListener('click', function() {
            preview.src = "";
            imageInput.value = "";
        });



        $('#category-dropdown').on('change', function() {
            // alert("sd");
            var categoryId = $(this).val();

            if (categoryId) {
                $.ajax({
                    url: '/get-subcategories/' + categoryId,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#subcategory-dropdown').empty();
                        $('#subcategory-dropdown').append(
                            '<option selected disabled>Select Subcategory</option>');
                        $.each(data, function(key, value) {
                            $('#subcategory-dropdown').append('<option value="' + value.id +
                                '">' + value.name + '</option>');
                        });
                    }
                });
            } else {
                $('#subcategory-dropdown').empty();
            }
        });
    </script>


    <script>
        document.getElementById('imageUpload').addEventListener('change', function(event) {
            let file = event.target.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('previewImage');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    document.getElementById('removeImage').style.display = 'inline-block';
                }
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('removeImage').addEventListener('click', function() {
            document.getElementById('imageUpload').value = "";
            document.getElementById('previewImage').style.display = 'none';
            this.style.display = 'none';
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#color-select').select2({
                tags: true,
                placeholder: "Select or type color(s)",
                allowClear: true,
                width: 'resolve'
            });
        });
    </script>
    @endsection
