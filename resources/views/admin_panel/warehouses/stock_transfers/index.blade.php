@extends('admin_panel.layout.app')
@section('content')
<style>
    tr.selected-row {
        background-color: #d9edf7 !important;
    }
</style>
<div class="card shadow-sm border-0">
     <div class="card-header">
         <h5 class="mb-0 fw-bold">🔄 Stock Transfer List</h5>
         <div class="d-flex align-items-center">
             <a href="{{ route('stock_transfers.create') }}" class="btn btn-primary btn-sm">+ New Transfer</a>
             <a id="exportTransfersAllBtn" class="dropdown-item btn btn-outline-secondary btn-sm" href="javascript:void(0)" style="width: auto; margin-left: 10px;">⬇ Export All</a>
             <button id="exportTransfersSelectedBtn" class="btn btn-outline-primary btn-sm" type="button" style="margin-left: 10px;">⬇ Export Selected</button>
             <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm rounded-pill px-3" style="margin-left: 10px;">← Back</a>
         </div>
     </div>


    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <div id="receiptContainer" style="display:none;"></div>

    <div class="card-body">
        <form method="GET" action="{{ route('stock_transfers.index') }}" class="row g-3 align-items-end mb-4">
            <div class="col-md-3">
                <label class="form-label fw-bold">Start Date:</label>
                <input type="date" name="start_date" class="form-control form-control-sm"
                    value="{{ request('start_date', \Carbon\Carbon::now()->format('Y-m-d')) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">End Date:</label>
                <input type="date" name="end_date" class="form-control form-control-sm"
                    value="{{ request('end_date', \Carbon\Carbon::now()->format('Y-m-d')) }}">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-success btn-sm w-100">Filter</button>
            </div>

            <div class="col-md-2">
                <a href="{{ route('stock_transfers.index') }}" class="btn btn-secondary btn-sm w-100">Reset</a>
            </div>
        </form>

        @if(request('start_date') && request('end_date'))
        <div class="alert alert-info py-2">
            Showing transfers from <strong>{{ request('start_date') }}</strong> to <strong>{{ request('end_date') }}</strong>
        </div>
        @endif
        <table class="table table-bordered table-striped" id="transferTable">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="selectAll">
                    </th>
                    <th>#</th>
                    <th>Date</th>
                    <th>From Location</th>
                    <th>Transfer Type</th>
                    <th>To Warehouse / Shop</th>
                    <th style="width: 250px;">Items (Qty)</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach($transfers as $transfer)
                <tr>
                    <td>
                        <input type="checkbox" class="row-check">
                    </td>
                    <td>{{ $loop->iteration }}</td>
                    <td data-order="{{ $transfer->created_at }}">
                        {{ $transfer->created_at ? \Carbon\Carbon::parse($transfer->created_at)->format('d-m-Y h:i A') : 'N/A' }}
                    </td>
                    <td>{{ $transfer->fromWarehouse->warehouse_name ?? 'Shop' }}</td>
                    <td class="fw-semibold text-capitalize">
                        {{ $transfer->transfer_to ?? '-' }}
                    </td>

                    <td>
                        @if($transfer->transfer_to === 'shop')
                        Shop
                        @elseif($transfer->transfer_to === 'warehouse')
                        {{ $transfer->toWarehouse->warehouse_name ?? '-' }}
                        @else
                        -
                        @endif
                    </td>

                    <td class="text-start align-top">
                        <div style="max-height: 180px; overflow-y: auto; font-size: 0.85rem; min-width: 240px;">
                            @if($transfer->items && count($transfer->items) > 0)
                                <table class="table table-sm table-borderless mb-0">
                                    @foreach($transfer->items as $item)
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td class="p-1">
                                            {{ $item['name'] }}
                                            @if(!empty($item['barcode']))
                                                <br><small class="text-muted fw-bold">{{ $item['barcode'] }}</small>
                                            @endif
                                        </td>
                                        <td class="p-1 text-end text-nowrap">
                                            <strong>{{ $item['qty'] ?? 0 }}</strong>
                                            <small class="text-muted ms-1">{{ $item['unit'] }}</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            @else
                                <div class="text-center">-</div>
                            @endif
                        </div>
                    </td>

                    <td>{{ $transfer->remarks ?? '-' }}</td>
                    <td>
                        <a href="{{ route('recipt.warehouse',$transfer->id) }}" class="btn btn-primary btn-sm">Recepit</a>
                        <!-- <button type="button" class="btn btn-danger btn-sm print-receipt" data-id="{{ $transfer->id }}">Print</button> -->
                    </td>
                </tr>
                @endforeach
            </tbody>

        </table>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#transferTable').DataTable({
            "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "lengthMenu": [
                [25, 50, 100, -1],
                [25, 50, 100, "All"]
            ],
            "pageLength": 25,
            "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 8]
                }
            ],
            "ordering": false,
            "scrollX": true,
            "autoWidth": false,
            "language": {
                "search": "",
                "searchPlaceholder": "Search transfers...",
                "lengthMenu": "_MENU_ entries"
            }
        });

        // Style the search input to look more premium
        $('.dataTables_filter input').addClass('form-control form-control-sm shadow-sm').css({
            'width': '250px',
            'border-radius': '20px',
            'padding-left': '15px'
        });
        $('.dataTables_length select').addClass('form-select form-select-sm shadow-sm').css({
            'border-radius': '8px'
        });
    });
</script>

<script>
    $(document).on('click', '.print-receipt', function() {
        let id = $(this).data('id');

        $.ajax({
            url: "{{ url('/warehouse-stock-receipt') }}/" + id,
            type: "GET",
            success: function(response) {
                // Load the full receipt HTML into hidden div
                $('#receiptContainer').html(response);

                // Open print window for that HTML
                let printContents = document.getElementById('receiptContainer').innerHTML;
                let printWindow = window.open('', '', 'width=400,height=600');
                printWindow.document.write(printContents);
                printWindow.document.close();
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            },
            error: function() {
                alert('Error fetching receipt.');
            }
        });
    });
