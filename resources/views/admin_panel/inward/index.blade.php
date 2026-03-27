@extends('admin_panel.layout.app')
<style>
    tr.selected-row {
        background-color: #d9edf7 !important;
    }

    .swal2-container .swal2-styled {
        background-color: #dd3333 !important;
    }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <h4 class="mb-0">List Inward Gatepasses</h4>
                            <div class="d-flex gap-2">
                                <a href="{{ route('add_inwardgatepass') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add New Inward Gatepass
                                </a>

                                <button id="exportGatepassAllBtn" class="btn btn-outline-secondary btn-sm">
                                    ⬇ Export All
                                </button>

                                <button id="exportGatepassSelectedBtn" class="btn btn-outline-primary btn-sm">
                                    ⬇ Export Selected
                                </button>
                            </div>

                        </div>

                        <div class="card-body">
                    <!-- Filter Row -->
                    <div class="row mb-3">
                        <form action="{{ route('InwardGatepass.home') }}" method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                            
                            <div>
                                <label class="fw-bold small">Start Date</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                            </div>

                            <div>
                                <label class="fw-bold small">End Date</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                            </div>

                            <div>
                                <label class="fw-bold small">Status</label>
                                <select name="status" class="form-select form-select-sm" style="min-width: 150px;">
                                    <option value="">All</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Unbilled (Pending)</option>
                                    <option value="linked" {{ request('status') == 'linked' ? 'selected' : '' }}>Billed (Linked)</option>
                                </select>
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <a href="{{ route('InwardGatepass.home') }}" class="btn btn-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </div>

                        </form>
                    </div>

                    <div class="table-responsive mt-2 mb-5">
                                        <table id="gatepass-table" class="table table-bordered">
                                            <thead class="text-center" style="background:#add8e6;">
                                                <tr>
                                                    <th>
                                                        <input type="checkbox" id="selectAllGatepass">
                                                    </th>
                                                    <th style="width:60px;">ID</th>

                                                    <!-- Inv column wider -->
                                                    <th style="width:140px; white-space:nowrap;">Inv</th>

                                                    <th style="width:110px;">Builty#</th>
                                                    <th style="width:110px;">Company Inv#</th>
                                                    <th style="width:120px;">Branch</th>
                                                    <th style="width:110px;">ReceivedIn</th>
                                                    <th style="width:150px;">Warehouse</th>
                                                    <th style="width:140px;">Vendor</th>
                                                    <th style="width:220px;">Items</th>
                                                    <th style="width:90px;">Qty</th>

                                                    <!-- Date column wider -->
                                                    <th style="width:120px; white-space:nowrap;">Date</th>

                                                    <th style="width:160px;">Note</th>
                                                    <th style="width:110px;">Status</th>
                                                    <th style="width:200px;">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody class="text-center">
                                                @foreach ($gatepasses as $gp)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="row-check-gatepass">
                                                    </td>
                                                    <td>{{ $gp->id }}</td>

                                                    <!-- Inv -->
                                                    <td style="white-space:nowrap;">
                                                        {{ $gp->invoice_no }}
                                                    </td>

                                                    <td>{{ $gp->gatepass_no }}</td>
                                                    <td>{{ $gp->company_invoice_no ?? 'Bill Not Generate' }}</td>
                                                    <td>{{ $gp->branch->name ?? 'N/A' }}</td>
                                                    <td>{{ $gp->receive_type }}</td>
                                                    <td>{{ $gp->warehouse->warehouse_name ?? 'N/A' }}</td>
                                                    <td>{{ $gp->vendor->name ?? 'N/A' }}</td>

                                                    <!-- Items -->
                                                    <td style="text-align:left; max-width:220px;">
                                                        @if($gp->items->count())
                                                        <div style="max-height:90px; overflow-y:auto; background:#fafafa; border-radius:6px; padding:6px;">
                                                            @foreach($gp->items as $item)
                                                            <div style="font-size:14px; border-bottom:1px dashed #ddd;">
                                                                <strong>{{ $item->product->item_name ?? 'N/A' }}</strong>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                        @else
                                                        <span class="badge bg-danger">Not Added Yet</span>
                                                        @endif
                                                    </td>

                                                    <!-- Qty -->
                                                    <td style="text-align:left; max-width:90px;">
                                                        @if($gp->items->count())
                                                        <div style="max-height:90px; overflow-y:auto;">
                                                            @foreach($gp->items as $item)
                                                            <div><strong class="text-muted">({{ $item->qty }})</strong></div>
                                                            @endforeach
                                                        </div>
                                                        @else
                                                        <span class="badge bg-danger">Not Added Yet</span>
                                                        @endif
                                                    </td>

                                                    <!-- Date -->
                                                    <td style="white-space:nowrap;">
                                                        {{ \Carbon\Carbon::parse($gp->gatepass_date)->format('d-m-Y') }}
                                                    </td>

                                                    <td>{{ $gp->remarks ?? 'N/A' }}</td>

                                                    <td>
                                                        @if ($gp->status == 'pending')
                                                        <span class="badge bg-warning">Pending</span>
                                                        @elseif($gp->status == 'linked')
                                                        <span class="badge bg-success">Linked</span>
                                                        @elseif($gp->status == 'cancelled')
                                                        <span class="badge bg-danger">Cancelled</span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <a href="{{ route('InwardGatepass.show', $gp->id) }}" class="btn btn-sm btn-info mb-1">
                                                            View
                                                        </a>

                                                        @if ($gp->status == 'pending')
                                                        <a href="{{ route('InwardGatepass.addDetails', $gp->id) }}" class="btn btn-sm btn-warning mb-1">
                                                            ➕ Add Details
                                                        </a>

                                                        <a href="{{ route('add_bill', $gp->id) }}" class="btn btn-sm btn-info mb-1">
                                                            Add Bill
                                                        </a>
                                                        @elseif($gp->status == 'linked')
                                                        <a href="{{ route('InwardGatepass.inv', $gp->id) }}" class="btn btn-sm btn-success mb-1">
                                                            Invoice
                                                        </a>
                                                        <a href="{{ route('edit_bill', $gp->id) }}" class="btn btn-sm btn-warning mb-1">
                                                            Edit Bill
                                                        </a>
                                                        @endif

                                                        <a href="{{ route('InwardGatepass.edit', $gp->id) }}"
                                                            class="btn btn-sm mb-1"
                                                            style="background:#add8e6">
                                                            Edit
                                                        </a>

                                                        @if ($gp->status == 'pending' && auth()->user()->email === 'admin@admin.com')
                                                        <form action="{{ route('InwardGatepass.destroy', $gp->id) }}"
                                                            method="POST"
                                                            class="delete-form d-inline">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="button"
                                                                class="btn btn-sm btn-danger btn-delete">
                                                                🗑 Delete
                                                            </button>
                                                        </form>
                                                        @endif
                                                    </td>

                                                </tr>
                                                @endforeach
                                            </tbody>

                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>






                </div>
            </div>
        </div>
    </div>
