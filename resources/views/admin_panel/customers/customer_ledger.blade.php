@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Customer Ledger</h4>
                    <h6>View Customer Balances</h6>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <table id="default-datatable" class="table ">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Opening Balance</th>
                                <th>Previous Balance</th>
                                <th>Closing Balance</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($CustomerLedgers as $key => $ledger)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $ledger->customer->customer_name ?? 'N/A' }}</td>
                                <td>{{ number_format($ledger->opening_balance, 2) }}</td>
                                <td>{{ number_format($ledger->previous_balance, 2) }}</td>
                                <td>{{ number_format($ledger->closing_balance, 2) }}</td>
                                <td>{{ $ledger->created_at->format('d-m-Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('scripts')
 
<script>
    $('.datanew').DataTable();
</script>
<script>
     $(document).ready(function() {
         $('#default-datatable').DataTable({
             "pageLength": 10,
             "lengthMenu": [5, 10, 25, 50, 100],
             "order": [
                 [0, 'desc']
             ],
             "language": {
                 "search": "Search Category:",
                 "lengthMenu": "Show _MENU_ entries"
             }
         });
     });
 </script>

 @endsection