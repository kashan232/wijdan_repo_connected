@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Table aur container ka overflow visible rakho */

    /* --- Search result dropdown --- */

    .searchResults {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        max-height: 200px;
        /* overflow-y: auto; */
        background: #fff;
        border: 1px solid #ccc;
        z-index: 999999 !important;
    }

    /* --- Table ke andar overflow na cut ho --- */
    .table-responsive,
    .table,
    .table-bordered,
    #gatepassItems {
        overflow: visible !important;
        position: relative !important;
    }

    /* --- Compact remove button --- */
    .remove-row {
        min-height: 30px;
        min-width: 30px;
        padding: 2px 6px;
        font-size: 14px;
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
        color: #374151;
    }

    label {
        font-weight: 500;
        margin-bottom: 4px;
    }

    .form-control,
    .form-select {
        height: 44px;
        font-size: 14px;
    }

    .radio-box {
        border: 1px dashed #d1d5db;
        border-radius: 8px;
        padding: 12px 15px;
        background: #fff;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="page-title">Create Inward Gatepass</h5>
                    <a href="{{ route('InwardGatepass.home') }}" class="btn btn-danger">Back</a>
                </div>

                <div class="col-lg-12 col-md-12 mb-30">
                    <div class="card">
                        <div class="card-body">

                            @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                            @endif

                            <form action="{{ route('store.InwardGatepass') }}" method="POST" id="gatepassForm">
                                @csrf

                                <!-- ================= BASIC INFO ================= -->
                                <div class="form-section">
                                    <h6>📄 Gatepass Information</h6>

                                    <div class="row g-4">

                                        <div class="col-md-4">
                                            <label>Date</label>
                                            <input type="date" name="gatepass_date" class="form-control"
                                                value="{{ old('gatepass_date', date('Y-m-d')) }}">
                                        </div>

                                        <div class="col-md-4">
                                            <label>Branch</label>
                                            <select name="branch_id" class="form-control select2">
                                                <option value="">Select Branch</option>
                                                @foreach ($branches as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label>Vendor</label>
                                            <select name="vendor_id" class="form-control select2">
                                                <option value="">Select Vendor</option>
                                                @foreach ($vendors as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                    </div>
                                </div>

                                <!-- ================= RECEIVE LOCATION ================= -->
                                <div class="form-section">
                                    <h6>📦 Receive Location</h6>

                                    <div class="row g-4 align-items-end">

                                        <div class="col-md-4">
                                            <div class="radio-box">
                                                <label class="d-block mb-2">Receive In</label>

                                                <div class="form-check">
                                                    <input class="form-check-input receiveType" type="radio"
                                                        name="receive_type" value="warehouse" id="receiveWarehouse">
                                                    <label class="form-check-label" for="receiveWarehouse">
                                                        Warehouse
                                                    </label>
                                                </div>

                                                <div class="form-check mt-2">
                                                    <input class="form-check-input receiveType" type="radio"
                                                        name="receive_type" value="shop" id="receiveShop">
                                                    <label class="form-check-label" for="receiveShop">
                                                        Shop
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- WAREHOUSE -->
                                        <div class="col-md-4 d-none" id="warehouseBox">
                                            <label>Warehouse</label>
                                            <select name="warehouse_id" class="form-control select2">
                                                <option value="">Select Warehouse</option>
                                                @foreach ($warehouses as $item)
                                                <option value="{{ $item->id }}">{{ $item->warehouse_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- SHOP -->
                                        <div class="col-md-4 d-none" id="shopBox">
                                            <label>Shop Name</label>
                                            <input type="text" name="shop_name" class="form-control"
                                                placeholder="Enter shop name">
                                        </div>

                                    </div>
                                </div>

                                <!-- ================= TRANSPORT INFO ================= -->
                                <div class="form-section">
                                    <h6>🚚 Transport Details</h6>

                                    <div class="row g-4">

                                        <div class="col-md-4">
                                            <label>Transport Name</label>
                                            <input type="text" name="transport_name" class="form-control"
                                                placeholder="e.g. ABC Transport">
                                        </div>

                                        <div class="col-md-4">
                                            <label>Bilty No</label>
                                            <input type="text" name="bilty_no" class="form-control"
                                                placeholder="Bilty / GR Number">
                                        </div>

                                        <div class="col-md-4">
                                            <label>Remarks</label>
                                            <input type="text" name="note" class="form-control"
                                                placeholder="Optional note">
                                        </div>

                                    </div>
                                </div>

                                <!-- ================= ACTION ================= -->
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-5 py-2">
                                        💾 Save Temporary Inward Gatepass
                                    </button>
                                </div>

                            </form>


                            <!-- Product Table -->
                            <!-- Items Table -->
                            <!-- <div style="max-height: 400px;  position: relative; overflow-x: visible !important;">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr class="text-center">
                                                <th>Product</th>
                                                <th>Item Code</th>
                                                <th>Brand</th>
                                                <th>Unit</th>
                                                <th>Qty</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="gatepassItems">
                                            <tr>
                                                <td style="position: relative;">
                                                    <input type="hidden" name="product_id[]" class="product_id">
                                                    <input type="text" class="form-control productSearch"
                                                        placeholder="Enter product name..." autocomplete="off">
                                                    <ul class="searchResults list-group"></ul>
                                                </td>
                                                <td><input type="text" name="item_code[]" class="form-control"
                                                        readonly></td>
                                                <td><input type="text" name="brand[]" class="form-control" readonly>
                                                </td>
                                                <td><input type="text" name="unit[]" class="form-control" readonly>
                                                </td>
                                                <td><input type="number" name="qty[]" class="form-control quantity"
                                                        min="1" value="1"></td>
                                                <td class="text-end">
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger remove-row">X</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div> -->

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

        $('.select2').select2({
            width: '100%'
        });

        $('.receiveType').on('change', function() {
            let type = $(this).val();

            if (type === 'warehouse') {
                $('#warehouseBox').removeClass('d-none');
                $('#shopBox').addClass('d-none');
                $('input[name="shop_name"]').val('');
            }

            if (type === 'shop') {
                $('#shopBox').removeClass('d-none');
                $('#warehouseBox').addClass('d-none');
                $('select[name="warehouse_id"]').val('').trigger('change');
            }
        });

    });
</script>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Select One',
            allowClear: true
        });

        // naya row add
        function appendBlankRow() {
            const row = `
        <tr>
            <td style="position: relative;">
                <input type="hidden" name="product_id[]" class="product_id">
                <input type="text" class="form-control productSearch" placeholder="Enter product name..." autocomplete="off">
                <ul class="searchResults list-group"></ul>
            </td>
            <td><input type="text" name="item_code[]" class="form-control" readonly></td>
            <td><input type="text" name="brand[]" class="form-control" readonly></td>
            <td><input type="text" name="unit[]" class="form-control" readonly></td>
            <td><input type="number" name="qty[]" class="form-control quantity" min="1" value="1"></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
        </tr>`;
            $('#gatepassItems').append(row);
        }


        $(document).on('keydown', '.quantity', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const $currentRow = $(this).closest('tr');
                const pid = $currentRow.find('.product_id').val();

                if (pid) {
                    appendBlankRow();
                    $('#gatepassItems tr:last .productSearch').focus();
                }
            }
        });

        // search
        $(document).on('keyup', '.productSearch', function() {
            const $input = $(this);
            const q = $input.val().trim();
            const $row = $input.closest('tr');
            const $box = $row.find('.searchResults');

            if (q.length === 0) {
                $box.empty();
                return;
            }

            $.ajax({
                url: "{{ route('search-products') }}",
                type: "GET",
                data: {
                    q
                },
                success: function(data) {
                    let html = '';
                    (data || []).forEach(p => {
                        const brand = p.brand && p.brand.name ? p.brand.name : '';
                        const unit = p.unit_id ?? '';
                        const code = p.item_code ?? '';
                        const name = p.item_name ?? '';
                        const id = p.id ?? '';
                        html += `
                    <li class="list-group-item search-result-item"
                        style="cursor:pointer;"
                        data-product-id="${id}"
                        data-product-name="${escapeHtml(name)}"
                        data-code="${escapeHtml(code)}"
                        data-brand="${escapeHtml(brand)}"
                        data-unit="${escapeHtml(unit)}">
                        ${escapeHtml(name)} (${escapeHtml(code)}) - ${escapeHtml(brand)}
                    </li>`;
                    });
                    $box.html(html).show();
                },
                error: function() {
                    $box.empty();
                }
            });
        });

        // safe HTML
        function escapeHtml(text) {
            return String(text || '').replace(/[&<>"'`=\/]/g, function(s) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                    '/': '&#47;',
                    '`': '&#96;',
                    '=': '&#61;'
                } [s];
            });
        }

        // select product
        $(document).on('click', '.search-result-item', function() {
            const $li = $(this);
            const $row = $li.closest('tr');

            // fill product data
            $row.find('.product_id').val($li.data('product-id'));
            $row.find('.productSearch').val($li.data('product-name'));
            $row.find('input[name="item_code[]"]').val($li.data('code'));
            $row.find('input[name="brand[]"]').val($li.data('brand'));
            $row.find('input[name="unit[]"]').val($li.data('unit'));
            $row.find('.searchResults').empty();

            // 👇 new behavior: abhi naya row nahi add hoga, 
            // user qty me Enter press karega tab appendBlankRow() chalega
            $row.find('.quantity').focus();
        });

        // remove row
        $(document).on('click', '.remove-row', function() {
            if ($('#gatepassItems tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });

        // form submit check
        $('#gatepassForm').on('submit', function(e) {
            $('#gatepassItems tr').each(function() {
                const pid = $(this).find('.product_id').val();
                if (!pid) $(this).remove();
            });
            if ($('input[name="product_id[]"]').filter(function() {
                    return $(this).val() != '';
                }).length === 0) {
                e.preventDefault();
                appendBlankRow();

                Swal.fire('Error', 'Please add at least one product for the gatepass', 'error');
                return false;
            }
        });

        // prevent Enter submit
        $('#gatepassForm').on('keypress', function(e) {
            if (e.key === 'Enter' && e.target.type !== 'textarea') {
                e.preventDefault();
                return false;
            }
        });

    });
</script>
@endsection