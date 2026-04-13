@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row align-items-center mb-3">
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <h4 class="mb-1">Warehouse List</h4>
                    <h6 class="text-muted">Manage Warehouses</h6>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-primary" data-toggle="modal" data-target="#warehouseModal" onclick="clearWarehouse()">
                        Add Warehouse
                    </button>
                    <a href="{{ url()->previous() }}" class="btn btn-danger text-center">
                        Back
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @if (session()->has('success'))
                    <div class="alert alert-success"><strong>Success!</strong> {{ session('success') }}</div>
                    @endif

                    <table class="table datanew">
                        <thead><tr><th>#</th><th>Created By</th><th>Name</th><th>Location</th><th>Remarks</th><th>Action</th></tr></thead>
                        <tbody>
                            @foreach($warehouses as $key => $w)
                            <tr>
                                <td>{{ $key+1 }}</td><td>{{ $w->user->name }}</td><td>{{ $w->warehouse_name }}</td><td>{{ $w->location }}</td><td>{{ $w->remarks }}</td>
                                <td>
                                    {{-- <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#warehouseModal" onclick="editWarehouse('{{ $w->id }}','{{ $w->warehouse_name }}','{{ $w->location }}','{{ $w->remarks }}')">Edit</button> --}}
                                    <button class="btn btn-sm btn-primary edit-warehouse-btn"
                                        data-id="{{ $w->id }}"
                                        data-name="{{ $w->warehouse_name }}"
                                        data-location="{{ $w->location }}"
                                        data-remarks="{{ $w->remarks }}"
                                        data-toggle="modal"
                                        data-target="#warehouseModal">
                                        Edit
                                    </button>
                                    <!-- <a href="{{ url('warehouse/delete/'.$w->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a> -->
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

<div class="modal fade" id="warehouseModal">
    <div class="modal-dialog">
        <form action="{{ url('warehouse/store') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="warehouse_id">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add/Edit Warehouse</h5></div>
                <div class="modal-body">
                    <div class="mb-2"><input class="form-control" name="warehouse_name" id="warehouse_name" placeholder="Name" required></div>
                    <div class="mb-2 d-none"><input class="form-control" name="creater_id" id="" value="{{ Auth()->user()->id }}" placeholder="Name" required></div>
                    <div class="mb-2"><input class="form-control" name="location" id="location" placeholder="Location"></div>
                    <div class="mb-2"><textarea class="form-control" name="remarks" id="remarks" placeholder="Remarks"></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
            </div>
        </form>
    </div>
</div>

@endsection

    {{-- @push('scripts') --}}
    @section('scripts')
        <script>
    function clearWarehouse(){ $('#warehouse_id').val(''); $('#warehouse_name').val(''); $('#location').val(''); $('#remarks').val(''); }

        // function editWarehouse(id,name,location,remarks){
        //     alert(id);
        //     $('#warehouse_id').val(id);
        //     $('#warehouse_name').val(name);
        //     $('#location').val(location);
        //     $('#remarks').val(remarks);
        //  }

        // Handle Edit button click
        $(document).on('click', '.edit-warehouse-btn', function () {
            // alert("Edit button clicked ✅"+ $(this).data('id'));

            $('#warehouse_id').val($(this).data('id'));
            $('#warehouse_name').val($(this).data('name'));
            $('#location').val($(this).data('location'));
            $('#remarks').val($(this).data('remarks'));
        });

    
    $('.datanew').DataTable();
    </script>
    {{-- @endpush --}}

@endsection
