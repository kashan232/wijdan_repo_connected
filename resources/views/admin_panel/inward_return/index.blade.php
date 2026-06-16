@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Inward Returns</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Return Invoice</th>
                                    <th>Gatepass Ref</th>
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th>Received In</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inwardReturns as $key => $return)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $return->return_invoice }}</td>
                                    <td>{{ $return->inwardGatepass ? $return->inwardGatepass->invoice_no : 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($return->return_date)->format('d-m-Y') }}</td>
                                    <td>{{ $return->vendor ? $return->vendor->name : 'N/A' }}</td>
                                    <td class="text-capitalize">{{ $return->inwardGatepass ? $return->inwardGatepass->receive_type : 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('inward-returns.show', $return->id) }}" class="btn btn-sm btn-info text-white">View</a>
                                            <form action="{{ route('inward-returns.destroy', $return->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this return? Stock will be reverted.')">Delete</button>
                                            </form>
                                        </div>
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

@endsection

@section('scripts')
<!--datatable js-->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

<script>
    $(document).ready(function() {
        $('#example').DataTable({
            order: [[0, 'desc']]
        });
    });
</script>
@endsection