</script>

<!-- SheetJS CDN (add before your script or inside scripts section) -->
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

<script>
    $(function() {
        // Make rows clickable to toggle selection for "Export Selected"


        // Select All
        $('#selectAll').on('change', function() {
            $('.row-check').prop('checked', this.checked).trigger('change');
        });

        // Row highlight on checkbox
        $(document).on('change', '.row-check', function() {
            $(this).closest('tr').toggleClass('selected-row', this.checked);
        });

        function trimText(t) {
            return (t || '').toString().trim();
        }

        // parse multi-line products cell into a single text string
        function normalizeProductsCell($td) {
            // if cell contains multiple <br> separated product lines, join with " | "
            var raw = $td.html() || $td.text() || '';
            // replace <br> with newline, strip tags
            raw = raw.replace(/<br\s*\/?>/gi, '\n');
            var div = document.createElement('div');
            div.innerHTML = raw;
            var text = div.textContent || div.innerText || '';
            var lines = text.split(/\n/).map(s => s.trim()).filter(Boolean);
            return lines.join(' | ');
        }

        // parse a table row (returns array in export column order)
        function parseTransferRow(tr) {
            var $tds = $(tr).find('td');

            var date    = trimText($tds.eq(2).text());
            var from    = trimText($tds.eq(3).text());
            var to      = trimText($tds.eq(5).text());
            
            // Cleanly extract Remarks from index 7
            var $remarksCell = $tds.eq(7).clone();
            $remarksCell.find('small, br').remove();
            var remarks = trimText($remarksCell.text()); 

            // Items (Qty) is at index 6
            var productLines = [];
            var barcodeLines = [];
            var qtyLines     = [];
            var uomLines     = [];

            // Find the inner table rows
            $tds.eq(6).find('table tr').each(function() {
                var $rowTds = $(this).find('td');
                if($rowTds.length >= 2) {
                    var $prodCell = $rowTds.eq(0);
                    
                    // Extract barcode from <small>
                    var barcode = trimText($prodCell.find('small').text());
                    
                    // Extract product name by removing <small> and <br> from a clone
                    var $nameClone = $prodCell.clone();
                    $nameClone.find('small, br').remove();
                    var nameText = trimText($nameClone.text());

                    productLines.push(nameText);
                    barcodeLines.push(barcode);

                    // Right TD: Qty + Unit
                    var qtyUnitText = trimText($rowTds.eq(1).text());
                    // qtyUnitText looks like "50 pcs"
                    var parts = qtyUnitText.split(/\s+/);
                    qtyLines.push(parts[0] || '0');
                    uomLines.push(parts.slice(1).join(' ') || '');
                }
            });

            var rows = [];
            var maxLen = Math.max(
                productLines.length,
                barcodeLines.length,
                qtyLines.length,
                uomLines.length,
                1
            );

            if(maxLen === 1 && productLines.length === 0) {
                 // Fallback for empty row: Date, From, To, Product, Barcode, Qty, UOM, Remarks
                 return [[date, from, to, '', '', '', '', remarks]];
            }

            for (var i = 0; i < maxLen; i++) {
                rows.push([
                    date,
                    from,
                    to,
                    productLines[i] || '',
                    barcodeLines[i] || '',
                    qtyLines[i] || '',
                    uomLines[i] || '',
                    remarks
                ]);
            }

            return rows;
        }




        // build workbook and download
        function buildAndDownload(rowsArray, filename) {
            var header = [
                'Date',
                'From Warehouse',
                'To Warehouse / Shop',
                'Product',
                'Barcode',
                'Qty',
                'UOM',
                'Remarks'
            ];
            var aoa = [header].concat(rowsArray);
            var ws = XLSX.utils.aoa_to_sheet(aoa);
            ws['!cols'] = [{
                    wpx: 120 // Date
                },
                {
                    wpx: 160 // From
                },
                {
                    wpx: 160 // To
                },
                {
                    wpx: 250 // Product
                },
                {
                    wpx: 150 // Barcode
                },
                {
                    wpx: 80 // Qty
                },
                {
                    wpx: 80 // UOM
                },
                {
                    wpx: 200 // Remarks
                }
            ];
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Transfers');
            XLSX.writeFile(wb, filename);
        }

        // Export ALL visible rows (respects any filtering)
        $('#exportTransfersAllBtn').on('click', function() {
            var rows = [];
            $('#transferTable tbody tr').each(function() {
                if ($(this).is(':hidden')) return;
                var r = parseTransferRow(this); // returns array of rows
                rows = rows.concat(r); // merge into main rows array
            });
            if (rows.length === 0) {
                alert('No rows to export.');
                return;
            }
            var ts = new Date().toISOString().replace(/[:\-T]/g, '').slice(0, 14);
            buildAndDownload(rows, 'stock_transfers_all_' + ts + '.xlsx');
        });

        // Export SELECTED (click rows to mark selection)
        $('#exportTransfersSelectedBtn').on('click', function() {
            var sel = [];
            $('#transferTable tbody tr').each(function() {
                if ($(this).find('.row-check').is(':checked')) {
                    var r = parseTransferRow(this);
                    sel = sel.concat(r);
                }
            });
            if (sel.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Selection',
                    text: 'Please select at least one record to export.'
                });
                return;
            }
            var ts = new Date().toISOString().replace(/[:\-T]/g, '').slice(0, 14);
            buildAndDownload(sel, 'stock_transfers_selected_' + ts + '.xlsx');
        });

    });
</script>

@endsection
