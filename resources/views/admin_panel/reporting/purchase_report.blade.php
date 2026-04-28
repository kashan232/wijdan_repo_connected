@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>Purchase Report</h4>
                    <h6>View purchases by date range with details</h6>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form id="purchaseFilterForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-primary w-100">Search</button>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" id="btnExportCsv" class="btn btn-danger">Export CSV</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div id="loader" style="display:none;text-align:center;margin-bottom:10px;">
                        <div class="spinner-border" role="status"></div>
                    </div>

                    <div class="table-responsive">
                        <table id="purchaseTable" class="table table-striped table-bordered" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Source</th>
                                    <th>Purchase Date</th>
                                    <th>Invoice No</th>
                                    <th>Vendor</th>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Qty</th>
                                    <th>Unit</th>
                                    <th>Price</th>
                                    <th>Item Discount</th>
                                    <th>Line Total</th>
                                    <th>Subtotal</th>
                                    <th>Discount</th>
                                    <th>Extra Cost</th>
                                    <th>Net Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Due Amount</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody"></tbody>
                            <tfoot>
                                <tr class="fw-bold bg-light">
                                    <td colspan="12" class="text-end">Grand Total:</td>
                                    <td id="grandSubtotal">0.00</td>
                                    <td id="grandDiscount">0.00</td>
                                    <td id="grandExtraCost">0.00</td>
                                    <td id="grandNet">0.00</td>
                                    <td id="grandPaid">0.00</td>
                                    <td id="grandDue">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
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
        var purchaseTable = $('#purchaseTable').DataTable({
            paging: true,
            searching: true,
            info: true,
            ordering: true,
            columns: [{
                    data: 'index'
                },
                {
                    data: 'source_type'
                },
                {
                    data: 'purchase_date'
                },
                {
                    data: 'invoice_no'
                },
                {
                    data: 'vendor_name'
                }, // updated
                {
                    data: 'item_code'
                },
                {
                    data: 'item_name'
                },
                {
                    data: 'qty'
                },
                {
                    data: 'unit'
                },
                {
                    data: 'price'
                },
                {
                    data: 'item_discount'
                },
                {
                    data: 'line_total'
                },
                {
                    data: 'subtotal'
                },
                {
                    data: 'discount'
                },
                {
                    data: 'extra_cost'
                },
                {
                    data: 'net_amount'
                },
                {
                    data: 'paid_amount'
                },
                {
                    data: 'due_amount'
                }
            ]

        });

        function renderRows(rows) {
            if ($.fn.DataTable.isDataTable('#purchaseTable')) {
                purchaseTable.clear();
            }

            let grandSubtotal = 0;
            let grandDiscount = 0;
            let grandExtraCost = 0;
            let grandNet = 0;
            let grandPaid = 0;
            let grandDue = 0;

            function formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const day = d.getDate();
                const month = d.getMonth() + 1;
                const year = d.getFullYear();
                return `${day}-${month}-${year}`;
            }

            let dataTableRows = [];

            rows.forEach(function(r, idx) {
                
                let sourceBadge = `<span class="badge ${r.source_type === 'inward' ? 'bg-danger' : (r.source_type === 'purchase' ? 'bg-success' : 'bg-secondary')}">${r.source_type}</span>`;
                
                let subtotalCell = r.is_duplicate ? '<span class="text-muted">-</span>' : parseFloat(r.subtotal).toFixed(2);
                let discountCell = r.is_duplicate ? '<span class="text-muted">-</span>' : parseFloat(r.discount).toFixed(2);
                let extraCostCell = r.is_duplicate ? '<span class="text-muted">-</span>' : parseFloat(r.extra_cost).toFixed(2);
                let netAmountCell = r.is_duplicate ? '<span class="text-muted">-</span>' : '<span class="fw-bold">' + parseFloat(r.net_amount).toFixed(2) + '</span>';
                let paidAmountCell = r.is_duplicate ? '<span class="text-muted">-</span>' : parseFloat(r.paid_amount).toFixed(2);
                let dueAmountCell = r.is_duplicate ? '<span class="text-muted">-</span>' : parseFloat(r.due_amount).toFixed(2);

                dataTableRows.push({
                    index: idx + 1,
                    source_type: sourceBadge,
                    purchase_date: formatDate(r.purchase_date),
                    invoice_no: r.invoice_no,
                    vendor_name: r.vendor_name,
                    item_code: r.item_code,
                    item_name: r.item_name,
                    qty: r.qty,
                    unit: r.unit,
                    price: parseFloat(r.price).toFixed(2),
                    item_discount: parseFloat(r.item_discount).toFixed(2),
                    line_total: parseFloat(r.line_total).toFixed(2),
                    subtotal: subtotalCell,
                    discount: discountCell,
                    extra_cost: extraCostCell,
                    net_amount: netAmountCell,
                    paid_amount: paidAmountCell,
                    due_amount: dueAmountCell
                });

                // Grand totals
                grandSubtotal += parseFloat(r.subtotal);
                grandDiscount += parseFloat(r.discount);
                grandExtraCost += parseFloat(r.extra_cost);
                grandNet += parseFloat(r.net_amount);
                grandPaid += parseFloat(r.paid_amount);
                grandDue += parseFloat(r.due_amount);
            });

            purchaseTable.rows.add(dataTableRows).draw();

            $('#grandSubtotal').text(grandSubtotal.toFixed(2));
            $('#grandDiscount').text(grandDiscount.toFixed(2));
            $('#grandExtraCost').text(grandExtraCost.toFixed(2));
            $('#grandNet').text(grandNet.toFixed(2));
            $('#grandPaid').text(grandPaid.toFixed(2));
            $('#grandDue').text(grandDue.toFixed(2));
        }

        $('#btnSearch').on('click', function() {
            fetchReport();
        });

        function fetchReport() {
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            $('#loader').show();

            $.ajax({
                url: "{{ route('report.purchase.fetch') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    start_date: start_date,
                    end_date: end_date
                },
                success: function(response) {
                    $('#loader').hide();
                    renderRows(response.data);
                },
                error: function() {
                    $('#loader').hide();
                    alert('Error fetching purchase report');
                }
            });
        }

        $('#btnExportCsv').on('click', function() {
            var rowsData = purchaseTable.rows({ search: 'applied' }).data().toArray();
            
            if (rowsData.length === 0) {
                alert('No data to export');
                return;
            }

            // Simple function to strip HTML tags from a string
            function stripHtml(html) {
                if (typeof html !== 'string') return html;
                var doc = new DOMParser().parseFromString(html, 'text/html');
                return doc.body.textContent || "";
            }

            var csv = 'Source,Purchase Date,Invoice No,Vendor,Item Code,Item Name,Qty,Unit,Price,Item Discount,Line Total,Subtotal,Discount,Extra Cost,Net Amount,Paid Amount,Due Amount\n';

            rowsData.forEach(function(r) {
                csv += `"${stripHtml(r.source_type)}","${r.purchase_date}","${r.invoice_no}","${r.vendor_name}","${r.item_code}","${r.item_name}",${r.qty},"${r.unit}",${r.price},${r.item_discount},${r.line_total},"${stripHtml(r.subtotal)}","${stripHtml(r.discount)}","${stripHtml(r.extra_cost)}","${stripHtml(r.net_amount)}","${stripHtml(r.paid_amount)}","${stripHtml(r.due_amount)}"\n`;
            });

            var blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'purchase_report.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    });
</script>

@endsection
