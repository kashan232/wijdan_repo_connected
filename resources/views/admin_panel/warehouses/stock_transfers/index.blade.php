@extends('admin_panel.layout.app')
@section('content')
<style>
    tr.selected-row {
        background-color: #d9edf7 !important;
    }
</style>
<div class="card shadow-sm border-0">
    <div class="d-flex gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm rounded-pill px-3">← Back</a>
        <h5 class="mb-0 text-center flex-grow-1">🔄 Stock Transfer List</h5>
        <a href="{{ route('stock_transfers.create') }}" class="btn btn-primary btn-sm">+ New Transfer</a>

        <!-- EXPORT buttons -->
        <a id="exportTransfersAllBtn" class="btn btn-outline-secondary btn-sm" href="javascript:void(0)">⬇ Export All</a>
        <button id="exportTransfersSelectedBtn" class="btn btn-outline-primary btn-sm" type="button">⬇ Export Selected</button>
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
                    <th>Products</th>
                    <th>Qty</th>
                    <th>UOM</th>
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
                        @forelse($transfer->items as $item)
                        <div>{{ $item['name'] }}</div>
                        @empty
                        <div>-</div>
                        @endforelse
                    </td>

                    {{-- QTY column --}}
                    <td class="text-center align-top">
                        @forelse($transfer->items as $item)
                        <div><strong>{{ $item['qty'] }}</strong></div>
                        @empty
                        <div>0</div>
                        @endforelse
                    </td>
                    <td class="text-center align-top">
                        @forelse($transfer->items as $item)
                        <div>{{ $item['unit'] }}</div>
                        @empty
                        <div>-</div>
                        @endforelse
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
            "lengthMenu": [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ], // <-- add "All" option
            "pageLength": 25, // default rows per page
            "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 9]
                } // disable sorting on checkbox & Action column
            ],
            "ordering": false,
            "scrollX": true, // horizontal scroll if needed
            "autoWidth": false
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

            var date = trimText($tds.eq(2).text());
            var from = trimText($tds.eq(3).text());
            var to = trimText($tds.eq(5).text());
            var remarks = trimText($tds.eq(9).text()); // ⚠️ index change

            // Products
            var productLines = [];
            $tds.eq(6).find('div').each(function() {
                var t = trimText($(this).text());
                if (t) productLines.push(t);
            });

            // Qty
            var qtyLines = [];
            $tds.eq(7).find('div').each(function() {
                var t = trimText($(this).text());
                if (t) qtyLines.push(t);
            });

            // ✅ UOM (NEW)
            var uomLines = [];
            $tds.eq(8).find('div').each(function() {
                var t = trimText($(this).text());
                if (t) uomLines.push(t);
            });

            var rows = [];
            var maxLen = Math.max(
                productLines.length,
                qtyLines.length,
                uomLines.length
            );

            for (var i = 0; i < maxLen; i++) {
                rows.push([
                    date,
                    from,
                    to,
                    productLines[i] || '',
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
                'Qty',
                'UOM',
                'Remarks'
            ];
            var aoa = [header].concat(rowsArray);
            var ws = XLSX.utils.aoa_to_sheet(aoa);
            ws['!cols'] = [{
                    wpx: 90
                },
                {
                    wpx: 160
                },
                {
                    wpx: 160
                },
                {
                    wpx: 300
                },
                {
                    wpx: 100
                },
                {
                    wpx: 90
                }, // ✅ UOM
                {
                    wpx: 200
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
