@extends('admin_panel.layout.app')
@section('content')
<style>
    .stock-page-header {
        background: #fff;
        border-bottom: 1px solid #eef1f4;
    }

    .stock-filter-box {
        background: #f8fafc;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }

    .stock-filter-box .form-label {
        font-size: 0.78rem;
        margin-bottom: 0.35rem;
        color: #495057;
    }

    .stock-summary-row {
        margin-bottom: 1.25rem;
    }

    .stock-summary-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
        height: 100%;
        overflow: hidden;
    }

    .stock-summary-card .card-body {
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stock-summary-card .summary-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.22);
    }

    .stock-summary-card .summary-content {
        min-width: 0;
    }

    .stock-summary-card .summary-label {
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        opacity: 0.92;
        margin-bottom: 0.2rem;
    }

    .stock-summary-card .summary-value {
        font-size: 1.55rem;
        font-weight: 700;
        margin-bottom: 0;
        line-height: 1.2;
        word-break: break-word;
    }

    .stock-summary-card .summary-amount {
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 0;
        opacity: 0.95;
    }

    .price-source-badge {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 999px;
        margin-top: 4px;
        text-transform: uppercase;
    }

    .price-source-badge.purchase,
    .price-source-badge.avg-purchase {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .price-source-badge.wholesale {
        background: #fef3c7;
        color: #b45309;
    }

    .price-source-badge.na {
        background: #f1f5f9;
        color: #64748b;
    }

    .bg-shop-stock {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
    }

    .bg-warehouse-stock {
        background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
        color: #fff;
    }

    .bg-total-stock {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        color: #fff;
    }

    .bg-piece-stock {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
    }

    .bg-meter-stock {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: #fff;
    }

    .bg-yard-stock {
        background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
        color: #fff;
    }

    .stock-summary-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 0.75rem;
    }

    .stock-table-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }

    .stock-table-card .table {
        margin-bottom: 0;
    }

    .stock-table-card thead th {
        background: #f1f5f9;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
        vertical-align: middle;
        border-bottom: 2px solid #e2e8f0;
    }

    .stock-table-card tbody td {
        vertical-align: middle;
        font-size: 0.875rem;
    }

    #brandFilter + .select2-container .select2-selection--multiple {
        min-height: 31px;
        border-color: #ced4da;
    }

    @media (max-width: 768px) {
        .card-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 8px;
        }

        .card-header .btn {
            width: 100%;
        }

        .stock-summary-card .summary-value {
            font-size: 1.25rem;
        }

        #stockTable th:nth-child(6),
        #stockTable td:nth-child(6),
        #stockTable th:nth-child(7),
        #stockTable td:nth-child(7),
        #stockTable th:nth-child(8),
        #stockTable td:nth-child(8),
        #stockTable th:nth-child(13),
        #stockTable td:nth-child(13) {
            display: none;
        }

        #stockTable {
            font-size: 12px;
        }
    }
</style>

