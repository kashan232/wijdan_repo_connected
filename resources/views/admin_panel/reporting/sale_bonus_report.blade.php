@extends('admin_panel.layout.app')
<style>
    .bg-bonus {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }

    .summary-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .table thead th {
        background-color: #f1f3f5;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
    }

    .info-badge {
        font-size: 0.8rem;
        padding: 4px 10px;
        border-radius: 20px;
        background: #e9ecef;
        color: #495057;
        display: inline-block;
        margin-right: 5px;
    }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-4">
                <div class="page-title col-lg-8">
                    <h4 class="fw-bold">Sale Bonus Report</h4>
                    <p class="text-muted">Bonus Logic: Rs. 8 per piece (Meter/4.5, Yard/8, Piece/1)</p>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <form id="BonusFilterForm" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">From Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">To Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Customer Category</label>
                            <div class="d-flex gap-3 pt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="customer_type[]" value="Wholesaler" id="type_wh">
                                    <label class="form-check-label" for="type_wh">Wholesaler</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="customer_type[]" value="Retailer" id="type_ret" checked>
                                    <label class="form-check-label" for="type_ret">Retailer</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="customer_type[]" value="Walking Customer" id="type_walk" checked>
                                    <label class="form-check-label" for="type_walk">Walking</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-primary w-100 py-2 shadow-sm">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Result Summary Section -->
            <div id="summary-section" class="mb-5"></div>

            <!-- Detailed Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div id="loader" style="display:none;text-align:center;padding:40px;">
                        <div class="spinner-border text-success" role="status"></div>
                        <p class="mt-2 text-muted">Calculating bonuses...</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="bonusReportTable">
                            <thead>
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Date</th>
                                    <th>Invoice No</th>
                                    <th>Customer</th>
                                    <th>Products</th>
                                    <th class="text-center">Sold Pcs</th>
                                    <th class="text-center">Return Pcs</th>
                                    <th class="text-center">Net Pcs</th>
                                    <th class="text-end pe-4 text-success">Bonus (Rs. 8)</th>
                                </tr>
                            </thead>
                            <tbody id="bonusBody">
                                <tr><td colspan="9" class="text-center py-5 text-muted">Select date range and click Generate Report.</td></tr>
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
        $("#bonusBody").html('');
        $("#summary-section").html('');

        $.ajax({
            url: "{{ route('report.sale.bonus.fetch') }}",
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
                
                let totalSoldPieces = 0;
                let totalReturnPieces = 0;
                let totalNetPieces = 0;
                let totalBonus = 0;

                (res || []).forEach((s, i) => {
                    const sold = parseFloat(s.pieces_sold || 0);
                    const ret = parseFloat(s.return_pieces || 0);
                    const net = parseFloat(s.net_pieces || 0);
                    const bonus = parseFloat(s.bonus_amount || 0);

                    totalSoldPieces += sold;
                    totalReturnPieces += ret;
                    totalNetPieces += net;
                    totalBonus += bonus;

                    tableHtml += `<tr>
                        <td class="ps-4">${i+1}</td>
                        <td>${s.created_at.split(' ')[0]}</td>
                        <td class="fw-bold">${s.invoice_no ?? '-'}</td>
                        <td>${s.customer_name ?? '-'}</td>
                        <td style="font-size: 0.85rem;" class="text-muted">${s.product_names}</td>
                        <td class="text-center">${sold.toFixed(2)}</td>
                        <td class="text-center text-danger">${ret > 0 ? ret.toFixed(2) : '-'}</td>
                        <td class="text-center fw-bold">${net.toFixed(2)}</td>
                        <td class="text-end pe-4 fw-bold text-success">Rs. ${bonus.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>`;
                });

                if (res.length === 0) {
                    tableHtml = '<tr><td colspan="9" class="text-center py-5">No sale records found for this period.</td></tr>';
                } else {
                    // Summary Row
                    tableHtml += `<tr class="bg-light fw-bold">
                        <td colspan="5" class="text-end ps-4">TOTALS:</td>
                        <td class="text-center">${totalSoldPieces.toFixed(2)}</td>
                        <td class="text-center text-danger">${totalReturnPieces.toFixed(2)}</td>
                        <td class="text-center text-primary">${totalNetPieces.toFixed(2)}</td>
                        <td class="text-end pe-4 text-success">Rs. ${totalBonus.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>`;

                    // Hero Summary Card
                    let summaryHtml = `
                        <div class="row g-4 justify-content-center">
                            <div class="col-md-6 col-lg-5">
                                <div class="card summary-card bg-bonus">
                                    <div class="card-body text-center p-4">
                                        <p class="text-uppercase mb-1" style="opacity: 0.9; font-size: 0.8rem; letter-spacing: 2px;">Total Sale Bonus</p>
                                        <h1 class="display-4 fw-bold mb-3">Rs. ${totalBonus.toLocaleString(undefined, {minimumFractionDigits: 2})}</h1>
                                        
                                        <div class="row g-0 pt-3 border-top border-white border-opacity-25">
                                            <div class="col-6 border-end border-white border-opacity-25">
                                                <small class="d-block opacity-75">Net Pieces</small>
                                                <span class="fw-bold fs-5">${totalNetPieces.toFixed(2)}</span>
                                            </div>
                                            <div class="col-6">
                                                <small class="d-block opacity-75">Rate / Piece</small>
                                                <span class="fw-bold fs-5">Rs. 8.00</span>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <span class="info-badge">Meter / 4.5</span>
                                            <span class="info-badge">Yard / 8</span>
                                            <span class="info-badge">Piece / 1</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $("#summary-section").html(summaryHtml);
                }

                $('#bonusBody').html(tableHtml);
            },
            error: function() {
                $("#loader").hide();
                alert('Something went wrong. Please check your connection.');
            }
        });
    });
</script>
@endsection
