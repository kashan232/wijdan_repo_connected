@extends('admin_panel.layout.app')
@section('content')
<style>
    div.dataTables_wrapper div.dataTables_length select {
        width: 75px !important
    }

    .barcode {
        width: 100%;
        max-width: 180px;
    }

    td.text-center {
        vertical-align: middle;
    }

    .bottom--impo th {
        padding-right: 28px !important;
        font-size: 22px !important;
        color: #000 !important;
        text-align: center;
    }

    .h-5 {
        width: 30px;
    }

    .leading-5 {
        padding: 20px 0px;
    }

    .leading-5 span:nth-child(3) {
        color: red;
        font-weight: 500;
    }
</style>
<div class="card shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">📦 Product List</h5>
            <small class="text-muted">Manage all products here</small>



        </div>
         <div class="d-flex align-items-center">
             @if (auth()->user()->can(' Discount.index') || auth()->user()->email === 'admin@admin.com')
             <a href="{{ route('discount.index') }}" class="btn btn-success btn-sm">View Discount</a>
             @endif
             <a href="create_prodcut" class="btn btn-primary btn-sm">Add Product</a>
             <button type="button" class="btn btn-info btn-sm text-white" data-toggle="modal" data-target="#bulkImportModal">📥 Bulk Import</button>
             <button type="button" class="btn btn-warning btn-sm text-white" data-toggle="modal" data-target="#bulkUpdateModal">🔄 Bulk Update</button>
             <button id="createDiscountBtn" class="btn btn-primary btn-sm">Create Discount</button>
             <a id="exportAllBtn" class="btn btn-outline-secondary btn-sm" href="javascript:void(0)">⬇ Export All</a>
             <button id="exportSelectedBtn" class="btn btn-outline-primary btn-sm" type="button">⬇ Export Selected</button>
             <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm px-3">Back</a>
         </div>


    </div>

    <div class="card-body">
        @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            ✅ {{ session('success') }}
            <button type="button" class="btn-close" data-dismiss="alert"></button>
        </div>
        @endif

        @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            ❌ {{ session('error') }}
            <button type="button" class="btn-close" data-dismiss="alert"></button>
        </div>
        @endif

        @if (session()->has('import_errors'))
        <div class="alert alert-warning alert-dismissible fade show">
            <strong>Import warnings:</strong>
            <ul class="mb-0 mt-2">
                @foreach (session('import_errors') as $importError)
                <li>{{ $importError }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-dismiss="alert"></button>
        </div>
        @endif

        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" id="productSearch" class="form-control" placeholder="🔍 Search product (code, name, barcode, brand)">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle nowrap" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Barcode</th>
                        <th>Image</th>
                        <th>Category<br>Sub-Category</th>
                        <th>Item Name</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th class="text-center">Brand Name</th>
                        <th>Note</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="productTable">
                    @foreach ($products as $key => $product)
                    <tr>
                        <td><input type="checkbox" class="selectProduct" value="{{ $product->id }}"></td>
                        <td>{{ $products->firstItem() + $key }}</td>
                        <td class="fw-bold">{{ $product->item_code }}</td>
                        <td class="fw-bold text-primary">{{ $product->barcode_path ?? '—' }}</td>

                        <td>
                            @if ($product->image)
                            <img src="{{ asset('public/uploads/products/'.$product->image) }}" width="50" height="50">
                            @else
                            <span class="badge bg-secondary">No Img</span>
                            @endif
                        </td>

                        <td>
                            <strong>{{ $product->category_relation->name ?? '-' }}</strong><br>
                            <small>{{ $product->sub_category_relation->name ?? '-' }}</small>
                        </td>

                        <td>{{ $product->item_name }}</td>
                        <td>{{ $product->unit_id ?? '-' }}</td>
                        <td>
                            @if($product->discountProduct)
                            @php
                            $discount = $product->discountProduct;
                            $discountedPrice = $discount->final_price; // ✅ already stored in DB
                            @endphp

                            <span class="badge bg-danger mb-1">
                                {{ $discount->discount_percentage }}% OFF
                            </span><br>

                            <del class="text-muted">
                                PKR {{ number_format($product->price, 2) }}
                            </del><br>

                            <strong class="text-success">
                                PKR {{ number_format($discountedPrice, 2) }}
                            </strong>
                            @else
                            PKR {{ number_format($product->price, 2) }}
                            @endif
                        </td>

                        <td>{{ $product->stock->qty ?? '-' }}</td>
                        <td>{{ $product->brand->name ?? '-' }}</td>
                        <td>{{ $product->note ?? '-' }}</td>

                        <td>
                            <a href="{{ route('products.edit',$product->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            <a href="{{ route('product.barcode',$product->id) }}" class="btn btn-sm btn-success">Barcode</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>

            </table>

            <div class="py-5">
                {{ $products->appends(request()->input())->links() }}
            </div>


            <style>
                #datatable tbody tr.low-stock td {
                    background-color: #ffcccc !important;
                }

                #datatable tbody tr.low-stock:hover td {
                    background-color: #ffb3b3 !important;
                }
            </style>

        </div>
    </div>
