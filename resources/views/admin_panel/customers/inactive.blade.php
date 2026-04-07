@extends('admin_panel.layout.app')
@section('content')
<div class="container mt-4">
    <h3>Inactive Customers</h3>
    <a href="{{ route('customers.index') }}" class="btn btn-primary mb-3">← Back to Active List</a>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Customer ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Zone</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
            <tr>
                <td>{{ $customer->customer_id }}</td>
                <td>{{ $customer->customer_name }}</td>
                <td>{{ $customer->mobile }}</td>
                <td>{{ $customer->zone }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
