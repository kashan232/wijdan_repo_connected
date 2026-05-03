@extends('admin_panel.layout.app')
@section('content')
<style>
    /* Mobile optimization */
    @media (max-width: 768px) {

        .card-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 8px;
        }

        .card-header .btn {
            width: 100%;
        }

        /* Hide less important columns on mobile */
        #stockTable th:nth-child(6),
        #stockTable td:nth-child(6),
        /* Brand */

        #stockTable th:nth-child(5),
        #stockTable td:nth-child(5),
        /* Unit */

        #stockTable th:nth-child(7),
        #stockTable td:nth-child(7),
        /* Price */

        #stockTable th:nth-child(11),
        #stockTable td:nth-child(11)

        /* Remarks */
            {
            display: none;
        }

        /* Smaller text for table */
        #stockTable {
            font-size: 12px;
        }
    }
</style>

<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            ➕ Stock Status
        </h5>
        <div class="d-flex gap-2">
            <a href="{{ route('warehouse_stocks.create') }}" class="btn btn-primary btn-sm">Add Stock</a>
            <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm">Back</a>

            <!-- EXPORT buttons (add these) -->
            <a id="exportStockAllBtn" class="btn btn-outline-secondary btn-sm" href="javascript:void(0)">⬇ Export All</a>
            <button id="exportStockSelectedBtn" class="btn btn-outline-primary btn-sm" type="button">⬇ Export Selected</button>
        </div>

    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('warehouse_stocks.index') }}" class="row g-2 mb-3">
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
                <input type="text" name="search" id="warehouseStockSearch" class="form-control form-control-sm" placeholder="Name or Code..." value="{{ request('search') }}">
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
        </form>
        @if(request('start_date') && request('end_date'))
        <div class="alert alert-info py-2">
            Showing results from <strong>{{ request('start_date') }}</strong> to <strong>{{ request('end_date') }}</strong>
        </div>
        @endif
        <div class="table-responsive stock-table-wrapper">
            <table class="table table-bordered table-striped table-sm" id="stockTable">
                <thead>
                    <tr>
                        <th>NO#</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Brand</th>
                        <th>Price</th>
                        <th>Shop Stock</th> <!-- new -->
                        <th>Warehouse Stock</th> <!-- new -->
                        <th>Total Stock</th> <!-- new -->
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stocks as $stock)
                    <tr>
                        <td>{{ ($stocks->currentPage() - 1) * $stocks->perPage() + $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($stock->created_at)->format('d M Y') }}</td>
                        <td>{{ $stock->warehouse_name ?? '— Shop —' }}</td>
                        <td>
                            <strong>{{ $stock->item_name }}</strong><br>
                            <small class="text-muted">{{ $stock->item_code }}</small>
                        </td>
                        <td>{{ $stock->unit_id }}</td>
                        <td>{{ $stock->brand_name ?? 'N/A' }}</td>
                        <td>{{ number_format($stock->price, 2) }}</td>

                        <td class="text-center">{{ number_format($stock->shop_stock, 2) }}</td>
                        <td class="text-center">{{ number_format($stock->warehouse_stock, 2) }}</td>
                        <td class="text-center fw-bold">{{ number_format($stock->shop_stock + $stock->warehouse_stock, 2) }}</td>

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
            paging: false, // Disabling DT paging because we use Laravel Pagination
            searching: false, // Disabling DT searching because we use server-side search
            ordering: true,
            info: false,
            responsive: true,
            scrollX: false
        });

        // 🔍 AJAX SEARCH (Similar to Product List)
        let searchTimer = null;

        function triggerAjaxFetch() {
            let query = $('#warehouseStockSearch').val();
            let type = $('select[name="stock_type"]').val();
            let start = $('input[name="start_date"]').val();
            let end = $('input[name="end_date"]').val();
            fetchStocks(query, type, start, end);
        }

        $('#warehouseStockSearch').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(triggerAjaxFetch, 400); // debounce
        });

        $('select[name="stock_type"], input[name="start_date"], input[name="end_date"]').on('change', function() {
            triggerAjaxFetch();
        });

        // Prevent form submission to keep it AJAX
        $('form').on('submit', function(e) {
            e.preventDefault();
            triggerAjaxFetch();
        });

        // 📄 PAGINATION
        $(document).on('click', '#paginationLinks a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            fetchStocks($('#warehouseStockSearch').val(), $('select[name="stock_type"]').val(), $('input[name="start_date"]').val(), $('input[name="end_date"]').val(), url);
        });

        function fetchStocks(search = '', type = 'all', start = '', end = '', url = null) {
            if (!url) {
                url = "{{ route('warehouse_stocks.index') }}";
            }

            $.ajax({
                url: url,
                data: {
                    search: search,
                    stock_type: type,
                    start_date: start,
                    end_date: end
                },
                success: function(res) {
                    // Replace table body and pagination
                    $('#stockTable tbody').html($(res).find('#stockTable tbody').html());
                    $('#paginationLinks').html($(res).find('#paginationLinks').html());
                }
            });
        }
    });
</script>

<script>
    $(function() {
        // Make rows clickable to toggle selection for "Export Selected"
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
            // columns: # | Date | Warehouse | Product | Shop Stock | Warehouse Stock | Total Stock | Remarks
            var $tds = $(tr).find('td');
            var date = $tds.eq(1).text().trim();
            var warehouse = $tds.eq(2).text().trim();
            var product = $tds.eq(3).text().trim();
            var shopStock = toNumber($tds.eq(4).text());
            var warehouseStock = toNumber($tds.eq(5).text());
            var totalStock = toNumber($tds.eq(6).text());
            var remarks = $tds.eq(7).text().trim();
            return [date, warehouse, product, shopStock, warehouseStock, totalStock, remarks];
        }

        // build workbook and download
        function buildAndDownload(rowsArray, filename) {
            var header = ['Date', 'Warehouse', 'Product', 'Shop Stock', 'Warehouse Stock', 'Total Stock', 'Remarks'];
            var aoa = [header].concat(rowsArray);
            var ws = XLSX.utils.aoa_to_sheet(aoa);
            // set column widths
            ws['!cols'] = [{
                wpx: 80
            }, {
                wpx: 140
            }, {
                wpx: 200
            }, {
                wpx: 80
            }, {
                wpx: 100
            }, {
                wpx: 100
            }, {
                wpx: 180
            }];
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'WarehouseStock');
            XLSX.writeFile(wb, filename);
        }

        // Export ALL
        $('#exportStockAllBtn').on('click', function() {
            var rows = [];
            $('#stockTable tbody tr').each(function() {
                // skip any hidden rows
                if ($(this).is(':hidden')) return;
                rows.push(parseStockRow(this));
            });
            if (rows.length === 0) {
                alert('No rows to export.');
                return;
            }
            var ts = new Date().toISOString().replace(/[:\-T]/g, '').slice(0, 14);
            buildAndDownload(rows, 'warehouse_stock_all_' + ts + '.xlsx');
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
            buildAndDownload(sel, 'warehouse_stock_selected_' + ts + '.xlsx');
        });
    });
</script>
@endsection