<div class="card shadow-sm border-0">
    <div class="card-header stock-page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-0 fw-bold">Stock Status</h5>
            <small class="text-muted">Shop & warehouse stock overview with filters</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('warehouse_stocks.create') }}" class="btn btn-primary btn-sm">Add Stock</a>
            <a href="{{ url()->previous() }}" class="btn btn-outline-danger btn-sm">Back</a>
            <a id="exportStockAllBtn" class="btn btn-outline-secondary btn-sm" href="javascript:void(0)">Export All</a>
            <button id="exportStockSelectedBtn" class="btn btn-outline-primary btn-sm" type="button">Export Selected</button>
        </div>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('warehouse_stocks.index') }}" class="stock-filter-box row g-2 mb-4">
            <div class="col-12 col-md-2">
                <label class="form-label fw-bold">Stock Location:</label>
                <select name="stock_type" class="form-control form-control-sm">
                    <option value="all" {{ request('stock_type') == 'all' ? 'selected' : '' }}>All Locations</option>
                    <option value="shop" {{ request('stock_type') == 'shop' ? 'selected' : '' }}>Shop Only</option>
                    <option value="warehouse" {{ request('stock_type') == 'warehouse' ? 'selected' : '' }}>All Warehouses</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ request('stock_type') == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-bold">Search Product:</label>
                <input type="text" name="search" id="warehouseStockSearch" class="form-control form-control-sm" placeholder="Name, Code or Barcode..." value="{{ request('search') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">Start Date:</label>
                <input type="date" name="start_date" class="form-control form-control-sm"
                    value="{{ request('start_date') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">End Date:</label>
                <input type="date" name="end_date" class="form-control form-control-sm"
                    value="{{ request('end_date') }}">
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label d-none d-md-block">&nbsp;</label>
                <button type="submit" class="btn btn-success btn-sm w-100">Filter</button>
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label d-none d-md-block">&nbsp;</label>
                <a href="{{ route('warehouse_stocks.index') }}" class="btn btn-secondary btn-sm w-100">Reset</a>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label fw-bold">Unit:</label>
                <select name="unit" id="unitFilter" class="form-control form-control-sm">
                    <option value="">All Units</option>
                    <option value="Piece" {{ request('unit') == 'Piece' ? 'selected' : '' }}>Piece</option>
                    <option value="Meter" {{ request('unit') == 'Meter' ? 'selected' : '' }}>Meter</option>
                    <option value="Yard" {{ request('unit') == 'Yard' ? 'selected' : '' }}>Yard</option>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label fw-bold">Category:</label>
                <select name="category_id" id="categoryFilter" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label fw-bold">Sub Category:</label>
                <select name="subcategory_id" id="subcategoryFilter" class="form-control form-control-sm">
                    <option value="">All Sub Categories</option>
                    @foreach($subcategories as $subcategory)
                        <option value="{{ $subcategory->id }}" {{ request('subcategory_id') == $subcategory->id ? 'selected' : '' }}>
                            {{ $subcategory->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-bold">Brand:</label>
                <select name="brand_id[]" id="brandFilter" class="form-control form-control-sm" multiple>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ in_array($brand->id, (array) request('brand_id', [])) ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        <div id="stockSummaryCards">
        @if($isAdmin)
            <div class="stock-summary-section-title">Location Totals</div>
            <div class="row g-3 stock-summary-row">
                <div class="col-md-4">
                    <div class="card stock-summary-card bg-shop-stock">
                        <div class="card-body">
                            <div class="summary-icon"><i class="fas fa-store"></i></div>
                            <div class="summary-content">
                                <div class="summary-label">Total Shop Stock</div>
                                <p class="summary-value" id="totalShopStock">{{ number_format($stockTotals['total_shop'], 2) }}</p>
                                <p class="summary-amount">PKR {{ number_format($stockTotals['total_shop_value'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stock-summary-card bg-warehouse-stock">
                        <div class="card-body">
                            <div class="summary-icon"><i class="fas fa-warehouse"></i></div>
                            <div class="summary-content">
                                <div class="summary-label">Total Warehouse Stock</div>
                                <p class="summary-value" id="totalWarehouseStock">{{ number_format($stockTotals['total_warehouse'], 2) }}</p>
                                <p class="summary-amount">PKR {{ number_format($stockTotals['total_warehouse_value'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stock-summary-card bg-total-stock">
                        <div class="card-body">
                            <div class="summary-icon"><i class="fas fa-boxes"></i></div>
                            <div class="summary-content">
                                <div class="summary-label">Total Stock</div>
                                <p class="summary-value" id="totalStock">{{ number_format($stockTotals['total_stock'], 2) }}</p>
                                <p class="summary-amount">PKR {{ number_format($stockTotals['total_stock_value'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stock-summary-section-title mt-2">Unit Wise Totals</div>
            <div class="row g-3 stock-summary-row">
                <div class="col-md-4">
                    <div class="card stock-summary-card bg-piece-stock">
                        <div class="card-body">
                            <div class="summary-icon"><i class="fas fa-cubes"></i></div>
                            <div class="summary-content">
                                <div class="summary-label">Total Piece Stock</div>
                                <p class="summary-value" id="totalPieceStock">{{ number_format($stockTotals['piece'], 2) }}</p>
                                <p class="summary-amount">PKR {{ number_format($stockTotals['piece_value'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stock-summary-card bg-meter-stock">
                        <div class="card-body">
                            <div class="summary-icon"><i class="fas fa-ruler-horizontal"></i></div>
                            <div class="summary-content">
                                <div class="summary-label">Total Meter Stock</div>
                                <p class="summary-value" id="totalMeterStock">{{ number_format($stockTotals['meter'], 2) }}</p>
                                <p class="summary-amount">PKR {{ number_format($stockTotals['meter_value'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stock-summary-card bg-yard-stock">
                        <div class="card-body">
                            <div class="summary-icon"><i class="fas fa-ruler-combined"></i></div>
                            <div class="summary-content">
                                <div class="summary-label">Total Yard Stock</div>
                                <p class="summary-value" id="totalYardStock">{{ number_format($stockTotals['yard'], 2) }}</p>
                                <p class="summary-amount">PKR {{ number_format($stockTotals['yard_value'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        </div>

        @if(request('start_date') && request('end_date'))
        <div class="alert alert-info py-2 mb-3">
            Showing results from <strong>{{ request('start_date') }}</strong> to <strong>{{ request('end_date') }}</strong>
        </div>
        @endif

        @if($isAdmin)
        <div class="alert alert-light border py-2 mb-3 small">
            <strong>Stock Value:</strong> Purchase ki <strong>weighted average price</strong> use hoti hai (saari purchases ke price × qty ka average). Agar purchase na ho to product ki <strong>Wholesale Price</strong> use hoti hai.
        </div>
        @endif

        <div class="stock-table-card">
        <div class="table-responsive stock-table-wrapper">
            <table class="table table-bordered table-striped table-sm" id="stockTable">
                <thead>
                    <tr>
                        <th>NO#</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Product</th>
                        <th>Barcode</th>
                        <th>Unit</th>
                        <th>Brand</th>
                        @if($isAdmin)
                        <th>Cost Price</th>
                        @endif
                        <th>Shop Stock</th>
                        <th>Warehouse Stock</th>
                        <th>Total Stock</th>
                        @if($isAdmin)
                        <th>Stock Value</th>
                        @endif
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stocks as $stock)
                    @php
                        $totalQty = (float) $stock->shop_stock + (float) $stock->warehouse_stock;
                        $costPrice = $isAdmin ? (float) ($stock->cost_price ?? 0) : 0;
                        $stockValue = $totalQty * $costPrice;
                        $sourceClass = str_replace(' ', '-', strtolower($stock->price_source ?? 'na'));
                    @endphp
                    <tr>
                        <td>{{ ($stocks->currentPage() - 1) * $stocks->perPage() + $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($stock->created_at)->format('d M Y') }}</td>
                        <td>{{ $stock->warehouse_name ?? '— Shop —' }}</td>
                        <td>
                            <strong>{{ $stock->item_name }}</strong><br>
                            <small class="text-muted">{{ $stock->item_code }}</small>
                        </td>
                        <td>{{ $stock->barcode_path }}</td>
                        <td>{{ $stock->unit_id }}</td>
                        <td>{{ $stock->brand_name ?? 'N/A' }}</td>
                        @if($isAdmin)
                        <td class="text-end">
                            {{ number_format($costPrice, 2) }}<br>
                            <span class="price-source-badge {{ $sourceClass }}">{{ $stock->price_source ?? 'N/A' }}</span>
                        </td>
                        @endif

                        <td class="text-center">{{ number_format($stock->shop_stock, 2) }}</td>
                        <td class="text-center">{{ number_format($stock->warehouse_stock, 2) }}</td>
                        <td class="text-center fw-bold">{{ number_format($totalQty, 2) }}</td>
                        @if($isAdmin)
                        <td class="text-end fw-bold text-success">{{ number_format($stockValue, 2) }}</td>
                        @endif

                        <td>
                            @if($stock->warehouse_stock == 0 && $stock->shop_stock > 0)
                                Shop Only
                            @elseif($stock->warehouse_stock > 0 && $stock->shop_stock == 0)
                                Warehouse Only
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>

        <div class="mt-3" id="paginationLinks">
            {{ $stocks->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@endsection

@section('scripts')

<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

<script>
    $(document).ready(function() {
        $('#stockTable').DataTable({
            paging: false,
            searching: false,
            ordering: true,
            info: false,
            responsive: true,
            scrollX: false
        });

        $('#brandFilter').select2({
            placeholder: 'Search & select brand(s)',
            allowClear: true,
            width: '100%'
        });

        let searchTimer = null;

        function populateSubcategories(catId, selectedSubId) {
            let $sub = $('#subcategoryFilter');
            $sub.empty();
            $sub.append('<option value="">All Sub Categories</option>');

            if (!catId) {
                return;
            }

            $.ajax({
                url: "{{ route('fetch-subcategories', '') }}/" + catId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (Array.isArray(data)) {
                        data.forEach(function(s) {
                            const selected = String(selectedSubId) === String(s.id) ? 'selected' : '';
                            $sub.append(`<option value="${s.id}" ${selected}>${s.name}</option>`);
                        });
                    }
                }
            });
        }

        $('#categoryFilter').on('change', function() {
            populateSubcategories($(this).val(), '');
            triggerAjaxFetch();
        });

        $('#subcategoryFilter').on('change', triggerAjaxFetch);

        $('#unitFilter').on('change', triggerAjaxFetch);

        $('#brandFilter').on('change', triggerAjaxFetch);

        function getFilterData() {
            return {
                search: $('#warehouseStockSearch').val(),
                stock_type: $('select[name="stock_type"]').val(),
                start_date: $('input[name="start_date"]').val(),
                end_date: $('input[name="end_date"]').val(),
                category_id: $('#categoryFilter').val(),
                subcategory_id: $('#subcategoryFilter').val(),
                unit: $('#unitFilter').val(),
                brand_id: $('#brandFilter').val()
            };
        }

        function triggerAjaxFetch() {
            fetchStocks(getFilterData());
        }

        $('#warehouseStockSearch').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(triggerAjaxFetch, 400);
        });

        $('select[name="stock_type"], input[name="start_date"], input[name="end_date"]').on('change', function() {
            triggerAjaxFetch();
        });

        $('form').on('submit', function(e) {
            e.preventDefault();
            triggerAjaxFetch();
        });

        $(document).on('click', '#paginationLinks a', function(e) {
            e.preventDefault();
            fetchStocks(getFilterData(), $(this).attr('href'));
        });

        function fetchStocks(filters, url = null) {
            if (!url) {
                url = "{{ route('warehouse_stocks.index') }}";
            }

            $.ajax({
                url: url,
                data: filters,
                success: function(res) {
                    $('#stockTable tbody').html($(res).find('#stockTable tbody').html());
                    $('#paginationLinks').html($(res).find('#paginationLinks').html());
                    if ($('#stockSummaryCards').length) {
                        $('#stockSummaryCards').html($(res).find('#stockSummaryCards').html());
                    }
                }
            });
        }
    });
</script>

<script>
    $(function() {
        var isStockAdmin = @json($isAdmin);

        $('#stockTable tbody').on('click', 'tr', function(e) {
            // ignore clicks on interactive elements if any
            if ($(e.target).is('a,button,input,select,textarea')) return;
            $(this).toggleClass('row-selected');
            $(this).css('background-color', $(this).hasClass('row-selected') ? '#d9edf7' : '');
        });

        // helper to clean numeric text into Number where possible
        function toNumber(txt) {
            if (txt === null || txt === undefined) return '';
            var s = String(txt).trim();
            s = s.replace(/,/g, '').replace(/PKR/ig, '').replace(/[^\d\.\-]/g, '');
            if (s === '' || s === '-') return '';
            var n = Number(s);
            return isNaN(n) ? txt : n;
        }

        // parse a table row (returns array in export column order)
        function parseStockRow(tr) {
            var $tds = $(tr).find('td');
            var date = $tds.eq(1).text().trim();
            var location = $tds.eq(2).text().trim();
            var product = $tds.eq(3).text().trim();
            var barcode = $tds.eq(4).text().trim();
            var unit = $tds.eq(5).text().trim();
            var brand = $tds.eq(6).text().trim();

            if (isStockAdmin) {
                var costPrice = toNumber($tds.eq(7).text());
                var priceSource = $tds.eq(7).find('.price-source-badge').text().trim() || 'N/A';
                var shopStock = toNumber($tds.eq(8).text());
                var warehouseStock = toNumber($tds.eq(9).text());
                var totalStock = toNumber($tds.eq(10).text());
                var stockValue = toNumber($tds.eq(11).text());
                var remarks = $tds.eq(12).text().trim();
                return [date, location, product, barcode, unit, brand, costPrice, priceSource, shopStock, warehouseStock, totalStock, stockValue, remarks];
            }

            var shopStock = toNumber($tds.eq(7).text());
            var warehouseStock = toNumber($tds.eq(8).text());
            var totalStock = toNumber($tds.eq(9).text());
            var remarks = $tds.eq(10).text().trim();
            return [date, location, product, barcode, unit, brand, shopStock, warehouseStock, totalStock, remarks];
        }

        function buildAndDownload(rowsArray, filename, adminExport) {
            var header = adminExport
                ? ['Date', 'Location', 'Product', 'Barcode', 'Unit', 'Brand', 'Cost Price', 'Price Source', 'Shop Stock', 'Warehouse Stock', 'Total Stock', 'Stock Value', 'Remarks']
                : ['Date', 'Location', 'Product', 'Barcode', 'Unit', 'Brand', 'Shop Stock', 'Warehouse Stock', 'Total Stock', 'Remarks'];
            var aoa = [header].concat(rowsArray);
            var ws = XLSX.utils.aoa_to_sheet(aoa);
            ws['!cols'] = adminExport
                ? [
                    { wpx: 80 }, { wpx: 140 }, { wpx: 200 }, { wpx: 80 }, { wpx: 60 }, { wpx: 100 },
                    { wpx: 80 }, { wpx: 90 }, { wpx: 80 }, { wpx: 100 }, { wpx: 80 }, { wpx: 100 }, { wpx: 120 }
                ]
                : [
                    { wpx: 80 }, { wpx: 140 }, { wpx: 200 }, { wpx: 80 }, { wpx: 60 }, { wpx: 100 },
                    { wpx: 80 }, { wpx: 100 }, { wpx: 80 }, { wpx: 120 }
                ];
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'WarehouseStock');
            XLSX.writeFile(wb, filename);
        }

        // Export ALL
        $('#exportStockAllBtn').on('click', function() {
            var btn = $(this);
            var originalText = btn.html();
            btn.html('⏳ Exporting All...');
            btn.css('pointer-events', 'none');

            var type = $('select[name="stock_type"]').val() || 'all';
            var start = $('input[name="start_date"]').val() || '';
            var end = $('input[name="end_date"]').val() || '';
            var search = $('#warehouseStockSearch').val() || '';
            var category = $('#categoryFilter').val() || '';
            var subcategory = $('#subcategoryFilter').val() || '';
            var unit = $('#unitFilter').val() || '';
            var brands = $('#brandFilter').val() || [];

            var queryStr = "?stock_type=" + encodeURIComponent(type)
                + "&start_date=" + encodeURIComponent(start)
                + "&end_date=" + encodeURIComponent(end)
                + "&search=" + encodeURIComponent(search)
                + "&category_id=" + encodeURIComponent(category)
                + "&subcategory_id=" + encodeURIComponent(subcategory)
                + "&unit=" + encodeURIComponent(unit);

            brands.forEach(function(brandId) {
                queryStr += "&brand_id[]=" + encodeURIComponent(brandId);
            });

            fetch("{{ route('warehouse_stocks.export_all') }}" + queryStr)
                .then(res => res.json())
                .then(payload => {
                    var data = payload.rows || payload;
                    var adminExport = payload.is_admin !== undefined ? payload.is_admin : isStockAdmin;

                    if(data.length === 0) {
                        alert('No rows to export.');
                        btn.html(originalText);
                        btn.css('pointer-events', 'auto');
                        return;
                    }
                    var ts = new Date().toISOString().replace(/[:\-T]/g, '').slice(0, 14);
                    buildAndDownload(data, 'warehouse_stock_all_' + ts + '.xlsx', adminExport);
                    
                    btn.html(originalText);
                    btn.css('pointer-events', 'auto');
                })
                .catch(err => {
                    console.error(err);
                    alert("Error fetching stock data.");
                    btn.html(originalText);
                    btn.css('pointer-events', 'auto');
                });
        });

        // Export SELECTED
        $('#exportStockSelectedBtn').on('click', function() {
            var sel = [];
            $('#stockTable tbody tr.row-selected').each(function() {
                sel.push(parseStockRow(this));
            });
            if (sel.length === 0) {
                // friendly message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'No Selection',
                        text: 'Select rows by clicking them, then click Export Selected.'
                    });
                } else {
                    alert('Select rows by clicking them, then click Export Selected.');
                }
                return;
            }
            var ts = new Date().toISOString().replace(/[:\-T]/g, '').slice(0, 14);
            buildAndDownload(sel, 'warehouse_stock_selected_' + ts + '.xlsx', isStockAdmin);
        });
    });
</script>
@endsection
