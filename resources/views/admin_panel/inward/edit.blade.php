@extends('admin_panel.layout.app')

@section('content')
<style>
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
    }

    .radio-box {
        border: 1px dashed #d1d5db;
        border-radius: 8px;
        padding: 12px 15px;
        background: #fff;
    }
</style>

<div class="main-content">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="page-title">✏️ Edit Inward Gatepass</h5>
            <a href="{{ route('InwardGatepass.home') }}" class="btn btn-danger">Back</a>
        </div>

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

                <form action="{{ route('InwardGatepass.update', $gatepass->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- ================= BASIC INFO ================= -->
                    <div class="form-section">
                        <h6>📄 Gatepass Information</h6>

                        <div class="row g-4">

                            <div class="col-md-4">
                                <label>Date</label>
                                <input type="date"
                                       name="gatepass_date"
                                       class="form-control"
                                       value="{{ old('gatepass_date', $gatepass->gatepass_date) }}" readonly>
                            </div>

                            <div class="col-md-4">
                                <label>Branch</label>
                                <select name="branch_id" class="form-control select2" readonly>
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->id }}"
                                            {{ $gatepass->branch_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label>Vendor</label>
                                <select name="vendor_id" class="form-control select2" readonly>
                                    @foreach ($vendors as $item)
                                        <option value="{{ $item->id }}"
                                            {{ $gatepass->vendor_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
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
                                        <input class="form-check-input receiveType"
                                               type="radio"
                                               name="receive_type"
                                               value="warehouse"
                                               {{ $gatepass->receive_type === 'warehouse' ? 'checked' : '' }}>
                                        <label class="form-check-label">Warehouse</label>
                                    </div>

                                    <div class="form-check mt-2">
                                        <input class="form-check-input receiveType"
                                               type="radio"
                                               name="receive_type"
                                               value="shop"
                                               {{ $gatepass->receive_type === 'shop' ? 'checked' : '' }}>
                                        <label class="form-check-label">Shop</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Warehouse -->
                            <div class="col-md-4 {{ $gatepass->receive_type === 'warehouse' ? '' : 'd-none' }}"
                                 id="warehouseBox">
                                <label>Warehouse</label>
                                <select name="warehouse_id" class="form-control select2">
                                    @foreach ($warehouses as $item)
                                        <option value="{{ $item->id }}"
                                            {{ $gatepass->warehouse_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->warehouse_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Shop -->
                            <div class="col-md-4 {{ $gatepass->receive_type === 'shop' ? '' : 'd-none' }}"
                                 id="shopBox">
                                <label>Shop Name</label>
                                <input type="text"
                                       name="shop_name"
                                       class="form-control"
                                       value="{{ old('shop_name', $gatepass->shop_name) }}">
                            </div>

                        </div>
                    </div>

                    <!-- ================= TRANSPORT ================= -->
                    <div class="form-section">
                        <h6>🚚 Transport Details</h6>

                        <div class="row g-4">

                            <div class="col-md-4">
                                <label>Transport Name</label>
                                <input type="text"
                                       name="transport_name"
                                       class="form-control"
                                       value="{{ old('transport_name', $gatepass->transport_name) }}">
                            </div>

                            <div class="col-md-4">
                                <label>Bilty No</label>
                                <input type="text"
                                       name="gatepass_no"
                                       class="form-control"
                                       value="{{ old('gatepass_no', $gatepass->gatepass_no) }}">
                            </div>

                            <div class="col-md-4">
                                <label>Note</label>
                                <input type="text"
                                       name="remarks"
                                       class="form-control"
                                       value="{{ old('remarks', $gatepass->remarks) }}">
                            </div>

                        </div>
                    </div>

                    <!-- ================= ACTION ================= -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-success px-5 py-2">
                            💾 Update Inward Gatepass
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
$(document).ready(function () {

    $('.select2').select2({ width: '100%' });

    $('.receiveType').on('change', function () {
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
