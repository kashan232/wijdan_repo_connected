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
                                                        class="form-control">
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
                                                        class="form-control">
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
                                                    <select name="brand_id" class="form-control brand-select" required>
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
                                                        <div id="barcodePreview" class="mt-2 text-center"></div>
                                                    </div>
                                                </div>


                                                <div class="col-sm-4">
                                                    <label for="color-select">Color Name</label>
                                                    <select name="color[]" id="color-select" class="form-control"
                                                        multiple="multiple" style="width: 100%">
                                                        <option value="Mix" selected>Mix</option>
                                                        <option value="Black">Black</option>
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
                                                    <select name="unit" class="form-control" required>
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
                                                        class="form-control" value="0" step="any">
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">Wholesale Price</label>
                                                    <input type="number" name="wholesale_price"
                                                        class="form-control" value="Null" step="any">
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">Retail Price</label>
                                                    <input type="number" name="retail_price"
                                                        class="form-control" value="Null" step="any">
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
                <form action="{{ route('manual.category') }}" method="POST" id="categoryForm">
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
                <form action="{{ route('manual.subcategory') }}" method="POST" id="subcategoryForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Category Name</label>
                            <select name="category_id" class="form-control">
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
                <form action="{{ route('manual.Brand') }}" method="POST" id="brandForm">
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
            // --- Select2 Initialization ---
            const select2Options = {
                width: '100%',
                allowClear: true
            };

            $('.brand-select').select2({
                ...select2Options,
                placeholder: "Select Brand"
            });

            $('select[name="unit"]').select2({
                ...select2Options,
                placeholder: "Select Unit"
            });

            $('#color-select').select2({
                ...select2Options,
                tags: true,
                placeholder: "Select or type color(s)"
            });

            // --- Smooth Tab Navigation for Select2 ---
            
            // 1. Open Select2 on focus (Tab in)
            $(document).on('focus', '.select2-selection--single, .select2-selection--multiple', function(e) {
                const $select = $(this).closest(".select2-container").siblings('select:enabled');
                if ($select.length && !$select.data('select2').isOpen()) {
                    $select.select2('open');
                }
            });

            // 2. Handle Tab key in Select2 search field (Tab out)
            $(document).on('keydown', '.select2-search__field', function(e) {
                if (e.which === 9) { // Tab key
                    const $container = $(this).closest('.select2-container');
                    const $select = $container.prev('select');
                    
                    if ($select.length) {
                        $select.select2('close');
                        
                        // Move to next focusable element
                        setTimeout(() => {
                            const $focusables = $(':focusable');
                            const nextIndex = $focusables.index($select) + 1;
                            if (nextIndex < $focusables.length) {
                                $focusables.eq(nextIndex).focus();
                            }
                        }, 50);
                        e.preventDefault();
                    }
                }
            });

            // --- AJAX Form Submission for Modals ---
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
                            if (response.success || response.id) { // Handle different response formats
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message || 'Saved successfully',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                form[0].reset();
                                $(modalId).modal('hide');
                                
                                 // Refresh for Category, Subcategory, or Brand
                                 if (formId === '#categoryForm' || formId === '#subcategoryForm' || formId === '#brandForm') {
                                     location.reload();
                                 }
                            } else {
                                Swal.fire('Error', 'Something went wrong.', 'error');
                            }
                        },
                        error: function(xhr) {
                            let errors = xhr.responseJSON.errors;
                            let message = '';
                            if (errors) {
                                for (let key in errors) {
                                    message += errors[key][0] + '<br>';
                                }
                            } else {
                                message = xhr.responseJSON.message || 'Server error';
                            }
                            Swal.fire('Validation Error', message, 'error');
                        }
                    });
                });
            }

            handleAjaxFormSubmit('#categoryForm', '#categoryModal');
            handleAjaxFormSubmit('#subcategoryForm', '#subcategoryModal');
            handleAjaxFormSubmit('#brandForm', '#cuModal');
            // Unit form (using modelModal as per previous code)
            handleAjaxFormSubmit('#unitForm', '#modelModal');

            // --- Barcode Generation ---
            $('#generateBarcodeBtn').on('click', function() {
                let currentValue = $('#barcodeInput').val().trim();
                let url = '{{ route("generate-barcode-image") }}';
                
                if (currentValue !== "") {
                    url += '?code=' + encodeURIComponent(currentValue);
                }

                $.getJSON(url, function(data) {
                    $('#barcodeInput').val(data.barcode_number);
                    $('#barcodePreview').html(`<img src="${data.barcode_image}" alt="Barcode" class="img-fluid border p-2 mt-2 shadow-sm" style="max-height: 80px;">`);
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Barcode generation failed', 'error');
                });
            });

            // --- Image Preview ---
            $('#imageInput').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#preview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);
                }
            });

            $('#clearImageBtn').on('click', function() {
                $('#preview').attr('src', '');
                $('#imageInput').val('');
            });

            // --- Cascading Dropdowns ---
            $('#category-dropdown').on('change', function() {
                var categoryId = $(this).val();
                var $subcategory = $('#subcategory-dropdown');

                if (categoryId) {
                    $.ajax({
                        url: '/get-subcategories/' + categoryId,
                        type: "GET",
                        dataType: "json",
                        success: function(data) {
                            $subcategory.empty();
                            $subcategory.append('<option value="">Select Subcategory</option>');
                            $.each(data, function(key, value) {
                                $subcategory.append('<option value="' + value.id + '">' + value.name + '</option>');
                            });
                        }
                    });
                } else {
                    $subcategory.empty().append('<option value="">Select Subcategory</option>');
                }
            });

            // --- Prevent Enter Key Submission except on Submit Button ---
            $('#productForm').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    const el = e.target;
                    const tag = el.tagName.toLowerCase();
                    if (tag !== 'textarea' && !$(el).hasClass('select2-search__field') && el.id !== 'submitProductBtn') {
                        e.preventDefault();
                        // Optional: move to next field on Enter
                        const $focusables = $(':focusable');
                        const nextIndex = $focusables.index(el) + 1;
                        if (nextIndex < $focusables.length) {
                            $focusables.eq(nextIndex).focus();
                        }
                    }
                }
            });
        });

        // Focusable polyfill
        if (!$.expr[':'].focusable) {
            $.expr[':'].focusable = function(element) {
                var nodeName = element.nodeName.toLowerCase(),
                    tabIndex = $(element).attr('tabindex');
                return (/^(input|select|textarea|button|object)$/.test(nodeName) ?
                    !element.disabled :
                    'a' === nodeName ?
                    element.href || !isNaN(tabIndex) :
                    !isNaN(tabIndex)
                ) && $(element).is(':visible');
            };
        }
    </script>
    @endsection
