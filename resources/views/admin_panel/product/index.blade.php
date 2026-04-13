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
        <div class="d-flex justify-content-between align-items-end gap-1">
            @if (auth()->user()->can(' Discount.index') || auth()->user()->email === 'admin@admin.com')
            <a href="{{ route('discount.index') }}" class="btn btn-success btn-sm">
                View Discount
            </a>
            @endif


            <a href="create_prodcut" class="btn btn-primary btn-sm  text-center">
                ➡ Add Product
            </a>

            <button id="createDiscountBtn" class="btn btn-primary btn-sm  text-center">
                ➡ Create Discount
            </button>
            <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm  text-center">
                Back
            </a>

            <a id="exportAllBtn" class="btn btn-outline-secondary btn-sm" href="javascript:void(0)">⬇ Export All</a>
            <button id="exportSelectedBtn" class="btn btn-outline-primary btn-sm" type="button">⬇ Export Selected</button>

        </div>

    </div>

    <div class="card-body">
        @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            ✅ {{ session('success') }}
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
                        <td>{{ $product->barcode_path ?? '—' }}</td>

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
                                PKR {{ number_format($product->price) }}
                            </del><br>

                            <strong class="text-success">
                                PKR {{ number_format($discountedPrice) }}
                            </strong>
                            @else
                            PKR {{ number_format($product->price) }}
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
            // Column order in your table: checkbox | # | Item Code | Barcode | Image | Category/Sub | Item Name | Unit | Price | Stock | Alert | Brand | Note | Action
            // We'll export: Item Code, Barcode, Category, Sub-Category, Item Name, Unit, Price, Stock, Alert Qty, Brand, Note
            var $tds = $tr.find('td');

            // map tds indexes according to your markup (first td is checkbox)
            var itemCode = $tds.eq(2).text().trim();
            var barcode = $tds.eq(3).text().trim();
            // category cell contains <strong>cat</strong><br><small>sub</small>
            var catHtml = $tds.eq(5).html() || '';
            var cat = $tds.eq(5).find('strong').text().trim() || '';
            var sub = $tds.eq(5).find('small').text().trim() || '';
            var itemName = $tds.eq(6).text().trim();
            var unit = $tds.eq(7).text().trim();
            var price = extractNumber($tds.eq(8).text().trim());
            var stock = extractNumber($tds.eq(9).text().trim());
            var alertQty = extractNumber($tds.eq(10).text().trim());
            var brand = $tds.eq(11).text().trim();
            var note = $tds.eq(12).text().trim();

            return [itemCode, barcode, cat, sub, itemName, unit, price, stock, alertQty, brand, note];
        }

        function buildWorkbook(dataArray, sheetName) {
            // dataArray = [ ['head1','head2',...], [r1c1, r1c2,...], ... ]
            var ws = XLSX.utils.aoa_to_sheet(dataArray);
            // adjust column widths a bit
            var wscols = [{
                    wpx: 90
                }, // item code
                {
                    wpx: 80
                }, // barcode
                {
                    wpx: 110
                }, // cat
                {
                    wpx: 110
                }, // sub
                {
                    wpx: 160
                }, // item name
                {
                    wpx: 60
                }, // unit
                {
                    wpx: 70
                }, // price
                {
                    wpx: 60
                }, // stock
                {
                    wpx: 60
                }, // alert
                {
                    wpx: 110
                }, // brand
                {
                    wpx: 200
                } // note
            ];
            ws['!cols'] = wscols;
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, sheetName || 'Products');
            return wb;
        }

        function downloadWorkbook(wb, filename) {
            // XLSX writeFile will trigger download
            XLSX.writeFile(wb, filename);
        }

        // Create header row
        var HEADERS = ['Item Code', 'Barcode', 'Category', 'Sub-Category', 'Item Name', 'Unit', 'Price', 'Stock Qty', 'Alert Qty', 'Brand', 'Note'];

        // Export All button
        document.getElementById('exportAllBtn')?.addEventListener('click', function() {
            var rows = Array.from(document.querySelectorAll('#datatable tbody tr'));
            if (!rows.length) {
                alert('No products found to export.');
                return;
            }
            var out = [HEADERS];
            rows.forEach(function(tr) {
                // skip rows that are perhaps template or hidden
                if (tr.style.display === 'none') return;
                // only parse actual <tr> with tds
                var $ = window.jQuery;
                if (!$) return;
                var rowData = parseRow($(tr));
                out.push(rowData);
            });
            var wb = buildWorkbook(out, 'Products_All');
            var ts = new Date().toISOString().replace(/[:\-T]/g, '').slice(0, 14);
            downloadWorkbook(wb, 'products_all_' + ts + '.xlsx');
        });

        // Export Selected button
        document.getElementById('exportSelectedBtn')?.addEventListener('click', function() {
            var selectedBoxes = Array.from(document.querySelectorAll('.selectProduct:checked'));
            if (selectedBoxes.length === 0) {
                // fallback to exporting visible rows
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

    })();
</script>


@endsection
