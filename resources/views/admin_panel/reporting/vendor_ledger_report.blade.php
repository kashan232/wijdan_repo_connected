@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>Vendor Ledger</h4>
                    <h6>View ledger by date range</h6>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form id="ledgerForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Vendor</label>
                            <select name="Vendor_id" id="Vendor_id" class="form-control" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div id="loader" style="display:none;text-align:center;margin-bottom:10px;">
                        <div class="spinner-border" role="status"></div>
                    </div>
                    <div class="text-end mb-3">
                        <button id="exportPdfBtn" class="btn btn-danger btn-sm px-4">
                            <i class="fas fa-file-pdf me-1"></i> Export PDF
                        </button>
                    </div>
                    <div id="ledgerBox" style="display:none;">
                        <div class="ledger-box" id="ledgerPdfArea">
                            <div class="ledger-title">VENDOR LEDGER</div>
                            <div id="ledgerHeader" class="ledger-header mb-3"></div>



                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="text-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv / Ref</th>
                                            <th>Description</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ledgerBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
<style>
    /* Ledger Box */
    .ledger-box {
        border: 3px solid #000;
        padding: 25px;
        margin: 25px auto;
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    }

    .ledger-title {
        text-align: center;
        font-weight: 700;
        font-size: 22px;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #000;
        border-bottom: 3px solid #000;
        padding-bottom: 8px;
    }

    /* Ledger Header */
    .ledger-header {
        padding: 10px 15px;
        border: 3px solid #000;
        margin-bottom: 20px;
        background: #f8f9fa;
        font-size: 14px;
        border-radius: 4px;
        font-weight: 600;
        color: #000;
    }

    .ledger-header strong {
        color: #000;
        font-weight: 700;
    }

    /* Ledger Table */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        border: 3px solid #000;
    }

    table thead tr th {
        background: #e0e0e0;
        color: #000;
        font-weight: 700;
        text-align: center;
        padding: 10px;
        border: 2px solid #000 !important;
        text-transform: uppercase;
    }

    table tbody tr td {
        border: 2px solid #000 !important;
        text-align: center;
        padding: 8px;
        color: #000;
        vertical-align: middle;
    }

    table tbody tr {
        border: 2px solid #000 !important;
    }

    table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .text-left {
        text-align: left !important;
    }

    /* Balance Colors */
    .opening-balance {
        font-weight: 600;
        background-color: #f3f4f5;
    }

    .balance-positive {
        color: #198754;
        font-weight: 700;
    }

    .balance-negative {
        color: #dc3545;
        font-weight: 700;
    }

    .balance-neutral {
        color: #0d6efd;
        font-weight: 700;
    }

    /* Totals Row */
    .totals-row td {
        font-weight: 700;
        background: #e9ecef;
        border-top: 3px solid #000 !important;
        border-bottom: 3px solid #000 !important;
    }

    /* Form Styling */
    #btnSearch {
        font-weight: 600;
        letter-spacing: 0.3px;
        border-radius: 6px;
    }

    select.form-control,
    input.form-control {
        border-radius: 6px;
        border: 1px solid #000;
    }

    /* Loader */
    #loader .spinner-border {
        color: #000;
        width: 2rem;
        height: 2rem;
    }

    /* Page Title */
    .page-title h4 {
        font-weight: 700;
        color: #000;
    }

    .page-title h6 {
        color: #555;
        font-weight: 500;
    }
</style>


@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#Vendor_id').select2({
            placeholder: "Select Vendor",
            allowClear: true,
            width: '100%' // Fix for bootstrap width issue
        });
        $(document).on('click', '#btnSearch', function() {

            let cid = $("#Vendor_id").val();
            let start = $("#start_date").val();
            let end = $("#end_date").val();

            function formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}-${month}-${year}`;
            }


            if (!cid || !start || !end) {
                alert("Select all fields");
                return;
            }

            $("#loader").show();
            $.get("{{ route('report.vendor.ledger.fetch') }}", {
                vendor_id: cid,
                start_date: start,
                end_date: end
            }, function(res) {

                $("#loader").hide();
                $("#ledgerBox").show();

                $("#ledgerHeader").html(`
                    <strong>Vendor:</strong> ${res.vendor.name}
                    <span style="float:right;">
                        <strong>Duration:</strong> ${formatDate(start)} to ${formatDate(end)}
                    </span>
                `);

                let totalDebit = 0;
                let totalCredit = 0;
                let lastBalance = parseFloat(res.opening_balance) || 0;


                // Opening Balance Row
                let html = `
<tr>
    <td>N/A</td>
    <td>-</td>
    <td>-</td>
    <td>-</td>
    <td class="text-left opening-balance">Opening Balance</td>
    <td class="balance-neutral">Rs. ${lastBalance.toFixed(2)}</td>
</tr>
`;

                res.transactions.forEach((t) => {
                    let debit = parseFloat(t.debit) || 0;
                    let credit = parseFloat(t.credit) || 0;

                    totalDebit += debit;
                    totalCredit += credit;

                    lastBalance = lastBalance + debit - credit;

                    html += `
    <tr>
        <td>${formatDate(t.date.split(" ")[0])}</td>
        <td>${t.invoice ?? '-'} ${t.reference ? '- (' + t.reference + ')' : ''}</td>
        <td class="text-left">${t.description}</td>
        <td>${debit > 0 ? 'Rs. ' + debit.toFixed(2) : '-'}</td>
        <td>${credit > 0 ? 'Rs. ' + credit.toFixed(2) : '-'}</td>
        <td class="${lastBalance > 0 ? 'balance-positive' : (lastBalance < 0 ? 'balance-negative' : 'balance-neutral')}">
            Rs. ${lastBalance.toFixed(2)}
        </td>
    </tr>`;
                });


                // Totals Row
                html += `
    <tr class="totals-row">
        <td colspan="3" class="text-left">Totals:</td>
        <td>Rs. ${totalDebit.toFixed(2)}</td>
        <td>Rs. ${totalCredit.toFixed(2)}</td>
        <td class="${lastBalance > 0 ? 'balance-positive' : (lastBalance < 0 ? 'balance-negative' : 'balance-neutral')}">
            Rs. ${lastBalance.toFixed(2)}
        </td>
    </tr>
`;
                $("#ledgerBody").html(html);
            });
        });
    });

    // PDF Export Function
    $("#exportPdfBtn").on("click", function() {

        if ($("#ledgerBox").is(":hidden")) {
            alert("Please generate ledger first");
            return;
        }

        const element = document.getElementById("ledgerPdfArea");

        const opt = {
            margin: [10, 10, 10, 10], // top, left, bottom, right
            filename: 'Vendor_Ledger.pdf',
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 2,
                useCORS: true,
                scrollY: 0
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait'
            }
        };

        html2pdf().set(opt).from(element).save();
    });
</script>

@endsection
