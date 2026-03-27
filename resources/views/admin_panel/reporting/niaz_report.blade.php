@extends('admin_panel.layout.app')
<style>
    .bg-niaz {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
    }

    .summary-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .table thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>Niaz Calculation Report</h4>
                    <h6>Formula: (Total Net Sales) x 4%</h6>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <form id="NiazFilterForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">From Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control border-secondary-subtle" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">To Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control border-secondary-subtle" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Customer Category</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="customer_type[]" value="Wholesaler" id="type_wholesaler">
                                    <label class="form-check-label" for="type_wholesaler">Wholesaler</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="customer_type[]" value="Retailer" id="type_retailer" checked>
                                    <label class="form-check-label" for="type_retailer">Retailer</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="customer_type[]" value="Walking Customer" id="type_walking" checked>
                                    <label class="form-check-label" for="type_walking">Walking</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-dark w-100 shadow-sm">Calculate Niaz</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Card (Final Result) -->
            <div id="summary-section" class="mb-4"></div>

            <!-- Data Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div id="loader" style="display:none;text-align:center;margin-bottom:10px;">
                        <div class="spinner-grow text-primary" role="status"></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="niazReport">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Invoice #</th>
                                    <th>Customer Name</th>
                                    <th class="text-end">Sale Amount</th>
                                    <th class="text-end">Return Amount</th>
                                    <th class="text-end">Net Final (Row)</th>
                                </tr>
                            </thead>
                            <tbody id="niazBody">
                                <tr><td colspan="7" class="text-center text-muted">Please select filters and click Search.</td></tr>
                            </tbody>
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
    $(document).on('click', '#btnSearch', function() {
        let start = $('#start_date').val();
        let end = $('#end_date').val();

        $("#loader").show();
        $("#niazBody").html('');
        $("#summary-section").html('');

        $.ajax({
            url: "{{ route('report.niaz.fetch') }}",
            type: "GET",
            data: {
                start_date: start,
                end_date: end,
                customer_type: $('input[name="customer_type[]"]:checked').map(function(){return $(this).val();}).get(),
                _t: new Date().getTime()
            },
            success: function(res) {
                $("#loader").hide();
                let tableHtml = "";
                
                function num(v) {
                    if (v === null || v === undefined) return 0;
                    v = String(v).replace(/[^0-9.\-]/g, '');
                    const f = parseFloat(v);
                    return isNaN(f) ? 0 : f;
                }

                let totalSales = 0;
                let totalReturns = 0;

                (res || []).forEach((s, i) => {
                    const rowSale = num(s.original_total_net);
                    
                    let rowReturn = 0;
                    if (s.returns && Array.isArray(s.returns)) {
                        s.returns.forEach(r => {
                            rowReturn += num(r.original_amount);
                        });
                    }

                    const rowNet = rowSale - rowReturn;

                    totalSales += rowSale;
                    totalReturns += rowReturn;

                    tableHtml += `<tr>
                        <td>${i+1}</td>
                        <td>${s.created_at.split(' ')[0]}</td>
                        <td class="fw-bold">${s.invoice_no ?? '-'}</td>
                        <td>${s.customer_name ?? '-'}</td>
                        <td class="text-end">${rowSale.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-danger">${rowReturn > 0 ? rowReturn.toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00'}</td>
                        <td class="text-end fw-bold">${rowNet.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>`;
                });

                if (res.length === 0) {
                    tableHtml = '<tr><td colspan="7" class="text-center">No sales records found.</td></tr>';
                } else {
                    const finalTotalNet = totalSales - totalReturns;
                    const grandNiaz = finalTotalNet * 0.04;

                    // Grand Total Row in Table
                    tableHtml += `<tr class="fw-bold bg-light">
                        <td colspan="4" class="text-end">GRAND TOTALS:</td>
                        <td class="text-end">${totalSales.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-danger">${totalReturns.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-primary">${finalTotalNet.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>`;

                    // Single Large Summary Card for 4%
                    let summaryHtml = `
                        <div class="row justify-content-center">
                            <div class="col-md-6 col-lg-5">
                                <div class="card summary-card bg-niaz shadow-lg text-white">
                                    <div class="card-body text-center p-4">
                                        <p class="text-uppercase mb-1" style="opacity: 0.9; font-size: 0.8rem; letter-spacing: 2px;">Total Niaz Amount (4%)</p>
                                        <h1 class="display-4 fw-bold mb-0">Rs. ${grandNiaz.toLocaleString(undefined, {minimumFractionDigits: 2})}</h1>
                                        <hr class="bg-white border-white" style="opacity: 0.4;">
                                        <div class="d-flex justify-content-between px-3" style="font-size: 0.95rem; opacity: 0.95;">
                                            <span>Net Sale Total:</span>
                                            <span class="fw-bold">Rs. ${finalTotalNet.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $("#summary-section").html(summaryHtml);
                }

                $('#niazBody').html(tableHtml);
            },
            error: function() {
                $("#loader").hide();
                alert('Connection error. Please refresh and try again.');
            }
        });
    });
</script>
@endsection
