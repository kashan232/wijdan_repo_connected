@extends('admin_panel.layout.app')

@section('content')
<style>
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

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #stockTable {
        min-width: 1200px;
        /* 🔥 force scroll on mobile */
    }

    #stockTable th,
    #stockTable td {
        white-space: nowrap;
        font-size: 13px;
    }

    /* Item Name should wrap */
    #stockTable td:nth-child(4),
    #stockTable th:nth-child(4) {
        white-space: normal;
        min-width: 200px;
        line-height: 1.4;
    }

    /* Numbers aligned */
    #stockTable td:nth-child(n+6) {
        text-align: right;
    }

    #stockTable thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 2;
    }

    .custom-pagination {
        margin-top: 15px;
        padding: 10px 0;
    }

    /* Page info text */
    .custom-pagination span {
        font-size: 14px;
        font-weight: 600;
        margin: 6px 0;
    }

    /* Buttons spacing */
    .custom-pagination button {
        padding: 6px 14px;
        min-width: 90px;
    }

    /* Mobile fix */
    @media (max-width: 576px) {
        .custom-pagination {
            gap: 8px;
        }
    }
</style>
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>📦 Item Stock Report</h4>
                    <h6>Track initial, purchased, sold, and balance per product</h6>
                </div>
            </div>

            <!-- 🔹 Filter Form -->
            <div class="card mb-3">
                <div class="card-body">
                    <form id="stockFilterForm" class="row g-2 align-items-end" onsubmit="return false;">

                        <div class="col-12 col-md-6 col-lg-6">
                            <label class="form-label fw-bold">Product</label>
                            <select name="product_id" id="product_id" class="form-select select2">
                                <option value="all">-- All Products --</option>
                                @foreach ($allProducts as $prod)
                                <option value="{{ $prod->id }}">
                                    {{ $prod->item_code }} - {{ $prod->item_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label fw-bold">Start Date</label>
                            <input type="date" id="start_date" class="form-control">
                        </div>

                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label fw-bold">End Date</label>
                            <input type="date" id="end_date" class="form-control">
                        </div>

                        <div class="col-12 col-md-12 col-lg-2">
                            <button id="btnSearch" class="btn btn-danger w-100 mt-3 mt-lg-0">
                                Search
                            </button>
                        </div>

                        <div class="col-md-12 text-end mt-2">
                            <button type="button" id="btnExportCsv" class="btn btn-outline-secondary">
                                📤 Export CSV
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 🔹 Report Table -->
            <div class="card">
                <div class="card-body">
                    <div id="loader" style="display:none;text-align:center;margin-bottom:10px;">
                        <div class="spinner-border" role="status"></div>
                    </div>

                    <div class="table-responsive">
                        <table id="stockTable" class="table table-striped table-bordered" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>UOM</th>
                                    <th>Opening Stock</th>
                                    <th>Inward Qty</th>
                                    <th>Purchased Qty</th>
                                    <th>Purchase Return</th>
                                    <th>Sold Qty</th>
                                    <th>Sale Return</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>

                            <tbody id="reportBody">
                                <!-- AJAX will fill this -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="9" class="text-end">Grand Stock Value:</th>
                                    <th id="grandStockValue" colspan="2">0.00</th>
                                </tr>
                            </tfoot>
                        </table>

                        {{ $products->links() }}

                        <div class="custom-pagination d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                            <button class="btn btn-sm btn-secondary w-100 w-md-auto"
                                onclick="fetchReport(currentPage-1)">
                                ⬅ Previous
                            </button>

                            <span id="pageInfo">Page 1 of 1</span>

                            <button class="btn btn-sm btn-secondary w-100 w-md-auto"
                                onclick="fetchReport(currentPage+1)">
                                Next ➡
                            </button>
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
    $(document).ready(function() {
        // 🔹 Enable Select2 for product search
        $('#product_id').select2({
            placeholder: "Search Product...",
            allowClear: true,
            width: '100%'
        });

        // 🔹 DataTable init
        var stockTable = $('#stockTable').DataTable({
            paging: false, // ❌ client paging off
            searching: false,
            ordering: true,
            info: false,
            columns: [{
                    data: 'index'
                },
                {
                    data: 'date'
                },
                {
                    data: 'item_code'
                },
                {
                    data: 'item_name'
                },
                {
                    data: 'unit_id'
                },
                {
                    data: 'initial_stock'
                },
                {
                    data: 'inward_qty'
                },
                {
                    data: 'purchased'
                },
                {
                    data: 'purchase_return'
                },
                {
                    data: 'sold'
                },
                {
                    data: 'sale_return'
                },
                {
                    data: 'balance'
                }
            ]
        });

        // 🔹 Render data
        function renderRows(rows, grandTotal) {
            stockTable.clear().draw();
            rows.forEach(function(r, idx) {

                // Format date → dd-mm-yyyy
                let formattedDate = '';
                if (r.date) {
                    let d = new Date(r.date);
                    formattedDate = ("0" + d.getDate()).slice(-2) + '-' +
                        ("0" + (d.getMonth() + 1)).slice(-2) + '-' +
                        d.getFullYear();
                }

                stockTable.row.add({
                    index: idx + 1,
                    date: formattedDate, // 👈 use formatted date here
                    item_code: r.item_code,
                    item_name: r.item_name,
                    unit_id: r.unit_id,
                    initial_stock: parseFloat(r.initial_stock).toFixed(2),
                    inward_qty: parseFloat(r.inward_qty).toFixed(2),
                    purchased: parseFloat(r.purchased).toFixed(2),
                    purchase_return: parseFloat(r.purchase_return).toFixed(2),
                    sold: parseFloat(r.sold).toFixed(2),
                    sale_return: parseFloat(r.sale_return).toFixed(2),
                    balance: parseFloat(r.balance).toFixed(2)
                }).draw(false);
            });
            $('#grandStockValue').text(parseFloat(grandTotal).toFixed(2));
        }

        let currentPage = 1;

        // 🔹 Fetch report via AJAX
        function fetchReport(page = 1, callback = null) {
            if (page < 1) return;
            currentPage = page;

            let productId = $('#product_id').val();
            let startDate = $('#start_date').val();
            let endDate = $('#end_date').val();

            $('#loader').show();

            $.ajax({
                url: "{{ route('report.item_stock.fetch') }}?page=" + page,
                type: "POST",
                dataType: "json",
                data: {
                    _token: "{{ csrf_token() }}",
                    product_id: productId,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    $('#loader').hide();

                    if (response.data && response.data.length > 0) {
                        renderRows(response.data, response.grand_total);
                        if (typeof callback === 'function') callback(response.data);
                    } else {
                        stockTable.clear().draw();
                        $('#reportBody').html('<tr><td colspan="12" class="text-center text-danger">No records found</td></tr>');
                        $('#grandStockValue').text('0.00');
                    }

                    renderPagination(response.pagination);
                },
                error: function(xhr) {
                    $('#loader').hide();
                    alert("Error fetching report, please check console.");
                    console.log(xhr.responseText);
                }
            });
        }

        // 🔹 Bind Search button
        $('#btnSearch').on('click', function() {
            fetchReport();
        });

        function renderPagination(p) {
            $('#pageInfo').text(`Page ${p.current_page} of ${p.last_page}`);
        }

        // 🔹 Export CSV button
        $('#btnExportCsv').on('click', function() {
            let startDate = $('#start_date').val();
            let endDate = $('#end_date').val();
            let productId = $('#product_id').val();

            $('#loader').show();

            $.ajax({
                url: "{{ route('report.item_stock.fetch') }}",
                type: "POST",
                dataType: "json",
                data: {
                    _token: "{{ csrf_token() }}",
                    product_id: productId,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    $('#loader').hide();

                    if (!response.data || !response.data.length) {
                        alert('No data to export');
                        return;
                    }

                    var csv = 'Date,Item Code,Item Name,Initial Stock,Purchased Qty,Purchase Return,Sold Qty,Sale Return,Balance\n';
                    csv += `"${r.date}","${r.item_code}","${r.item_name}",${r.initial_stock},${r.purchased},${r.purchase_return},${r.sold},${r.sale_return},${r.balance}\n`;

                    response.data.forEach(function(r) {
                        csv += `"${r.date}","${r.item_code}","${r.item_name}",${r.initial_stock},${r.purchased},${r.sold},${r.balance}\n`;
                    });

                    var blob = new Blob([csv], {
                        type: 'text/csv;charset=utf-8;'
                    });
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'item_stock_report.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                },
                error: function() {
                    $('#loader').hide();
                    alert('Export failed');
                }
            });
        });

        // 🔹 Auto-load on page open
        fetchReport();
    });
</script>
@endsection