</div>
@endsection


{{-- SweetAlert --}}
@section('scripts')
<script>
    // Delete confirm
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        let form = $(this).closest('form');

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to delete this gatepass!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Success alert after delete
    @if(session('success'))
    Swal.fire({
        title: 'Done!',
        text: "{{ session('success') }}",
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
    @endif
</script>


<script>
    $(document).ready(function() {
        $('#gatepass-table').DataTable({
            "pageLength": 10,
            "lengthMenu": [5, 10, 25, 50, 100],
            "order": [
                [0, 'desc']
            ],
            "language": {
                "search": "Search Gatepass:",
                "lengthMenu": "Show _MENU_ entries"
            }
        });
    });
</script>


<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(function() {

        /* ================= SELECT ROW LOGIC ================= */

        $('#selectAllGatepass').on('change', function() {
            $('.row-check-gatepass').prop('checked', this.checked).trigger('change');
        });

        $(document).on('change', '.row-check-gatepass', function() {
            $(this).closest('tr').toggleClass('selected-row', this.checked);
        });

        function cleanText(t) {
            return (t || '').toString().replace(/\s+/g, ' ').trim();
        }

        // New Logic: Returns an ARRAY of rows (one per product item)
        function parseGatepassRow(tr) {
            let $tds = $(tr).find('td');

            // Common columns
            let id        = cleanText($tds.eq(1).text());
            let inv       = cleanText($tds.eq(2).text());
            let builty    = cleanText($tds.eq(3).text());
            let compInv   = cleanText($tds.eq(4).text());
            let branch    = cleanText($tds.eq(5).text());
            let recvIn    = cleanText($tds.eq(6).text());
            let warehouse = cleanText($tds.eq(7).text());
            let vendor    = cleanText($tds.eq(8).text());
            
            // 9=Items, 10=Qty
            let date      = cleanText($tds.eq(11).text());
            let note      = cleanText($tds.eq(12).text());
            let status    = cleanText($tds.eq(13).text());

            // Extract Items
            let itemLines = [];
            // The structure is usually: <div wrapper> <div item>...</div> ... </div>
            // We'll target the inner divs
            let $itemContainer = $tds.eq(9).find('div').first(); 
            if($itemContainer.length > 0) {
                 $itemContainer.children('div').each(function(){
                    itemLines.push(cleanText($(this).text()));
                 });
            } else {
                 // Fallback if structure is different or empty
                 let t = cleanText($tds.eq(9).text());
                 if(t && t !== 'Not Added Yet') itemLines.push(t);
            }

            // Extract Qty
            let qtyLines = [];
            let $qtyContainer = $tds.eq(10).find('div').first();
             if($qtyContainer.length > 0) {
                 $qtyContainer.children('div').each(function(){
                    let txt = cleanText($(this).text());
                    // Remove parens "(50)" -> "50"
                    txt = txt.replace(/[()]/g, '').trim(); 
                    qtyLines.push(txt);
                 });
            } else {
                 let t = cleanText($tds.eq(10).text());
                 if(t && t !== 'Not Added Yet') qtyLines.push(t);
            }

            // Combine into multiple rows
            let rows = [];
            let maxLen = Math.max(itemLines.length, qtyLines.length, 1);

            for (let i = 0; i < maxLen; i++) {
                rows.push([
                    id, inv, builty, compInv, branch, recvIn, warehouse, vendor, 
                    itemLines[i] || '', 
                    qtyLines[i] || '', 
                    date, note, status
                ]);
            }
            return rows;
        }

        function exportExcel(rows, filename) {
            let header = [
                'ID', 'Invoice', 'Builty', 'Company Inv', 'Branch', 'Received In',
                'Warehouse', 'Vendor', 'Items', 'Qty', 'Date', 'Note', 'Status'
            ];

            let sheetData = [header].concat(rows);
            let ws = XLSX.utils.aoa_to_sheet(sheetData);

            // Set Column Widths
            ws['!cols'] = [
                { wpx: 50 },  // ID
                { wpx: 120 }, // Inv
                { wpx: 100 }, // Builty
                { wpx: 120 }, // Comp Inv
                { wpx: 120 }, // Branch
                { wpx: 100 }, // Recv Type
                { wpx: 140 }, // Warehouse
                { wpx: 140 }, // Vendor
                { wpx: 250 }, // Items
                { wpx: 80 },  // Qty
                { wpx: 110 }, // Date
                { wpx: 200 }, // Note
                { wpx: 100 }  // Status
            ];

            let wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Inward Gatepasses');
            XLSX.writeFile(wb, filename);
        }

        /* ================= EXPORT ALL ================= */

        $('#exportGatepassAllBtn').on('click', function() {
            let rows = [];

            $('#gatepass-table tbody tr').each(function() {
                if (!$(this).is(':hidden')) {
                    // Use concat to merge array of rows
                    rows = rows.concat(parseGatepassRow(this));
                }
            });

            if (!rows.length) {
                alert('No data to export');
                return;
            }

            let ts = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
            exportExcel(rows, 'inward_gatepass_all_' + ts + '.xlsx');
        });

        /* ================= EXPORT SELECTED ================= */

        $('#exportGatepassSelectedBtn').on('click', function() {
            let rows = [];

            $('#gatepass-table tbody tr').each(function() {
                if ($(this).find('.row-check-gatepass').is(':checked')) {
                    rows = rows.concat(parseGatepassRow(this));
                }
            });

            if (!rows.length) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Selection',
                    text: 'Please select at least one gatepass'
                });
                return;
            }

            let ts = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
            exportExcel(rows, 'inward_gatepass_selected_' + ts + '.xlsx');
        });

    });

    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {

                let form = this.closest('.delete-form');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This Inward Gatepass will be deleted and the added stock will be reversed.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33', // RED = danger
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });

            });
        });

    });
</script>

@endsection