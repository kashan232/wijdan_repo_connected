{{-- Item Row Autocomplete + Add/Remove --}}
<!-- Make sure jQuery and Bootstrap Typeahead are included -->
@extends('admin_panel.layout.app')
<style>
    .searchResults {
        position: absolute;
        z-index: 9999;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background: #fff;
        /* border: 1px solid #ddd; */
    }

    .search-result-item.active {
        background: #007bff;
        color: white;
    }



    .table-scroll .icon-col {
        width: 51px;
        /* Ya jitni chhoti chahiye */
        min-width: 51px;
        max-width: 40px;
    }

    .table-scroll {
        max-height: none !important;
        overflow-y: visible !important;
    }

    .product-col {
        width: 20% !important;
        /* 👈 yahan control hai */
        min-width: 320px;
    }

    .product-col input {
        white-space: normal;
    }

    .product-col input {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .disabled-row input {
        background-color: #f8f9fa;
        pointer-events: none;
    }

    .purchase-table-wrapper {
        max-height: 300px;
        overflow-y: auto;
    }

    .purchase-table {
        table-layout: auto !important;
        width: 100%;
    }

    .qty-pulse {
        animation: pulseQty 1s ease-in-out;
    }

    @keyframes pulseQty {
        0% {
            background-color: #d4edda;
        }

        50% {
            background-color: #c3e6cb;
        }

        100% {
            background-color: #d4edda;
        }
    }

    /* +1 badge on quantity increment */
    .qty-indicator {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #28a745;
        color: #fff;
        font-weight: bold;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        pointer-events: none;
        z-index: 9999;
        animation: moveUp 1s ease forwards;
    }

    @keyframes moveUp {
        0% {
            opacity: 1;
            transform: translateY(0);
        }

        100% {
            opacity: 0;
            transform: translateY(-20px);
        }
    }

    /* Default nowrap */
    .purchase-table th:not(.product-col),
    .purchase-table td:not(.product-col) {
        white-space: nowrap;
    }


    .product-col {
        width: 28%;
        min-width: 280px;
    }

    .product-col input {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .purchase-table th.product-col,
    .purchase-table td.product-col {
        width: 10% !important;
        min-width: 250px !important;
        max-width: none !important;
    }

    /* product input full space le */
    .purchase-table td.product-col input {
        width: 100% !important;
        white-space: normal !important;
        /* 👈 wrap allow */
        overflow: visible !important;
        text-overflow: unset !important;
    }

    .total-unit-box {
        padding: 5px;
        border-radius: 6px;
        min-width: 80px;
    }

    .total-unit-box .unit-label {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 3px;
    }

    /* Unit */


    /* Price */

    /* ======================== */
    /* Purchase Table Columns   */
    /* ======================== */

    /* Product */
    .purchase-table th:nth-child(1),
    .purchase-table td:nth-child(1) {
        width: 28%;
        min-width: 280px;
    }

    /* Item Code - Hidden */
    .purchase-table th:nth-child(2),
    .purchase-table td:nth-child(2) {
        display: none;
        /* 👈 Item Code ko hide kar diya */
    }

    /* Brand */
    .purchase-table th:nth-child(3),
    .purchase-table td:nth-child(3) {
        width: 120px;
    }

    /* Unit */
    .purchase-table th:nth-child(4),
    .purchase-table td:nth-child(4) {
        width: 130px;
    }

    /* Price */
    .purchase-table th:nth-child(5),
    .purchase-table td:nth-child(5) {
        width: 160px;
    }

    /* Discount */
    .purchase-table th:nth-child(6),
    .purchase-table td:nth-child(6) {
        width: 300px;
    }

    .qty-pulse {
        animation: pulseQty 0.5s ease-in-out;
        /* faster */
    }

    .qty-indicator {
        animation: moveUp 0.8s ease forwards;
        /* faster fade/move */
    }

    /* Qty - Bada kar diya */
    .purchase-table th:nth-child(7),
    .purchase-table td:nth-child(7) {
        width: 150px;
        /* 👈 bari value ke liye */
    }

    /* Note */
    .purchase-table th:nth-child(8),
    .purchase-table td:nth-child(8) {
        width: 120px;
    }

    /* Total - thoda bara kar diya */
    .purchase-table th:nth-child(9),
    .purchase-table td:nth-child(9) {
        width: 160px;
        /* 👈 easily dekhnay ke liye */
    }

    /* Total */

    .purchase-table th:nth-child(10),
    .purchase-table td:nth-child(10) {
        width: 70px;
    }

    /* Action */
</style>
@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="body-wrapper">
                    <div class="bodywrapper__inner">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-nowrap overflow-auto">
                            <!-- Title on the left -->
                            <div class="flex-grow-1">
                                <h2 class="page-title m-0">Edit Purchase</h2>
                            </div>
                            <!-- Buttons on the right -->
                            <div class="d-flex gap-4 justify-content-end flex-wrap">
                                <a href="{{ route('Purchase.home') }}" class="btn btn-danger">Back </a>
                            </div>
                        </div>
                        <div class="row gy-3">
                            <div class="col-lg-12 col-md-12 mb-30">
                                <div class="card">
                                    <div class="card-body">
                                        {{-- <form action="{{ route('store-Purchase') }}" method="POST"> --}}
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
                                        <div class="alert alert-success alert-dismissible fade show"
                                            role="alert">
                                            <strong>Success!</strong> {{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                        @endif
                                        @if (session('error'))
                                        <div class="alert alert-danger alert-dismissible fade show"
                                            role="alert">
                                            <strong>Error!</strong> {{ session('error') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                        @endif

                                        <form action="{{ route('purchase.update', $purchase->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="row mb-3 g-3 mt-4">

                                                {{-- ================= ROW 1 ================= --}}
                                                <div class="col-xl-12">
                                                    <div class="row g-3">

                                                        <div class="col-xl-4 col-sm-6">
                                                            <label>
                                                                <i class="bi bi-calendar-date text-primary me-1"></i>
                                                                Current Date
                                                            </label>
                                                            <input name="purchase_date"
                                                                value="{{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : date('Y-m-d') }}"
                                                                type="date"
                                                                class="form-control">
                                                        </div>

                                                        <div class="col-xl-4 col-sm-6">
                                                            <label>
                                                                <i class="bi bi-receipt text-primary me-1"></i>
                                                                Companies / Vendors
                                                            </label>
                                                            <select name="vendor_id" class="form-control select2">
                                                                <option disabled selected>Select One</option>
                                                                @foreach ($Vendor as $item)
                                                                <option value="{{ $item->id }}" {{ $purchase->vendor_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-xl-4 col-sm-6">
                                                            <label>
                                                                <i class="bi bi-file-earmark-text text-primary me-1"></i>
                                                                Company Inv #
                                                            </label>
                                                            <input name="invoice_no" type="text" class="form-control" value="{{ $purchase->invoice_no }}">
                                                        </div>

                                                    </div>
                                                </div>

                                                {{-- ================= ROW 2 ================= --}}
                                                <div class="col-xl-12 mt-3">
                                                    <div class="row g-3 align-items-end">

                                                        <div class="col-xl-4 col-sm-6">
                                                            <label class="d-block mb-2">
                                                                <i class="bi bi-arrow-left-right text-primary me-1"></i>
                                                                Purchase Type
                                                            </label>

                                                            <div class="form-check">
                                                                <input class="form-check-input purchaseType"
                                                                    type="radio"
                                                                    name="purchase_to"
                                                                    value="warehouse"
                                                                    id="purchaseWarehouse" {{ $purchase->purchase_to == 'warehouse' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="purchaseWarehouse">
                                                                    Warehouse
                                                                </label>
                                                            </div>

                                                            <div class="form-check mt-2">
                                                                <input class="form-check-input purchaseType"
                                                                    type="radio"
                                                                    name="purchase_to"
                                                                    value="shop"
                                                                    id="purchaseShop" {{ $purchase->purchase_to == 'shop' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="purchaseShop">
                                                                    Shop
                                                                </label>
                                                            </div>
                                                        </div>

                                                        {{-- TO WAREHOUSE --}}
                                                        <div class="col-xl-4 col-sm-6 {{ $purchase->purchase_to == 'warehouse' ? '' : 'd-none' }}" id="purchaseWarehouseBox">
                                                            <label>
                                                                <i class="bi bi-building text-primary me-1"></i>
                                                                To Warehouse
                                                            </label>
                                                            <select name="warehouse_id" class="form-control">
                                                                <option disabled selected>Select Warehouse</option>
                                                                @foreach ($Warehouse as $item)
                                                                <option value="{{ $item->id }}" {{ $purchase->warehouse_id == $item->id ? 'selected' : '' }}>{{ $item->warehouse_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                    </div>
                                                </div>

                                                {{-- ================= ROW 3 ================= --}}
                                                <div class="col-xl-12 mt-3">
                                                    <div class="row g-3">

                                                        <div class="col-xl-6 col-sm-6">
                                                            <label>
                                                                <i class="bi bi-card-text text-primary me-1"></i>
                                                                Note
                                                            </label>
                                                            <input name="note" type="text" class="form-control" value="{{ $purchase->note }}">
                                                        </div>

                                                        <div class="col-xl-6 col-sm-6">
                                                            <label>
                                                                <i class="bi bi-truck text-primary me-1"></i>
                                                                Transport Name
                                                            </label>
                                                            <input name="job_description" type="text" class="form-control" value="{{ $purchase->job_description }}">
                                                        </div>

                                                    </div>
                                                </div>

                                            </div>




                                            <!-- Item Code Table -->
                                            <button class="btn btn-primary btn-sm mt-2 mb-2" id="openProductModal">
                                                Search Product (F2)
                                            </button>

                                            <div class="modal fade" id="productModal" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">

                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Search Product</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

                                            <div class="table-responsive purchase-table-wrapper">
                                                <table class="table table-bordered purchase-table">
                                                    <thead>
                                                        <tr class="text-center">
                                                            <th class="product-col text-start">Product</th>
                                                            <th>Item Code</th>
                                                            <th>Brand</th>
                                                            <th>Unit</th>
                                                            <th>Price</th>
                                                            <th>Discount (per pc)</th>
                                                            <th>Qty</th>
                                                            <th width="200">Note</th> {{-- ✅ NEW --}}
                                                            <th>Total</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody id="purchaseItems">
                                                        @foreach($purchase->items as $item)
                                                        <tr>
                                                            <td class="product-col">
                                                                <input type="hidden" name="product_id[]"
                                                                    class="product_id" value="{{ $item->product_id }}">
                                                                <input type="text"
                                                                    class="form-control productSearch"
                                                                    placeholder="Select product..."
                                                                    value="{{ $item->product->item_name ?? '' }}"
                                                                    readonly>
                                                            </td>

                                                            <td class="item_code border">
                                                                <input type="text" name="item_code[]"
                                                                    class="form-control" value="{{ $item->product->item_code ?? '' }}" readonly>
                                                            </td>

                                                            <td class="uom border">
                                                                <input type="text" name="uom[]"
                                                                    class="form-control" value="{{ $item->product->brand->name ?? '' }}" readonly>
                                                            </td>

                                                            <td class="unit border">
                                                                <input type="text" name="unit[]"
                                                                    class="form-control" value="{{ $item->unit }}" readonly>
                                                            </td>

                                                            <!-- Price (Editable if needed) -->
                                                            <td>
                                                                <input type="number" step="0.01"
                                                                    name="price[]" class="form-control price"
                                                                    value="{{ $item->price }}">
                                                            </td>

                                                            <!-- Per-item Discount (PKR, editable) -->
                                                            <td>
                                                                <div class="d-flex gap-1 align-items-center" style="min-width:140px;">
                                                                    <!-- PKR discount -->
                                                                    <input type="number" step="0.01" name="item_disc[]" class="form-control item_disc" value="{{ $item->item_discount }}" placeholder="PKR" style="width:60%;">
                                                                    <!-- Percent discount -->
                                                                    <div style="width:40%; display:flex; align-items:center;">
                                                                        <input type="number" step="0.01" name="item_disc_pct[]" class="form-control item_disc_pct" value="" placeholder="%" style="width:70%;">
                                                                        <span style="width:30%; text-align:center;">%</span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="qty">
                                                                <input type="number" name="qty[]" class="form-control quantity" min="1" value="{{ $item->qty }}">
                                                            </td>

                                                            <td>
                                                                <input type="text"
                                                                    name="item_note[]"
                                                                    class="form-control"
                                                                    value="{{ $item->note }}"
                                                                    placeholder="Optional note">
                                                            </td>

                                                            <td class="total border">
                                                                <input type="text" name="total[]" class="form-control row-total" value="{{ $item->line_total }}" readonly>
                                                            </td>

                                                            <td>
                                                                <button type="button"
                                                                    class="btn btn-sm btn-danger remove-row">X</button>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>


                                                </table>
                                            </div>
                                            <div class="row g-2 mt-3">
                                                <div class="col-md-12">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <!-- Yard -->
                                                        <div class="total-unit-box text-center text-white bg-primary">
                                                            <div class="unit-label">Yard</div>
                                                            <div><input type="text" id="totalYard" class="form-control form-control-sm text-center" readonly></div>
                                                        </div>

                                                        <!-- Meter -->
                                                        <div class="total-unit-box text-center text-white bg-success">
                                                            <div class="unit-label">Meter</div>
                                                            <div><input type="text" id="totalMeter" class="form-control form-control-sm text-center" readonly></div>
                                                        </div>

                                                        <!-- Piece -->
                                                        <div class="total-unit-box text-center text-white bg-warning">
                                                            <div class="unit-label">Piece</div>
                                                            <div><input type="text" id="totalPiece" class="form-control form-control-sm text-center" readonly></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3 mt-3">
                                                <div class="col-md-3">
                                                    <label>Subtotal</label>
                                                    <input type="text" id="subtotal" class="form-control"
                                                        value="{{ $purchase->subtotal }}" name="subtotal" readonly>
                                                </div>

                                                <div class="col-md-3">
                                                    <label>Discount (Overall)</label>
                                                    <input type="number" step="0.01" id="overallDiscount"
                                                        class="form-control" name="discount" value="{{ $purchase->discount }}">
                                                </div>

                                                <div class="col-md-3">
                                                    <label>Extra Cost</label>
                                                    <input type="number" step="0.01" id="extraCost"
                                                        class="form-control" name="extra_cost" value="{{ $purchase->extra_cost }}">
                                                </div>

                                                <div class="col-md-3">
                                                    <label>Net Amount</label>
                                                    <input type="text" id="netAmount" name="net_amount"
                                                        class="form-control fw-bold" value="{{ $purchase->net_amount }}" readonly>
                                                </div>
                                            </div>
                                            <button type="button" id="submitBtn" class="btn btn-primary mt-3 mb-3">Update Purchase</button>
                                        </form>
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
    @section('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector("form[action='{{ route('purchase.update', $purchase->id) }}']");
            const submitBtn = document.getElementById("submitBtn");

            // Enter key se form submit disable
            form.addEventListener("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                }
            });

            // Sirf button click pe submit
            submitBtn.addEventListener("click", function() {
                form.submit();
            });
        });
    </script>

    {{-- Success & Error Messages --}}
    @if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: @json(session('success')),
            confirmButtonColor: '#3085d6',
        });
    </script>
    @endif


    @if (session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: @json(session('error')),
            confirmButtonColor: '#d33',
        });
    </script>
    @endif


    @if ($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            html: `{!! implode('<br>', $errors->all()) !!}`,
            confirmButtonColor: '#d33',
        });
    </script>
    @endif

    {{-- Cancel Button Confirmation --}}
    <script>
        // Prevent Enter key from submitting form in product search
        $(document).on('keydown', '.productSearch', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();

                // sirf tab modal kholo jab product empty ho
                if (!$(this).closest('tr').find('.product_id').val()) {
                    openProductModal();
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const cancelBtn = document.getElementById('cancelBtn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'This will cancel your changes!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, go back!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '';
                        }
                    });
                });
            }
        });
    </script>

    {{-- Item Row Autocomplete + Add/Remove --}}
    <!-- Make sure jQuery and Bootstrap Typeahead are included -->

    <script>
        let scanLock = false;
        let lastBarcode = '';
        let lastScanTime = 0;

        function appendBlankRow() {
            const newRow = `
<tr>
     <td class="product-col">
        <input type="hidden" name="product_id[]" class="product_id">
        <input type="text"
               class="form-control productSearch"
               placeholder="Select product..."
               readonly>
    </td>

    <td class="item_code border"><input type="text" name="item_code[]" class="form-control" readonly></td>
    <td class="uom border"><input type="text" name="uom[]" class="form-control" readonly></td>
    <td class="unit border"><input type="text" name="unit[]" class="form-control" readonly></td>

    <td><input type="number" step="0.01" name="price[]" class="form-control price"></td>

    <td>
        <div class="d-flex gap-1 align-items-center" style="min-width:140px;">
            <input type="number" step="0.01" name="item_disc[]" class="form-control item_disc" placeholder="PKR" style="width:60%;">
            <div style="width:40%; display:flex; align-items:center;">
                <input type="number" step="0.01" name="item_disc_pct[]" class="form-control item_disc_pct" placeholder="%" style="width:70%;">
                <span style="width:30%; text-align:center;">%</span>
            </div>
        </div>
    </td>

    <td class="qty"><input type="number" name="qty[]" class="form-control quantity" min="1"></td>

    {{-- ✅ NOTE --}}
    <td>
        <input type="text" name="item_note[]" class="form-control" placeholder="Optional note">
    </td>

    <td class="total border"><input type="text" name="total[]" class="form-control row-total" readonly></td>

    <td>
        <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
    </td>
</tr>`;
            $('#purchaseItems').append(newRow);

            // 🔥 ensure focus
            setTimeout(() => {
                $('#purchaseItems tr:last .productSearch').focus();
            }, 50);
        }

        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        $(document).on('keydown', function(e) {
            if (e.key === 'F2') {
                e.preventDefault(); 
                openProductModal();
                return;
            }

            // ignore input fields, textarea, select
            const tag = e.target.tagName.toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
        });


        function recalcRow($row) {
            const qty = num($row.find('.quantity').val());
            const price = num($row.find('.price').val());
            const discPKR = num($row.find('.item_disc').val()); // per-piece PKR
            // total = qty * (price - per-piece-discount)
            let total = (qty * price) - (qty * discPKR);
            if (total < 0) total = 0;
            $row.find('.row-total').val(total.toFixed(2));
        }

        function recalcSummary() {
            let sub = 0;
            let totalYard = 0;
            let totalMeter = 0;
            let totalPiece = 0;

            $('#purchaseItems tr').each(function() {
                const $row = $(this);
                const rowTotal = num($row.find('.row-total').val());
                sub += rowTotal;

                const qty = num($row.find('.quantity').val());
                const unit = $row.find('.unit input').val().toLowerCase(); // unit column

                if (unit.includes('yard')) totalYard += qty;
                else if (unit.includes('meter')) totalMeter += qty;
                else if (unit.includes('piece')) totalPiece += qty;
            });

            $('#subtotal').val(sub.toFixed(2));
            $('#totalYard').val(totalYard);
            $('#totalMeter').val(totalMeter);
            $('#totalPiece').val(totalPiece);

            const oDisc = num($('#overallDiscount').val());
            const xCost = num($('#extraCost').val());
            const net = (sub - oDisc + xCost);
            $('#netAmount').val(net.toFixed(2));
        }



        $(document).ready(function() {

            $('.purchaseType').on('change', function() {

                let type = $(this).val();

                // hide first
                $('#purchaseWarehouseBox').addClass('d-none');

                // clear warehouse
                $('select[name="warehouse_id"]').val('');

                if (type === 'warehouse') {
                    $('#purchaseWarehouseBox').removeClass('d-none');
                }
            });

            // ---------- Helpers ----------


            function pctFromPKR(pkr, price) {
                if (!price || price === 0) return 0;
                return (pkr / price) * 100;
            }

            function pkrFromPct(pct, price) {
                if (!price || price === 0) return 0;
                return (price * pct) / 100;
            }

            // recalcRow expects: .price, .quantity, .item_disc (PKR per piece)



            $(document).on('input', '.item_disc', function() {
                const $pkr = $(this);
                const $row = $pkr.closest('tr');

                // prevent recursion if pct handler is already syncing
                if ($row.data('syncing')) return;

                const price = num($row.find('.price').val());
                const pkrVal = num($pkr.val());

                // calculate percent and update percent input
                const pct = pctFromPKR(pkrVal, price);
                $row.data('syncing', true); // lock
                $row.find('.item_disc_pct').val(pct ? pct.toFixed(2) : '');
                $row.data('syncing', false); // unlock

                recalcRow($row);
                recalcSummary();
            });
            $(document).on('input', '.item_disc_pct', function() {
                const $pct = $(this);
                const $row = $pct.closest('tr');

                if ($row.data('syncing')) return;

                const price = num($row.find('.price').val());
                const pctVal = num($pct.val());

                const pkr = pkrFromPct(pctVal, price);
                $row.data('syncing', true);
                // write PKR value (rounded)
                $row.find('.item_disc').val(pkr ? pkr.toFixed(2) : '');
                $row.data('syncing', false);

                recalcRow($row);
                recalcSummary();
            });

            $('#purchaseItems').on('input', '.price', function() {
                const $price = $(this);
                const $row = $price.closest('tr');

                // compute using current PKR value:
                const priceVal = num($price.val());
                const pkrVal = num($row.find('.item_disc').val());

                // update percent from PKR
                const pct = pctFromPKR(pkrVal, priceVal);
                $row.data('syncing', true);
                $row.find('.item_disc_pct').val(pct ? pct.toFixed(2) : '');
                $row.data('syncing', false);

                // recalc totals
                recalcRow($row);
                recalcSummary();
            });






            // ---------- Product Search (AJAX) ----------

            // Click/Enter on suggestion
            $(document).on('click', '.search-result-item', function() {
                const $li = $(this);
                const $row = $li.closest('tr');

                $row.find('.productSearch').val($li.data('product-name'));
                $row.find('.item_code input').val($li.data('product-code'));
                $row.find('.uom input').val($li.data('product-uom'));
                $row.find('.unit input').val($li.data('product-unit'));
                $row.find('.price').val($li.data('price'));

                $row.find('.product_id').val($li.data('product-id'));

                // reset qty & discount for fresh calc
                $row.find('.quantity').val(1);
                $row.find('.item_disc').val(0);

                recalcRow($row);
                recalcSummary();

                // clear search results
                $row.find('.searchResults').empty();

                // ✅ no auto append here
                // move focus to quantity input
                $row.find('.quantity').focus();
            });


            $(document).on('keydown', '.quantity', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();

                    const $row = $(this).closest('tr');
                    const productSelected = $row.find('.product_id').val();

                    // only append if product is actually selected
                    if (productSelected) {
                        recalcRow($row);
                        recalcSummary();
                        appendBlankRow();
                        $('#purchaseItems tr:last .productSearch').focus();
                    }
                }
            });

            // Also allow keyboard Enter selection when list focused
            $(document).on('keydown', '.searchResults .search-result-item', function(e) {
                if (e.key === 'Enter') {
                    $(this).trigger('click');
                }
            });

            // Row calculations
            $('#purchaseItems').on('input', '.quantity, .price, .item_disc', function() {
                const $row = $(this).closest('tr');
                recalcRow($row);
                recalcSummary();
            });

            // Remove row
            $('#purchaseItems').on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                recalcSummary();
            });

            // Summary inputs
            $('#overallDiscount, #extraCost').on('input', function() {
                recalcSummary();
            });

            // init first row values
            recalcRow($('#purchaseItems tr:first'));
            recalcSummary();
        });

        let scanBuffer = '';
        let scanTimer = null;

        $(document).on('keydown', function(e) {

            if (scanLock) return;

            if ($('#productModal').hasClass('show')) return;

            if (e.key === 'Enter') {
                e.preventDefault();

                if (scanBuffer.length >= 5) {
                    handleSalesBarcode(scanBuffer.trim());
                }

                scanBuffer = '';
                clearTimeout(scanTimer);
                return;
            }

            if (e.key.length === 1) {
                scanBuffer += e.key;
            }

            clearTimeout(scanTimer);
            scanTimer = setTimeout(() => {
                scanBuffer = '';
            }, 200);
        });

        function handleSalesBarcode(barcode) {

            const now = Date.now();

            // 🔒 Prevent duplicate scan
            if (scanLock || (barcode === lastBarcode && (now - lastScanTime) < 500)) {
                console.warn('Duplicate scan blocked:', barcode);
                return;
            }

            scanLock = true;
            lastBarcode = barcode;
            lastScanTime = now;

            $.get("{{ route('search-product-by-barcode') }}", {
                barcode
            }, function(res) {

                scanLock = false;

                if (!res || !res.id) {
                    console.warn('Barcode not found:', barcode);
                    return;
                }

                let foundRow = null;

                // Check if product already exists
                $('#purchaseItems tr').each(function() {
                    const pid = $(this).find('.product_id').val();
                    if (pid && Number(pid) === Number(res.id)) {
                        foundRow = $(this);
                        return false;
                    }
                });

                if (foundRow) {
                    const $qty = foundRow.find('.quantity');
                    let currentQty = parseInt($qty.val()) || 0;
                    currentQty += 1;
                    $qty.val(currentQty);

                    recalcRow(foundRow);
                    recalcSummary();

                    // Trigger animation by reflow trick
                    $qty.removeClass('qty-pulse');
                    void $qty[0].offsetWidth; // force reflow
                    $qty.addClass('qty-pulse');

                    // +1 badge
                    const badge = $('<span class="qty-indicator">+1</span>');
                    const $td = $qty.closest('td');
                    $td.css('position', 'relative').append(badge);

                    setTimeout(() => {
                        badge.remove();
                    }, 800); // slightly faster

                    // highlight row briefly
                    foundRow.addClass('table-success');
                    setTimeout(() => foundRow.removeClass('table-success'), 500);

                    foundRow[0].scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return;
                }
                // Add new row for new product
                appendBlankRow();
                const row = $('#purchaseItems tr:last');

                row.find('.product_id').val(res.id);
                row.find('.productSearch').val(res.name).prop('readonly', true);
                row.find('[name="item_code[]"]').val(res.code);
                row.find('[name="uom[]"]').val(res.brand ?? '');
                row.find('[name="unit[]"]').val(res.unit);
                row.find('.price').val(res.wholesale_price ?? 0);
                row.find('.quantity').val(1);
                row.find('.item_disc').val(0);

                recalcRow(row);
                recalcSummary();

                row.addClass('table-info');
                setTimeout(() => row.removeClass('table-info'), 300);
            });
        }




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
            // Reset search input & results
            $('#modalProductSearch').val('');
            $('#modalSearchResults').empty();

            // Show modal
            $('#productModal').modal('show');

            // Focus input after modal fully shown
            setTimeout(() => {
                const $input = $('#modalProductSearch');
                $input.focus();
                activeIndex = 0;
                setActiveItem(activeIndex); // first item active
            }, 300); // Bootstrap modal animation ke liye
        }

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
    data-price="${p.wholesale_price}"
    data-unit="${p.unit_id}"
    data-brand="${p.brand ?? ''}"
    data-note="${noteText}">
    <strong>${p.item_name}</strong>
    <br>
    <small>Rs: ${p.wholesale_price} | ${p.brand ?? '-'}</small>
    <br>
    <strong class="text-Dark">Note: ${noteText}</strong>

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
            const product = {
                id: $(this).data('id'),
                name: $(this).data('name'),
                code: $(this).data('code'),
                unit: $(this).data('unit'),
                brand: $(this).data('brand'),
                price: $(this).data('price'),
                note: $(this).data('note')
            };

            // ❌ Duplicate check remove kar diya user ki request par (F2 search ke liye always new row)
            // let foundRow = null; ...

            // Always add new row logic:
            let lastRow = $('#purchaseItems tr:last');
            if (lastRow.find('.product_id').val()) {
                appendBlankRow();
                lastRow = $('#purchaseItems tr:last');
            }

            lastRow.find('.product_id').val(product.id);
            lastRow.find('.productSearch').val(product.name).prop('readonly', true);
            lastRow.find('[name="item_code[]"]').val(product.code);
            lastRow.find('[name="item_note[]"]').val(product.note); // ✅ Note bhi populate karein
            lastRow.find('[name="uom[]"]').val(product.brand);
            lastRow.find('[name="unit[]"]').val(product.unit);
            lastRow.find('.price').val(product.price);
            lastRow.find('.quantity').val(1);
            lastRow.find('.item_disc').val(0);

            recalcRow(lastRow);
            recalcSummary();

            $('#productModal').modal('hide');
            $('#modalProductSearch').val('');
            $('#modalSearchResults').empty();
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

        <script>
        $(document).ready(function() {
            // Initialize Select2 for all elements with class .select2
            $('.select2').select2({
                width: '100%', // Ensure it takes full width of the container
                placeholder: "Select an option",
                allowClear: true
            });
        });
    </script>
@endsection