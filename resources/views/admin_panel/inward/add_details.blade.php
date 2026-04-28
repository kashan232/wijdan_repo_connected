@extends('admin_panel.layout.app')

@section('content')
<style>
    .searchResults {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        max-height: 200px;
        background: #fff;
        border: 1px solid #ccc;
        z-index: 99999;
    }

    #gatepassItems,
    .table-responsive {
        overflow: visible !important;
    }

    .remove-row {
        min-width: 32px;
    }

    .form-section {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .form-section h6 {
        font-weight: 600;
        margin-bottom: 15px;
    }

    .searchResults .active,
    .modal-product-item.active {
        background: #0d6efd !important;
        color: #fff !important;
    }

    .modal-product-item.active .product-price,
    .modal-product-item.active strong,
    .modal-product-item.active small,
    .modal-product-item.active div {
        color: white !important;
    }

    .unit-total-box {
        min-width: 90px;
        padding: 6px 8px;
        border-radius: 8px;
        text-align: center;
    }

    .unit-total-box .label {
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .unit-total-box input {
        font-weight: bold;
        text-align: center;
        border: none;
        background: rgba(255, 255, 255, 0.9);
        height: 30px;
    }
</style>

<div class="main-content">
    <div class="container-fluid">

        <div class="d-flex justify-content-between mb-3">
            <h5>➕ Add Items – Inward Gatepass</h5>
            <a href="{{ route('InwardGatepass.home') }}" class="btn btn-danger btn-sm">Back</a>
        </div>

        <div class="card">
            <div class="card-body">

                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
                @endif

                <form action="{{ route('InwardGatepass.storeDetails', $gatepass->id) }}"
                    method="POST"
                    id="gatepassForm"
                    novalidate>
                    @csrf

                    <input type="hidden" name="gatepass_id" value="{{ $gatepass->id }}">

                    <!-- ================= HEADER (READ ONLY) ================= -->
                    <div class="form-section">
                        <h6>📄 Gatepass Information</h6>
                        <div class="row g-4">
                            <div class="col-md-3">
                                <label>Date</label>
                                <input type="date" class="form-control" value="{{ $gatepass->gatepass_date }}" disabled>
                            </div>

                            <div class="col-md-3">
                                <label>Invoice #</label>
                                <input type="text" class="form-control" value="{{ $gatepass->invoice_no }}" disabled>
                            </div>

                            <div class="col-md-3">
                                <label>Branch</label>
                                <input type="text" class="form-control" value="{{ $gatepass->branch ? $gatepass->branch->name : 'N/A' }}" disabled>
                            </div>

                            <div class="col-md-3">
                                <label>Received Type</label>
                                <input type="text" class="form-control" name="receive_type" value="{{ $gatepass->receive_type }}" disabled>
                            </div>

                            <div class="col-md-3">
                                <label>Warehouse</label>
                                <input type="text" class="form-control"
                                    value="{{ $gatepass->warehouse ? $gatepass->warehouse->warehouse_name : 'N/A' }}"
                                    disabled>
                            </div>

                            <div class="col-md-3">
                                <label>Vendor</label>
                                <input type="text" class="form-control" value="{{ $gatepass->vendor ? $gatepass->vendor->name : 'N/A' }}" disabled>
                            </div>

                            <div class="col-md-3">
                                <label>Transport</label>
                                <input type="text" class="form-control" value="{{ $gatepass->transport_name }}" disabled>
                            </div>

                            <div class="col-md-3">
                                <label>Bilty No</label>
                                <input type="text" class="form-control" value="{{ $gatepass->gatepass_no }}" disabled>
                            </div>

                            <div class="col-md-12">
                                <label>Remarks</label>
                                <input type="text" class="form-control" value="{{ $gatepass->remarks }}" disabled>
                            </div>
                        </div>
                    </div>

                    <!-- ================= PRODUCT TABLE ================= -->
                    <button type="button" class="btn btn-primary btn-sm mt-2 mb-2" id="openProductModal">
                        Search Product (F2)
                    </button>
                    <div class="modal fade" id="productModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h5 class="modal-title">Search Product</h5>
                                    <button type="button" class="btn-close" data-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">

                                    <input type="text"
                                        id="modalProductSearch"
                                        class="form-control mb-2"
                                        placeholder="Type product name or scan barcode"
                                        autofocus>

                                    <ul class="list-group" id="modalSearchResults"
                                        style="max-height:300px; overflow-y:auto;"></ul>

                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <h6>📦 Add Products</h6>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="text-center bg-light">
                                    <th width="220">Product</th>
                                    <th width="120">Item Code</th>
                                    <th width="140">Brand</th>
                                    <th width="100">Unit</th>
                                    <th width="90">Qty</th>
                                    <th width="120">Location</th>
                                    <th width="180">Note</th>
                                    <th width="80">Action</th>

                                </thead>

                                <tbody id="gatepassItems">

                                    @forelse($gatepass->items as $item)
                                    <tr>
                                        <td style="position:relative">
                                            <input type="hidden" name="existing_item_id[]" value="{{ $item->id }}">
                                            <input type="hidden" name="product_id[]" class="product_id" value="{{ $item->product_id }}">
                                            <input type="text" class="form-control productSearch"
                                                value="{{ $item->product->item_name ?? '' }}" placeholder="Type name OR scan barcode" readonly>
                                        </td>

                                        <td>
                                            <input type="text" name="item_code[]" class="form-control"
                                                value="{{ $item->product->item_code ?? '' }}" readonly>
                                        </td>

                                        <td>
                                            <input type="text" name="brand[]" class="form-control"
                                                value="{{ $item->product->brand->name ?? '' }}" readonly>
                                        </td>

                                        <td>
                                            <input type="text" name="unit[]" class="form-control"
                                                value="{{ $item->product->unit_id ?? '' }}" readonly>
                                        </td>

                                        <td>
                                            <input type="number"
                                                name="qty[]"
                                                class="form-control quantity"
                                                value="{{ $item->qty ?? '' }}"
                                                min="0"
                                                step="any">
                                        </td>
                                        
                                        <td>
                                            <select name="item_receive_type[]" class="form-control">
                                                <option value="shop" {{ ($item->receive_type ?? $gatepass->receive_type) == 'shop' ? 'selected' : '' }}>Shop</option>
                                                <option value="warehouse" {{ ($item->receive_type ?? $gatepass->receive_type) == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                                            </select>
                                        </td>

                                        <td>
                                            <input type="text"
                                                name="note[]"
                                                class="form-control"
                                                value="{{ $item->note ?? '' }}"
                                                placeholder="Optional note">
                                        </td>

                                        <!-- ✅ ACTION (merged) -->
                                        <td class="text-center">
                                            <span class="badge bg-secondary d-block mb-1">Saved</span>
                                            <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
                                        </td>
                                    </tr>

                                    @empty
                                    <tr>
                                        <td style="position:relative">
                                            <input type="hidden" name="existing_item_id[]" value="">
                                            <input type="hidden" name="product_id[]" class="product_id">
                                            <input type="text"
                                                class="form-control productSearch"
                                                placeholder="Select product from Search (F2)"
                                                readonly>
                                        </td>
                                        <td><input type="text" name="item_code[]" class="form-control" readonly></td>
                                        <td><input type="text" name="brand[]" class="form-control" readonly></td>
                                        <td><input type="text" name="unit[]" class="form-control" readonly></td>
                                        <td><input type="number"
                                                name="qty[]"
                                                class="form-control quantity"
                                                value=""
                                                min="0"
                                                step="any"></td>

                                        <td>
                                            <select name="item_receive_type[]" class="form-control">
                                                <option value="shop" {{ $gatepass->receive_type == 'shop' ? 'selected' : '' }}>Shop</option>
                                                <option value="warehouse" {{ $gatepass->receive_type == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                                            </select>
                                        </td>

                                        <!-- ✅ NOTE -->
                                        <td>
                                            <input type="text" name="note[]" class="form-control" placeholder="Optional note">
                                        </td>

                                        <!-- ✅ ACTION -->
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
                                        </td>
                                    </tr>

                                    @endforelse

                                </tbody>


                                <tfoot id="unitTotals"></tfoot>

                            </table>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success px-5">
                            ✔ Save Items & Finalize
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>
@endsection
@section('scripts')
<script>
    let IS_SCANNING = false;

    function addRow() {
        $('#gatepassItems').append(`
<tr>
    <td style="position:relative">
        <input type="hidden" name="existing_item_id[]" value="">
        <input type="hidden" name="product_id[]" class="product_id">
        <input type="text"
       class="form-control productSearch"
       placeholder="Select product from Search (F2)"
       readonly>
    </td>
    <td><input type="text" name="item_code[]" class="form-control" readonly></td>
    <td><input type="text" name="brand[]" class="form-control" readonly></td>
    <td><input type="text" name="unit[]" class="form-control" readonly></td>
    <td> <input type="number"
        name="qty[]"
        class="form-control quantity"
        value=""
        min="0"
        step="any"></td>
    <td>
        <select name="item_receive_type[]" class="form-control">
            <option value="shop" {{ $gatepass->receive_type == 'shop' ? 'selected' : '' }}>Shop</option>
            <option value="warehouse" {{ $gatepass->receive_type == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
        </select>
    </td>
    <td>
        <input type="text" name="note[]" class="form-control" placeholder="Optional note">
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
    </td>
</tr>
`);
    }

    $(document).ready(function() {
        calculateUnitWiseTotals();
    });
    const unitColors = {
        Yard: 'primary', // Blue
        Piece: 'success', // Green
        Meter: 'warning' // Yellow
    };


    function calculateUnitWiseTotals() {

        let totals = {}; // { Yard: 10, Piece: 2 }

        $('#gatepassItems tr').each(function() {

            let unit = $(this).find('[name="unit[]"]').val();
            let qty = parseFloat($(this).find('.quantity').val());

            if (!unit || isNaN(qty)) return;

            totals[unit] = (totals[unit] || 0) + qty;
        });

        let html = `
    <tr class="bg-light">
        <td colspan="7">
            <div class="d-flex gap-2 flex-wrap justify-content-end">
    `;

        Object.keys(totals).forEach(unit => {

            let color = unitColors[unit] || 'secondary';

            html += `
        <div class="unit-total-box bg-${color} text-${color === 'warning' ? 'dark' : 'white'}">
            <div class="label">${unit}</div>
            <input type="text" value="${totals[unit].toFixed(2)}" readonly>
        </div>
        `;
        });

        html += `
            </div>
        </td>
    </tr>
    `;

        $('#unitTotals').html(html);
    }




    $(document).ready(function() {

        setTimeout(() => {
            $('#gatepassItems tr:last .productSearch').blur();
        }, 300);

        /* ================= REMOVE ROW ================= */
        $(document).on('click', '.remove-row', function() {
            if ($('#gatepassItems tr').length > 1) {
                $(this).closest('tr').remove();
                calculateUnitWiseTotals();

            }
        });
        $(document).on('click', '.remove-row', function() {
            setTimeout(calculateUnitWiseTotals, 50);
        });

        /* ================= FORM SUBMIT VALIDATION ================= */
        $('#gatepassForm').on('submit', function(e) {

            // remove empty rows
            $('#gatepassItems tr').each(function() {
                if (!$(this).find('.product_id').val()) {
                    $(this).remove();
                }
            });

            if ($('input[name="product_id[]"]').length === 0) {
                e.preventDefault();
                Swal.fire('Error', 'Please add at least one product', 'error');
                addRow();
                return false;
            }
        });

        /* ================= PREVENT ENTER SUBMIT ================= */
        $('#gatepassForm').on('keypress', function(e) {
            if (e.key === 'Enter' && e.target.type !== 'number') {
                e.preventDefault();
            }
        });

    });


    const SCAN_GAP = 50; // ms (scanner fast)
    const SCAN_TIMEOUT = 80;






    function handleBarcodeScan(barcode) {

        $.get("{{ route('search-product-by-barcode') }}", {
            barcode
        }, function(res) {

            if (!res) {
                Swal.fire('Not Found', 'Product not found', 'warning');
                return;
            }

            let foundRow = null;

            $('#gatepassItems tr').each(function() {
                const pid = $(this).find('.product_id').val();
                if (pid && +pid === +res.id) {
                    foundRow = $(this);
                    return false;
                }
            });

            // SAME PRODUCT → qty +1
            if (foundRow) {
                const qty = foundRow.find('.quantity');
                qty.val((+qty.val() || 0) + 1);
                return;
            }

            // NEW PRODUCT
            let row = $('#gatepassItems tr:last');
            if (row.find('.product_id').val()) {
                addRow();
                row = $('#gatepassItems tr:last');
            }

            row.find('.product_id').val(res.id);
            row.find('.productSearch').val(res.name);
            row.find('[name="item_code[]"]').val(res.code);
            row.find('[name="brand[]"]').val(res.brand);
            row.find('[name="unit[]"]').val(res.unit);
            row.find('.quantity').val(1);

            calculateUnitWiseTotals();

        });
    }


    let scanBuffer = '';
    let scanTimer = null;

    $(document).on('keydown', function(e) {

        // agar focus productSearch ya textarea me hai → ignore
        if ($(e.target).hasClass('productSearch') || $(e.target).is('textarea')) return;

        // F2 modal
        if (e.key === 'F2') {
            e.preventDefault();
            e.stopPropagation();
            openProductModal();
            return false;
        }

        // scanner Enter
        if (e.key === 'Enter') {
            e.preventDefault();

            if (scanBuffer.length >= 5) {
                console.log('SCANNED BARCODE:', scanBuffer);
                handleBarcodeScan(scanBuffer);
            }

            scanBuffer = '';
            return;
        }

        // collect numeric keys
        if (/^[0-9]$/.test(e.key)) {
            scanBuffer += e.key;
        }

        // reset buffer if scanner slow
        clearTimeout(scanTimer);
        scanTimer = setTimeout(() => {
            scanBuffer = '';
        }, 150); // safe for most scanners
    });




    $('#openProductModal').on('click', function(e) {
        e.preventDefault();
        openProductModal();
    });

    $(document).on('click', '.modal-product-item', function() {
        $('#productModal').modal('hide');

        // Reset search box
        $('#modalProductSearch').val('');
        $('#modalSearchResults').empty();
    });
    $('#productModal').on('shown.bs.modal', function() {
        const $input = $('#modalProductSearch');
        $input.focus(); // Cursor focus on search input
        activeIndex = 0;
        setActiveItem(activeIndex); // First item active
    });


    function openProductModal() {

        $('#modalProductSearch').val('');
        $('#modalSearchResults').empty();

        $('#productModal').modal('show');

        $('#productModal').one('shown.bs.modal', function() {
            $('#modalProductSearch').focus();
            activeIndex = 0;
        });
    }

    function calculateTotalQty() {
        let total = 0;

        $('.quantity').each(function() {
            let val = parseFloat($(this).val());
            if (!isNaN(val)) {
                total += val;
            }
        });

        $('#totalQty').val(total.toFixed(2));
    }

    $(document).on('input', '.quantity', function() {

        let row = $(this).closest('tr');
        let unit = row.find('[name="unit[]"]').val();
        let val = $(this).val();

        if (unit === 'Piece') {
            // integer only
            $(this).val(Math.floor(val));
        }

        calculateUnitWiseTotals();
    });



    let modalTimer = null;

    $('#modalProductSearch').on('input', function() {
        clearTimeout(modalTimer);
        let q = $(this).val().trim();

        if (q.length < 2) {
            $('#modalSearchResults').empty();
            return;
        }

        modalTimer = setTimeout(() => {
            $.get("{{ route('search-product-name') }}", {
                q
            }, function(res) {

                let html = '';
                res.forEach(p => {

                    let noteText = (p.note && p.note.trim() !== '') ? p.note : '-';

                    html += `
<li class="list-group-item modal-product-item"
    data-id="${p.id}"
    data-name="${p.item_name}"
    data-code="${p.item_code}"
    data-price="${p.price}"
    data-unit="${p.unit_id}"
    data-brand="${p.brand ?? ''}"
    data-note="${noteText}">
    <strong>${p.item_name}</strong>
    <br>
    <div class="mt-1"><span class="product-price" style="font-weight: 800; font-size: 1.3rem; color: #2563eb;">Rs: ${p.price}</span> | <small>${p.brand ?? '-'}</small></div>
    <br>
    <strong class="text-Dark" style="font-size:1rem;">Note: ${noteText}</strong>

</li>`;
                });

                $('#modalSearchResults').html(html);
                activeIndex = -1;
                setActiveItem(0);
            });
        }, 250);
    });

    $(document).on('mouseenter', '.modal-product-item', function() {
        const index = $(this).index();
        setActiveItem(index);
    });

    $('#modalProductSearch').on('keydown', function(e) {

        const items = $('#modalSearchResults .modal-product-item');

        if (!items.length) return;

        switch (e.key) {

            case 'ArrowDown':
                e.preventDefault();
                setActiveItem(activeIndex + 1);
                break;

            case 'ArrowUp':
                e.preventDefault();
                setActiveItem(activeIndex - 1);
                break;

            case 'Enter':
                e.preventDefault();
                if (activeIndex >= 0) {
                    items.eq(activeIndex).trigger('click');
                }
                break;
        }
    });

    $(document).on('click', '.modal-product-item', function() {

        let row = $('#gatepassItems tr:last');

        if (row.find('.product_id').val()) {
            addRow();
            row = $('#gatepassItems tr:last');
        }

        row.find('.product_id').val($(this).data('id'));
        row.find('.productSearch').val($(this).data('name'));
        row.find('[name="item_code[]"]').val($(this).data('code'));
        row.find('[name="brand[]"]').val($(this).data('brand'));
        row.find('[name="unit[]"]').val($(this).data('unit'));
        row.find('.quantity').val(1);

        calculateUnitWiseTotals();

        $('#productModal').modal('hide');

        setTimeout(() => {
            row.find('.quantity').focus();
        }, 150);
    });


    let activeIndex = -1;

    function setActiveItem(index) {
        const items = $('#modalSearchResults .modal-product-item');
        items.removeClass('active');

        if (items.length === 0) return;

        if (index < 0) index = 0;
        if (index >= items.length) index = items.length - 1;

        activeIndex = index;

        const activeItem = items.eq(activeIndex);
        activeItem.addClass('active');

        // 🔥 auto scroll into view
        activeItem[0].scrollIntoView({
            block: 'nearest'
        });
    }
</script>
@endsection