</div>

{{-- add product modal --}}

<div class="modal fade bd-example-modal-lg" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('store-product') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-control" name="category_id" id="categorySelect" required>
                                <option value="">Select Category</option>
                                @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sub-Category</label>
                            <select class="form-control" name="sub_category_id" id="subCategorySelect">
                                <option value="">Select Sub-Category</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="item_name" required>
                        </div>
                    </div>

                    {{-- <div class="row"> --}}
                    {{-- <div class="col-md-6 mb-3">
                            <label class="form-label">Size</label> --}}
                    {{-- <select class="form-control" name="size" id="sizeSelect" required>
                                <option value="">Select Size</option>

                            </select> --}}
                    {{-- </div> --}}
                    {{-- <div class="col-md-6 mb-3">
                            <label class="form-label">Carton Quantity</label>
                            <input type="number" class="form-control" name="carton_quantity" id="carton_quantity" required>
                        </div> --}}
                    {{-- </div> --}}
                    {{-- <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pieces per Carton</label>
                            <input type="number" class="form-control" name="pcs_in_carton" id="pieces_per_carton" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Initial Stock</label>
                            <input type="number" class="form-control" name="initial_stock" id="initial_stock">
                        </div>
                    </div> --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Alert Quantity</label>
                            <input type="number" class="form-control" name="alert_quantity" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" name="wholesale_price" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sale Price</label>
                            <input type="number" step="0.01" class="form-control" name="retail_price" required>
                        </div>
                    </div>


                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk Import Modal --}}
<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-labelledby="bulkImportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkImportModalLabel">📥 Bulk Product Import (CSV)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Upload a CSV file to import products in bulk. Client sirf <strong>category</strong> aur <strong>sub-category</strong> ke names likhein (ID ki zaroorat nahi).
                        Agar barcode pehle se hai to product update ho jayega.
                    </p>
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <a href="{{ route('products.import.template') }}" class="btn btn-outline-success btn-sm">
                            ⬇ Download CSV Template
                        </a>
                        <a href="{{ route('products.import.categories') }}" class="btn btn-outline-info btn-sm">
                            ⬇ Category List (Names)
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select CSV File</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <ul class="small text-muted mb-0">
                        <li>Required: <strong>Item Name</strong></li>
                        <li>Category: <strong>Category</strong> (e.g. Women, Men, Kids)</li>
                        <li>Sub-Category: <strong>Sub-Category</strong> (e.g. Unstitch Casual)</li>
                        <li>Unit: Piece, Meter, or Yards</li>
                        <li>Stock: <strong>Shop Qty</strong> aur <strong>W/H Qty</strong></li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Import Products</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk Update Modal --}}
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUpdateModalLabel">🔄 Bulk Product Update (CSV)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('products.update.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        CSV upload karein — pehle <strong>Barcode</strong> se product dhundha jayega.
                        Agar barcode galat hai ya nahi mila to <strong>Item Name</strong> se match hoga.
                        Sirf ye 3 fields update hongi: <strong>Retail Price</strong>, <strong>Shop Qty</strong>, <strong>W/H Qty</strong>.
                    </p>
                    <div class="mb-3">
                        <a href="{{ route('products.update.template') }}" class="btn btn-outline-success btn-sm">
                            ⬇ Download Update Template
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select CSV File</label>
                        <input type="file" name="update_file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <ul class="small text-muted mb-0">
                        <li>Match: <strong>Barcode</strong> (pehle), phir <strong>Item Name</strong> (agar barcode na mile)</li>
                        <li>Update: <strong>Retail Price</strong>, <strong>Shop Qty</strong>, <strong>W/H Qty</strong></li>
                        <li>Dono galat hon to row skip ho jayegi</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm text-white">Update Products</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

@section('scripts')


