@extends('admin_panel.layout.app')
<style>
    .return-cell {
        max-width: 180px;
        max-height: 80px;
        overflow-y: auto;
        overflow-x: hidden;
        white-space: normal;
        font-size: 12px;
        line-height: 1.4;
        background: #fafafa;
        border-radius: 4px;
        padding: 4px;
        scrollbar-width: thin;
    }

    .return-cell::-webkit-scrollbar {
        width: 5px;
    }

    .return-cell::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 3px;
    }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>Sale Report (Fixed)</h4>
                    <h6>View Sales by date range with details</h6>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form id="SaleFilterForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Customer Type</label>
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
                            <button type="button" id="btnSearch" class="btn btn-primary w-100">Search</button>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="button" id="btnExportCsv" class="btn btn-danger w-100">Export CSV</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Jump to Bottom Button -->
            <div class="d-flex justify-content-end mb-2">
                <button class="btn btn-sm btn-secondary" onclick="document.getElementById('grandTotalRow').scrollIntoView({behavior: 'smooth'});">
                    ⬇ Go to Grand Total
                </button>
            </div>

            <div class="card">
                <div class="card-body">
                    <div id="loader" style="display:none;text-align:center;margin-bottom:10px;">
                        <div class="spinner-border" role="status"></div>
                    </div>

                    <div class="table-responsive">
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered" id="saleReport">
                                <thead class="bg-gray">
                                    <tr>
                                        <th>#</th>
                                        <th style="width:160px!important;">Date | Time</th>
                                        <th>Invoice No</th>
                                        <th>Customer</th>
                                        <th>Reference</th>
                                        <th>Products</th>
                                        <th>Unit</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                        <th>Net Amount</th>
                                        <th>Payment</th>
                                        <th>Returns</th>
                                    </tr>
                                </thead>
                                <tbody id="saleBody"></tbody>
                            </table>
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
    $(document).on('click', '#btnSearch', function() {
        let start = $('#start_date').val();
        let end = $('#end_date').val();

        $("#loader").show();
        $.ajax({
            url: "{{ route('report.sale.fetch') }}",
            type: "GET",
            data: {
                start_date: start,
                end_date: end,
                customer_type: $('input[name="customer_type[]"]:checked').map(function(){return $(this).val();}).get(),
                _t: new Date().getTime() // Cache buster
            },
            success: function(res) {
                $("#loader").hide();
                let html = "";

                // helpers
                function num(v) {
                    if (v === null || v === undefined) return 0;
                    if (typeof v === 'number') return v;
                    // remove any non numeric except dot and minus
                    v = String(v).replace(/[^0-9.\-]/g, '');
                    const f = parseFloat(v);
                    return isNaN(f) ? 0 : f;
                }

                function sumArray(arr) {
                    return arr.reduce((a, b) => a + num(b), 0);
                }

                function formatDate(dateString) {
                    if (!dateString) return '-';

                    const d = new Date(dateString);
                    if (isNaN(d)) return dateString;

                    const day = String(d.getDate()).padStart(2, '0');
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const year = d.getFullYear();

                    let hours = d.getHours();
                    const minutes = String(d.getMinutes()).padStart(2, '0');
                    const ampm = hours >= 12 ? 'PM' : 'AM';

                    hours = hours % 12;
                    hours = hours ? hours : 12; // 0 ko 12 bana do
                    hours = String(hours).padStart(2, '0');

                    return `${day}-${month}-${year} ${hours}:${minutes} ${ampm}`;
                }
                let grandQty = 0,
                    grandTotal = 0,
                    grandNet = 0,
                    grandCard = 0,
                    grandReturnQty = 0,
                    grandReturnAmount = 0;
                
                // Unit Totals
                let grandPiece = 0;
                let grandMeter = 0;
                let grandYard = 0;

                // Subtotal Grouping Variables
                let currentGroupKey = null;
                let subPiece = 0, subMeter = 0, subYard = 0, subQty = 0;
                let subTotal = 0, subNet = 0, subCard = 0, subReturnQty = 0, subReturnAmt = 0;

                function renderSubtotalRow() {
                    if (!currentGroupKey) return "";
                    let rowHtml = `<tr class="fw-bold" style="background-color: #e9ecef;">
                        <td colspan="6" class="text-end text-primary">Subtotal (${currentGroupKey}):</td>
                        <td class="text-primary">
                            Pcs: ${subPiece.toFixed(2)}<br>
                            Mtr: ${subMeter.toFixed(2)}<br>
                            Yrd: ${subYard.toFixed(2)}
                        </td>
                        <td class="text-primary">${subQty.toFixed(2)}</td>
                        <td>-</td>
                        <td class="text-primary">${subTotal.toFixed(2)}</td>
                        <td class="text-primary">${subNet.toFixed(2)}</td>
                        <td class="text-primary">${subCard.toFixed(2)}</td>
                        <td class="text-primary">Qty: ${subReturnQty.toFixed(2)}<br>Amt: ${subReturnAmt.toFixed(2)}</td>
                    </tr>`;
                    
                    // Reset Subtotals for Next Group
                    subPiece = 0; subMeter = 0; subYard = 0; subQty = 0; 
                    subTotal = 0; subNet = 0; subCard = 0; subReturnQty = 0; subReturnAmt = 0;
                    
                    return rowHtml;
                }

                (res || []).forEach((s, i) => {
                    const createdAt = s.created_at || s.date || s.sale_date || s.created || '';
                    
                    // Determine AM/PM Group Key
                    const d = new Date(createdAt);
                    let groupKey = currentGroupKey;
                    if (!isNaN(d)) {
                        const mDay = String(d.getDate()).padStart(2, '0');
                        const mMonth = String(d.getMonth() + 1).padStart(2, '0');
                        const mYear = d.getFullYear();
                        const ampm = d.getHours() >= 12 ? 'PM' : 'AM';
                        groupKey = `${mDay}-${mMonth}-${mYear} [Shift: ${ampm}]`;
                    }
                    
                    if (currentGroupKey !== null && currentGroupKey !== groupKey) {
                        html += renderSubtotalRow();
                    }
                    currentGroupKey = groupKey;

                    // Products: if product_names field present (comma separated) else '-' 
                    let products = '-';
                    if (s.product_names) {
                        // keep line breaks for CSV/HTML view
                        products = s.product_names.split(',').map(p => p.trim()).join('<br>');
                    }

                    // Parse qty array safely (qty may be comma-separated string)
                    const qtyArrRaw = (s.qty || '').toString().trim();
                    const qtyArr = qtyArrRaw.length ? qtyArrRaw.split(',').map(x => x.trim()) : [];
                    const qtyArrNums = qtyArr.map(num);
                    const rowQty = sumArray(qtyArrNums);
                    grandQty += rowQty;
                    subQty += rowQty;

                    // Units Logic
                    const unitArrRaw = (s.unit || '').toString().trim();
                    const unitArr = unitArrRaw.length ? unitArrRaw.split(',').map(x => x.trim()) : [];
                    
                    // Sum up units based on corresponding Qty
                    if (unitArr.length === qtyArr.length) {
                        unitArr.forEach((u, idx) => {
                            const q = qtyArrNums[idx];
                            const uLower = u.toLowerCase();
                            if (uLower.includes('piece') || uLower.includes('pcs')) { grandPiece += q; subPiece += q; }
                            else if (uLower.includes('meter')) { grandMeter += q; subMeter += q; }
                            else if (uLower.includes('yard')) { grandYard += q; subYard += q; }
                        });
                    }

                    // Price display (per item). Keep each item on new line for UI
                    const priceDisplay = (s.per_price || '').toString().trim().length ?
                        s.per_price.toString().split(',').map(p => p.trim()).join('<br>') : '-';

                    // Total per item (could be comma-separated)
                    const perTotalRaw = (s.per_total || '').toString().trim();
                    const perTotalArr = perTotalRaw.length ? perTotalRaw.split(',').map(x => x.trim()) : [];
                    const perTotalNums = perTotalArr.map(num);
                    const rowTotal = sumArray(perTotalNums);
                    grandTotal += rowTotal;
                    subTotal += rowTotal;

                    // net (row-level) - use s.total_net if provided, else fallback to rowTotal
                    const rowNet = num(s.total_net) || rowTotal;
                    grandNet += rowNet;
                    subNet += rowNet;

                    const rowCard = num(s.card);
                    grandCard += rowCard;
                    subCard += rowCard;

                    // Returns: handle multiple shapes
                    let returnHtml = '';
                    let returnQtyTotal = 0;
                    let returnAmountTotal = 0;

                    if (s.returns) {
                        // If it's already an array of objects
                        if (Array.isArray(s.returns)) {
                            const lines = s.returns.map(r => {
                                const rQty = num(r.qty);
                                const rAmt = num(r.per_total || r.amount || r.total);
                                returnQtyTotal += rQty;
                                returnAmountTotal += rAmt;
                                // Show decimals if float, else integer
                                const qtyDisplay = Number.isInteger(rQty) ? rQty : rQty.toFixed(2).replace(/\.?0+$/, '');
                                return `<small>${(r.product || '').toString().trim()}</small><br>
                                        <span class="text-danger">Qty: ${qtyDisplay}</span> | 
                                        <span class="fw-bold">${rAmt.toFixed(2)}</span>`;
                            });
                            returnHtml = lines.join('<hr class="my-1">');
                        } else {
                            // maybe string: try JSON parse, else comma-separated lines like "prod|qty|amt;..."
                            let parsed = null;
                            try {
                                parsed = JSON.parse(s.returns);
                            } catch (e) {
                                parsed = null;
                            }

                            if (Array.isArray(parsed)) {
                                const lines = parsed.map(r => {
                                    const rQty = num(r.qty);
                                    const rAmt = num(r.per_total || r.amount || r.total);
                                    returnQtyTotal += rQty;
                                    returnAmountTotal += rAmt;
                                    return `${(r.product || '').toString().trim()} (${rQty}) - ${rAmt.toFixed(2)}`;
                                });
                                returnHtml = lines.join('<br>');
                            } else {
                                // fallback: show raw returns string but try to extract numbers
                                const raw = s.returns.toString();
                                // Try to split by newline or semicolon
                                const candidates = raw.split(/\r?\n|;|\|/).map(r => r.trim()).filter(Boolean);
                                const lines = candidates.map(c => {
                                    // try extract product, qty, amount using regex
                                    const m = c.match(/(.+?)\s*\(?(\d+\.?\d*)\)?\s*[-:]\s*([0-9.,]+)/);
                                    if (m) {
                                        const prod = m[1].trim();
                                        const rQty = num(m[2]);
                                        const rAmt = num(m[3]);
                                        returnQtyTotal += rQty;
                                        returnAmountTotal += rAmt;
                                        return `${prod} (${rQty}) - ${rAmt.toFixed(2)}`;
                                    } else {
                                        // if cannot parse, just show the chunk
                                        return c;
                                    }
                                });
                                returnHtml = lines.join('<br>');
                            }
                        }
                    }

                    grandReturnQty += returnQtyTotal;
                    grandReturnAmount += returnAmountTotal;
                    
                    subReturnQty += returnQtyTotal;
                    subReturnAmt += returnAmountTotal;

                    // Price/Total columns will show items on separate lines (if multiple)
                    const totalDisplay = perTotalArr.length ? perTotalArr.map(x => {
                        const v = num(x);
                        return v ? v.toFixed(2) : '0.00';
                    }).join('<br>') : (num(s.per_total) ? num(s.per_total).toFixed(2) : '-');

                    html += `<tr>
                        <td>${i+1}</td>
                        <td>${formatDate(createdAt)}</td>
                        <td>${s.invoice_no ?? '-'}</td>
                        <td>${s.customer_name ?? '-'}</td>
                        <td>${s.reference ?? '-'}</td>
                        <td>${products}</td>
                        <td>${unitArr.length ? unitArr.join('<br>') : '-'}</td>
                        <td>${qtyArr.length ? qtyArr.map(x => (num(x) ? num(x).toFixed(2) : '0.00')).join('<br>') : '-'}</td>
                        <td>${priceDisplay}</td>
                        <td>${totalDisplay}</td>
                        <td>${rowNet.toFixed(2)}</td>
                        <td>
                            ${num(s.cash) > 0 ? '<span class="badge bg-success">Cash</span>' : ''}
                            ${num(s.card) > 0 ? '<br><span class="badge bg-info">Card: ' + num(s.card).toFixed(2) + '</span>' : ''}
                        </td>
                        <td><div class="return-cell">${returnHtml || '-'}</div></td>
                    </tr>`;
                });
                
                // Add the final subtotal row before Grand Total
                if (currentGroupKey) {
                    html += renderSubtotalRow();
                }

                // Grand total row
                html += `<tr class="fw-bold bg-dark text-white" id="grandTotalRow">
                    <td colspan="6" class="text-end text-white">Grand Total:</td>
                    <td class="text-warning">
                        Pieces: ${grandPiece.toFixed(2)}<br>
                        Meters: ${grandMeter.toFixed(2)}<br>
                        Yards: ${grandYard.toFixed(2)}
                    </td>
                    <td class="text-warning">${grandQty.toFixed(2)}</td>
                    <td>-</td>
                    <td class="text-warning">${grandTotal.toFixed(2)}</td>
                    <td class="text-warning">${grandNet.toFixed(2)}</td>
                    <td class="text-warning">
                        Card Total:<br>
                        ${grandCard.toFixed(2)}
                    </td>
                    <td class="text-warning">Qty: ${grandReturnQty.toFixed(2)}<br>ReturnAmt: ${grandReturnAmount.toFixed(2)}</td>
                </tr>`;

                $('#saleBody').html(html);
            },
            error: function() {
                $("#loader").hide();
                alert('Failed to fetch report. Please try again.');
            }
        });
    });

    // Ensure DOM is loaded
    $(document).ready(function() {
        // CSV export
        $(document).on('click', '#btnExportCsv', function() {
            // build CSV from currently rendered table
            let csv = [];
            $("#saleReport tr").each(function() {
                let row = [];
                $(this).find('th,td').each(function() {
                    let cellHtml = $(this).html();

                    // <br> ko " | " se replace kardo
                    let cellText = cellHtml
                        .replace(/<br\s*\/?>/gi, " | ")
                        .replace(/&nbsp;/gi, " ")
                        .replace(/<[^>]*>/g, "")
                        .trim();

                    row.push('"' + cellText.replace(/"/g, '""') + '"');
                });
                csv.push(row.join(","));
            });

            let csvString = csv.join("\n");
            let blob = new Blob([csvString], {
                type: 'text/csv;charset=utf-8;'
            });

            let link = document.createElement("a");
            if (link.download !== undefined) {
                let url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "sale_report.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('CSV download not supported in this browser.');
            }
        });
    });
</script>


@endsection