<script>
    $(document).ready(function() {

        let searchTimer = null;

        // 🔍 SEARCH
        $('#productSearch').on('keyup', function() {
            clearTimeout(searchTimer);
            let query = $(this).val();

            searchTimer = setTimeout(() => {
                fetchProducts(query);
            }, 400); // debounce
        });

        // 📄 PAGINATION
        $(document).on('click', '#paginationLinks a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            fetchProducts($('#productSearch').val(), url);
        });

        // 🚀 FETCH FUNCTION
        function fetchProducts(search = '', url = null) {
            if (!url) {
                url = "{{ route('product') }}"; // ✔️ correct
            }

            $.ajax({
                url: url,
                data: {
                    search: search
                },
                success: function(res) {
                    $('#productTable').html($(res).find('#productTable').html());
                    $('#paginationLinks').html($(res).find('#paginationLinks').html());
                }
            });
        }

        // Select/Deselect all checkboxes
        $('#selectAll').click(function() {
            $('.selectProduct').prop('checked', this.checked);
        });

        // On "Create Discount" click
        $('#createDiscountBtn').click(function() {
            var selected = [];
            $('.selectProduct:checked').each(function() {
                selected.push($(this).val());
            });

            if (selected.length === 0) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: "Please select at least one product!",

                });
                return;
            }

            // Redirect with product IDs as query param
            window.location.href = "{{ route('discount.create') }}" + "?products=" + selected.join(
                ',');
        });
    });
</script>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        JsBarcode(".barcode").init();
    });
    document.addEventListener("DOMContentLoaded", function() {
        let cartonQuantityInput = document.getElementById("carton_quantity");
        let piecesPerCartonInput = document.getElementById("pieces_per_carton");
        let initialStockInput = document.getElementById("initial_stock");

        function updateInitialStock() {
            let cartonQuantity = parseInt(cartonQuantityInput.value) || 0;
            let piecesPerCarton = parseInt(piecesPerCartonInput.value) || 0;
            initialStockInput.value = cartonQuantity * piecesPerCarton;
        }

        cartonQuantityInput.addEventListener("input", updateInitialStock);
        piecesPerCartonInput.addEventListener("input", updateInitialStock);
    });

    $(document).ready(function() {
        // Add Product Modal: Fetch Subcategories on Category Change
        $('#categorySelect').change(function() {
            var categoryId = $(this).val();

            $('#subCategorySelect').html('<option value="">Loading...</option>');

            if (categoryId) {
                $.ajax({
                    url: "/get-subcategories/" + categoryId,

                    type: "GET",
                    data: {
                        category_id: categoryId
                    },
                    success: function(data) {
                        $('#subCategorySelect').html(
                            '<option value="">Select Sub-Category</option>');
                        $.each(data, function(key, subCategory) {
                            $('#subCategorySelect').append('<option value="' +
                                subCategory.id + '">' + subCategory.name +
                                '</option>');
                        });
                    },
                    error: function() {
                        alert('Error fetching subcategories.');
                    }
                });
            } else {
                $('#subCategorySelect').html('<option value="">Select Sub-Category</option>');
            }
        });

        // Edit Product Modal: Fetch Subcategories when Category is Changed
        $('#edit_category').change(function() {
            var categoryId = $(this).val();
            $('#edit_sub_category').html('<option value="">Loading...</option>');

            if (categoryId) {
                $.ajax({
                    url: "/get-subcategories/" + categoryId,

                    type: "GET",
                    data: {
                        category_id: categoryId
                    },
                    success: function(data) {
                        $('#edit_sub_category').html(
                            '<option value="">Select Sub-Category</option>');
                        $.each(data, function(key, subCategory) {
                            $('#edit_sub_category').append('<option value="' +
                                subCategory.sub_category_name + '">' +
                                subCategory.sub_category_name + '</option>');
                        });
                    },
                    error: function() {
                        alert('Error fetching subcategories.');
                    }
                });
            } else {
                $('#edit_sub_category').html('<option value="">Select Sub-Category</option>');
            }
        });
    });
</script>

<!-- SheetJS library (browser build) -->
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

<script>
    (function() {
        // helper: clean numeric column strings like "PKR 1,234" -> 1234
        function extractNumber(str) {
            if (str === null || str === undefined) return '';
            str = String(str).trim();
            // remove PKR or non-digit except . and - and comma
            str = str.replace(/PKR/ig, '').replace(/[^\d\.\-\,]/g, '');
            // replace comma thousand sep and keep decimal dot
            if (str.indexOf(',') !== -1 && str.indexOf('.') === -1) {
                // if only commas, remove them
                str = str.replace(/,/g, '');
            } else {
                // remove commas used as thousand seps
                str = str.replace(/,/g, '');
            }
            // If empty after cleaning
            if (str === '') return '';
            var n = Number(str);
            return isNaN(n) ? str : n;
        }

        // read a table row and return array of cell values matching your visible columns
        function parseRow($tr) {
            // Column order in your table: checkbox | # | Item Code | Barcode | Image | Category/Sub | Item Name | Unit | Price | Stock | Brand | Note | Action
            // We'll export: Item Code, Barcode, Category, Sub-Category, Item Name, Unit, Price, Stock, Brand, Note
            var $tds = $tr.find('td');

            var itemCode = $tds.eq(2).text().trim();
            var barcode = $tds.eq(3).text().trim();
            var cat = $tds.eq(5).find('strong').text().trim() || '';
            var sub = $tds.eq(5).find('small').text().trim() || '';
            var itemName = $tds.eq(6).text().trim();
            var unit = $tds.eq(7).text().trim();
            var price = extractNumber($tds.eq(8).text().trim());
            var stock = extractNumber($tds.eq(9).text().trim());
            var brand = $tds.eq(10).text().trim();
            var note = $tds.eq(11).text().trim();

            return [itemCode, barcode, cat, sub, itemName, unit, price, stock, brand, note];
        }

        function buildWorkbook(dataArray, sheetName) {
            var ws = XLSX.utils.aoa_to_sheet(dataArray);
            var wscols = [
                { wpx: 90 }, // item code
                { wpx: 80 }, // barcode
                { wpx: 110 }, // cat
                { wpx: 110 }, // sub
                { wpx: 160 }, // item name
                { wpx: 60 }, // unit
                { wpx: 70 }, // price
                { wpx: 60 }, // stock
                { wpx: 110 }, // brand
                { wpx: 200 } // note
            ];
            ws['!cols'] = wscols;
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, sheetName || 'Products');
            return wb;
        }

        function downloadWorkbook(wb, filename) {
            XLSX.writeFile(wb, filename);
        }

        var HEADERS = ['Item Code', 'Barcode', 'Category', 'Sub-Category', 'Item Name', 'Unit', 'Price', 'Stock Qty', 'Brand', 'Note'];

        // Export All button
        document.getElementById('exportAllBtn')?.addEventListener('click', function() {
            var btn = this;
            var originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Exporting All...';
            btn.style.pointerEvents = 'none';

            fetch("{{ route('products.export_all') }}")
                .then(res => res.json())
                .then(data => {
                    var out = [HEADERS];
                    data.forEach(function(row) {
                        out.push(row);
                    });
                    var wb = buildWorkbook(out, 'Products_All');
                    var ts = new Date().toISOString().replace(/[:\-T]/g, '').slice(0, 14);
                    downloadWorkbook(wb, 'products_all_' + ts + '.xlsx');
                    
                    btn.innerHTML = originalText;
                    btn.style.pointerEvents = 'auto';
                })
                .catch(err => {
                    console.error(err);
                    alert("Error fetching all products. Please try again.");
                    btn.innerHTML = originalText;
                    btn.style.pointerEvents = 'auto';
                });
        });

        // Export Selected button
        document.getElementById('exportSelectedBtn')?.addEventListener('click', function() {
            var selectedBoxes = Array.from(document.querySelectorAll('.selectProduct:checked'));
            if (selectedBoxes.length === 0) {
                Swal.fire ? Swal.fire({
                    icon: 'info',
                    title: 'No selection',
                    text: 'Please select at least one product.'
                }) : alert('Please select at least one product.');
                return;
            }
            var out = [HEADERS];
            var $ = window.jQuery;
            selectedBoxes.forEach(function(cb) {
                var tr = cb.closest('tr');
                if (!tr) return;
                var rowData = parseRow($(tr));
                out.push(rowData);
            });
            var wb = buildWorkbook(out, 'Products_Selected');
            var ts = new Date().toISOString().replace(/[:\-T]/g, '').slice(0, 14);
            downloadWorkbook(wb, 'products_selected_' + ts + '.xlsx');
        });

        @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: "{!! session('success') !!}",
            timer: 4000,
            showConfirmButton: false
        });
        @endif

        @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: "{!! session('error') !!}",
        });
        @endif

        @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: `
                <ul style="text-align: left;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            `
        });
        @endif

        @if(session('import_errors'))
        Swal.fire({
            icon: 'warning',
            title: 'Warnings',
            html: `
                <ul style="text-align: left;">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            `
        });
        @endif

    })();
</script>


@endsection
